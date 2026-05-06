<?php

namespace App\Http\Controllers;

use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use App\Models\HoaDon;
use App\Models\ThongTinNhanVien;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NhanVienController extends Controller
{
    private const STAFF_SESSION_HOURS = 8;

    public function index()
    {
        return response()->json($this->baseQuery()->get());
    }

    public function show($id)
    {
        $nhanVien = $this->baseQuery()->find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        return response()->json($nhanVien);
    }

    public function workSessions(Request $request, $id)
    {
        $nhanVien = $this->baseQuery()->find($id);

        if (! $nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        if ($this->normalizeRole($nhanVien->vaiTro?->ten_vai_tro) !== 'staff') {
            return response()->json([
                'nhan_vien' => $nhanVien,
                'tracking_enabled' => false,
                'message' => 'Tài khoản admin không tính lịch sử ra vào làm việc.',
                'filter' => $this->resolveWorkSessionFilter($request)['meta'],
                'summary' => [
                    'tong_phien' => 0,
                    'tong_giay_lam' => 0,
                    'tong_gio_lam' => 0,
                    'dang_lam' => false,
                ],
                'sessions' => [],
            ]);
        }

        $this->closeExpiredWorkSessions($nhanVien->id_nhan_vien);

        $filter = $this->resolveWorkSessionFilter($request);
        $sessions = NhanVienDangNhapLog::query()
            ->where('id_nhan_vien', $nhanVien->id_nhan_vien)
            ->when($filter['from'], function ($query) use ($filter) {
                $query->whereBetween('thoi_gian_dang_nhap', [$filter['from'], $filter['to']]);
            })
            ->latest('thoi_gian_dang_nhap')
            ->get()
            ->map(fn (NhanVienDangNhapLog $log): array => $this->formatWorkSession($log));

        return response()->json([
            'nhan_vien' => $nhanVien,
            'tracking_enabled' => true,
            'filter' => $filter['meta'],
            'summary' => [
                'tong_phien' => $sessions->count(),
                'tong_giay_lam' => (int) $sessions->sum('thoi_luong_giay'),
                'tong_gio_lam' => round($sessions->sum('thoi_luong_giay') / 3600, 2),
                'dang_lam' => $sessions->contains(fn (array $session): bool => $session['trang_thai'] === 'dang_lam'),
            ],
            'sessions' => $sessions->values(),
        ]);
    }

    public function salesByDate(Request $request, $id)
    {
        $nhanVien = $this->baseQuery()->find($id);

        if (! $nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        try {
            $selectedDate = $request->filled('date')
                ? Carbon::parse((string) $request->query('date'))->startOfDay()
                : now()->startOfDay();
        } catch (\Throwable) {
            $selectedDate = now()->startOfDay();
        }

        $orders = HoaDon::query()
            ->where('id_nhan_vien', $nhanVien->id_nhan_vien)
            ->whereDate('ngay_ban', $selectedDate->toDateString())
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereHas('chiTiets')
            ->with([
                'khachHang:id_khach_hang,ten_khach_hang,so_dien_thoai,email',
                'latestLichSuDonHang',
                'chiTiets.loThuoc.thuoc:ma_thuoc,ten_thuoc,ham_luong,don_vi_tinh',
            ])
            ->orderByDesc('ngay_ban')
            ->orderByDesc('id_hoa_don')
            ->get()
            ->map(fn (HoaDon $hoaDon): array => $this->formatEmployeeOrder($hoaDon));

        return response()->json([
            'nhan_vien' => $nhanVien,
            'filter' => [
                'date' => $selectedDate->toDateString(),
                'label' => $selectedDate->format('d/m/Y'),
            ],
            'summary' => [
                'tong_don_hang' => $orders->count(),
                'tong_san_pham' => (int) $orders->sum('tong_san_pham'),
                'tong_doanh_thu' => round((float) $orders->sum('tien_thanh_toan'), 2),
            ],
            'orders' => $orders->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'so_dien_thoai' => [
                'required',
                'string',
                'size:10',
                Rule::unique('thong_tin_nhan_viens', 'so_dien_thoai'),
                Rule::unique('nhan_viens', 'ten_dang_nhap'),
                Rule::unique('khach_hangs', 'so_dien_thoai'),
            ],
            'mat_khau' => 'required|string|min:6|confirmed',
            'ho_ten' => 'required|string|min:5|max:100',
            'id_vai_tro' => 'required|exists:vai_tros,id_vai_tro',
            'id_bang_cap' => 'required|exists:bang_caps,id_bang_cap',
            'trang_thai' => 'sometimes|in:active,inactive'
        ], $this->employeeValidationMessages(), $this->employeeValidationAttributes());

        $nhanVien = DB::transaction(function () use ($validated) {
            $nhanVien = NhanVien::create([
                'ten_dang_nhap' => $validated['so_dien_thoai'],
                'mat_khau' => Hash::make($validated['mat_khau']),
                'ho_ten' => $validated['ho_ten'],
                'id_vai_tro' => $validated['id_vai_tro'],
                'id_bang_cap' => $validated['id_bang_cap'],
                'trang_thai' => $validated['trang_thai'] ?? 'active',
            ]);

            ThongTinNhanVien::create($this->defaultProfilePayload($nhanVien, $validated['so_dien_thoai']));

            return $nhanVien->load('vaiTro', 'bangCap', 'thongTinNhanVien');
        });

        return response()->json($nhanVien, 201);
    }

    public function update(Request $request, $id)
    {
        $nhanVien = NhanVien::find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        $validated = $request->validate([
            'so_dien_thoai' => [
                'sometimes',
                'required',
                'string',
                'size:10',
                Rule::unique('thong_tin_nhan_viens', 'so_dien_thoai')->ignore($nhanVien->id_nhan_vien, 'id_nhan_vien'),
                Rule::unique('nhan_viens', 'ten_dang_nhap')->ignore($nhanVien->id_nhan_vien, 'id_nhan_vien'),
                Rule::unique('khach_hangs', 'so_dien_thoai'),
            ],
            'ho_ten' => 'sometimes|required|string|min:5|max:100',
            'id_vai_tro' => 'sometimes|required|exists:vai_tros,id_vai_tro',
            'id_bang_cap' => 'sometimes|required|exists:bang_caps,id_bang_cap',
            'trang_thai' => 'sometimes|in:active,inactive'
        ], $this->employeeValidationMessages(), $this->employeeValidationAttributes());

        DB::transaction(function () use ($nhanVien, $validated) {
            $nhanVienPayload = collect($validated)
                ->except('so_dien_thoai')
                ->toArray();

            if (! empty($validated['so_dien_thoai'])) {
                $nhanVienPayload['ten_dang_nhap'] = $validated['so_dien_thoai'];
            }

            if (! empty($nhanVienPayload)) {
                $nhanVien->update($nhanVienPayload);
            }

            if (! empty($validated['so_dien_thoai'])) {
                $profile = $nhanVien->thongTinNhanVien()->first();

                if ($profile) {
                    $profile->update([
                        'so_dien_thoai' => $validated['so_dien_thoai'],
                    ]);
                } else {
                    ThongTinNhanVien::create($this->defaultProfilePayload($nhanVien, $validated['so_dien_thoai']));
                }
            }
        });

        $nhanVien->load('vaiTro', 'bangCap', 'thongTinNhanVien');

        return response()->json($nhanVien);
    }

    public function destroy($id)
    {
        $nhanVien = NhanVien::find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        $nhanVien->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $nhanViens = $this->baseQuery()
            ->where(function ($nhanVienQuery) use ($query) {
                $nhanVienQuery->where('ten_dang_nhap', 'like', '%' . $query . '%')
                    ->orWhere('ho_ten', 'like', '%' . $query . '%')
                    ->orWhereHas('thongTinNhanVien', function ($profileQuery) use ($query) {
                        $profileQuery->where('so_dien_thoai', 'like', '%' . $query . '%')
                            ->orWhere('email', 'like', '%' . $query . '%');
                    });
            })
            ->get();

        return response()->json($nhanViens);
    }

    public function changePassword(Request $request, $id)
    {
        $nhanVien = NhanVien::find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        $request->validate([
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>\[\]\/\\\\_\-+=~`;\']).+$/',
                'confirmed',
            ],
        ], $this->employeeValidationMessages(), $this->employeeValidationAttributes());

        $nhanVien->update([
            'mat_khau' => Hash::make($request->new_password)
        ]);

        return response()->json(['message' => 'Cập nhật mật khẩu thành công', 'nhan_vien' => $nhanVien]);
    }

    public function changeRole(Request $request, $id)
    {
        $nhanVien = NhanVien::find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        $request->validate([
            'id_vai_tro' => 'required|exists:vai_tros,id_vai_tro'
        ]);

        $nhanVien->update([
            'id_vai_tro' => $request->id_vai_tro
        ]);
        
        $nhanVien->load('vaiTro', 'bangCap', 'thongTinNhanVien');

        return response()->json(['message' => 'Cập nhật vai trò thành công', 'nhan_vien' => $nhanVien]);
    }

    private function baseQuery()
    {
        return NhanVien::with('vaiTro', 'bangCap', 'thongTinNhanVien')
            ->orderBy('id_nhan_vien');
    }

    private function employeeValidationMessages(): array
    {
        return [
            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại đăng nhập.',
            'so_dien_thoai.size' => 'Số điện thoại đăng nhập phải có đúng 10 chữ số.',
            'so_dien_thoai.unique' => 'Số điện thoại này đã được sử dụng.',
            'mat_khau.required' => 'Vui lòng nhập mật khẩu.',
            'mat_khau.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'mat_khau.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'ho_ten.required' => 'Vui lòng nhập họ tên.',
            'ho_ten.min' => 'Họ tên phải có ít nhất 5 ký tự.',
            'ho_ten.max' => 'Họ tên không được vượt quá 100 ký tự.',
            'id_vai_tro.required' => 'Vui lòng chọn vai trò.',
            'id_vai_tro.exists' => 'Vai trò được chọn không hợp lệ.',
            'id_bang_cap.required' => 'Vui lòng chọn bằng cấp.',
            'id_bang_cap.exists' => 'Bằng cấp được chọn không hợp lệ.',
            'trang_thai.in' => 'Trạng thái không hợp lệ.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'new_password.regex' => 'Mật khẩu mới phải có ít nhất 1 chữ in hoa và 1 ký tự đặc biệt.',
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ];
    }

    private function employeeValidationAttributes(): array
    {
        return [
            'so_dien_thoai' => 'số điện thoại đăng nhập',
            'mat_khau' => 'mật khẩu',
            'mat_khau_confirmation' => 'xác nhận mật khẩu',
            'ho_ten' => 'họ tên',
            'id_vai_tro' => 'vai trò',
            'id_bang_cap' => 'bằng cấp',
            'trang_thai' => 'trạng thái',
            'new_password' => 'mật khẩu mới',
            'new_password_confirmation' => 'xác nhận mật khẩu mới',
        ];
    }

    private function defaultProfilePayload(NhanVien $nhanVien, string $phone): array
    {
        return [
            'id_nhan_vien' => $nhanVien->id_nhan_vien,
            'so_dien_thoai' => $phone,
            'email' => 'nhanvien' . $nhanVien->id_nhan_vien . '@pharmago.local',
            'dia_chi' => 'Chưa cập nhật địa chỉ',
            'ngay_sinh' => now()->subYears(20)->toDateString(),
            'ngay_vao_lam' => now()->toDateString(),
        ];
    }

    private function resolveWorkSessionFilter(Request $request): array
    {
        try {
            if ($request->filled('date')) {
                $date = Carbon::parse((string) $request->query('date'));

                return [
                    'from' => $date->copy()->startOfDay(),
                    'to' => $date->copy()->endOfDay(),
                    'meta' => [
                        'type' => 'date',
                        'value' => $date->toDateString(),
                        'label' => $date->format('d/m/Y'),
                    ],
                ];
            }

            if ($request->filled('month')) {
                $month = Carbon::createFromFormat('Y-m', (string) $request->query('month'))->startOfMonth();

                return [
                    'from' => $month->copy()->startOfMonth(),
                    'to' => $month->copy()->endOfMonth(),
                    'meta' => [
                        'type' => 'month',
                        'value' => $month->format('Y-m'),
                        'label' => $month->format('m/Y'),
                    ],
                ];
            }

            if ($request->filled('year')) {
                $year = (int) $request->query('year');

                if ($year > 0) {
                    $yearStart = Carbon::create($year, 1, 1)->startOfYear();

                    return [
                        'from' => $yearStart->copy()->startOfYear(),
                        'to' => $yearStart->copy()->endOfYear(),
                        'meta' => [
                            'type' => 'year',
                            'value' => (string) $year,
                            'label' => (string) $year,
                        ],
                    ];
                }
            }
        } catch (\Throwable) {
            // Invalid filters fall back to the complete history.
        }

        return [
            'from' => null,
            'to' => null,
            'meta' => [
                'type' => 'all',
                'value' => null,
                'label' => 'Tất cả',
            ],
        ];
    }

    private function closeExpiredWorkSessions(int $nhanVienId): void
    {
        $now = now();

        NhanVienDangNhapLog::query()
            ->where('id_nhan_vien', $nhanVienId)
            ->whereNull('thoi_gian_dang_xuat')
            ->get()
            ->each(function (NhanVienDangNhapLog $log) use ($now): void {
                $expiresAt = $this->workSessionExpiresAt($log);

                if ($expiresAt && $expiresAt->lte($now)) {
                    $this->finishWorkSession($log, $expiresAt, 'expired');
                }
            });
    }

    private function formatWorkSession(NhanVienDangNhapLog $log): array
    {
        $now = now();
        $loginAt = $log->thoi_gian_dang_nhap;
        $expiresAt = $this->workSessionExpiresAt($log);
        $logoutAt = $log->thoi_gian_dang_xuat;
        $reason = $log->ly_do_dang_xuat;
        $status = 'dang_lam';

        if ($logoutAt) {
            $status = $reason === 'expired' ? 'tu_het_han' : 'da_dang_xuat';
        } elseif ($expiresAt && $expiresAt->lte($now)) {
            $logoutAt = $expiresAt;
            $reason = 'expired';
            $status = 'tu_het_han';
        }

        $endAt = $logoutAt ?: ($expiresAt && $expiresAt->lt($now) ? $expiresAt : $now);
        $durationSeconds = $loginAt ? max(0, (int) $loginAt->diffInSeconds($endAt)) : 0;

        return [
            'id' => $log->id,
            'thoi_gian_vao' => $loginAt?->toISOString(),
            'thoi_gian_ra' => $logoutAt?->toISOString(),
            'het_han_luc' => $expiresAt?->toISOString(),
            'thoi_luong_giay' => $durationSeconds,
            'so_gio_lam' => round($durationSeconds / 3600, 2),
            'trang_thai' => $status,
            'ly_do_dang_xuat' => $reason,
            'ngay' => $loginAt?->toDateString(),
            'thang' => $loginAt?->format('Y-m'),
            'nam' => $loginAt?->format('Y'),
        ];
    }

    private function formatEmployeeOrder(HoaDon $hoaDon): array
    {
        $items = $hoaDon->chiTiets
            ->map(function ($chiTiet): array {
                $loThuoc = $chiTiet->loThuoc;
                $thuoc = $loThuoc?->thuoc;

                return [
                    'id' => $chiTiet->getKey(),
                    'ma_thuoc' => $thuoc?->ma_thuoc,
                    'ten_thuoc' => $thuoc?->ten_thuoc ?: 'Sản phẩm không xác định',
                    'ham_luong' => $thuoc?->ham_luong,
                    'so_lo' => $loThuoc?->so_lo,
                    'don_vi_ban' => $chiTiet->don_vi_ban ?: $thuoc?->don_vi_tinh,
                    'so_luong' => (int) $chiTiet->so_luong,
                    'gia_ban' => round((float) $chiTiet->gia_ban, 2),
                    'thanh_tien' => round((float) $chiTiet->thanh_tien, 2),
                ];
            })
            ->values();

        return [
            'id_hoa_don' => $hoaDon->id_hoa_don,
            'ma_hoa_don' => $hoaDon->ma_hoa_don,
            'ngay_ban' => $hoaDon->ngay_ban?->toISOString(),
            'kenh_ban' => $hoaDon->kenh_ban,
            'trang_thai_xu_ly' => $hoaDon->trang_thai_xu_ly,
            'khach_hang' => [
                'ten_khach_hang' => $hoaDon->khachHang?->ten_khach_hang,
                'so_dien_thoai' => $hoaDon->khachHang?->so_dien_thoai,
                'email' => $hoaDon->khachHang?->email,
            ],
            'tong_tien' => round((float) $hoaDon->tong_tien, 2),
            'giam_gia' => round((float) $hoaDon->giam_gia, 2),
            'thue_vat' => round((float) $hoaDon->thue_vat, 2),
            'tien_thanh_toan' => round((float) $hoaDon->tien_thanh_toan, 2),
            'trang_thai' => $hoaDon->latestLichSuDonHang?->trang_thai ?: 'Thành công',
            'tong_san_pham' => (int) $items->sum('so_luong'),
            'items' => $items,
        ];
    }

    private function workSessionExpiresAt(NhanVienDangNhapLog $log): ?Carbon
    {
        if ($log->het_han_luc) {
            return $log->het_han_luc;
        }

        return $log->thoi_gian_dang_nhap?->copy()->addHours(self::STAFF_SESSION_HOURS);
    }

    private function finishWorkSession(NhanVienDangNhapLog $log, $logoutAt, string $reason): void
    {
        $loginAt = $log->thoi_gian_dang_nhap ?: $logoutAt;

        if ($logoutAt->lt($loginAt)) {
            $logoutAt = $loginAt->copy();
        }

        $log->update([
            'thoi_gian_dang_xuat' => $logoutAt,
            'thoi_luong_giay' => (int) $loginAt->diffInSeconds($logoutAt),
            'ly_do_dang_xuat' => $reason,
        ]);
    }

    private function normalizeRole(?string $role): string
    {
        $normalized = Str::of((string) $role)
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();

        if ($normalized === 'admin') {
            return 'admin';
        }

        if (in_array($normalized, ['staff', 'nhan_vien', 'nhanvien'], true)) {
            return 'staff';
        }

        return $normalized;
    }
}
