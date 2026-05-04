<?php

namespace App\Http\Controllers;

use App\Http\Requests\HoaDonStatisticsRequest;
use App\Http\Requests\SearchHoaDonRequest;
use App\Http\Requests\StoreHoaDonRequest;
use App\Mail\OrderConfirmedMail;
use App\Mail\OrderRejectedMail;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\LichSuDonHang;
use App\Models\LoThuoc;
use App\Models\MaGiamGiaLuotDung;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class HoaDonController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lấy danh sách hóa đơn thành công.',
            'data' => $this->baseQuery()->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $hoaDon = $this->baseQuery()->find($id);

        if (! $hoaDon) {
            return response()->json([
                'message' => 'Không tìm thấy hóa đơn.',
            ], 404);
        }

        return response()->json([
            'message' => 'Lấy chi tiết hóa đơn thành công.',
            'data' => $hoaDon,
        ]);
    }

    public function store(StoreHoaDonRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tongTien = (float) $validated['tong_tien'];
        $giamGia = (float) ($validated['giam_gia'] ?? 0);
        $tienSauGiam = max($tongTien - $giamGia, 0);
        $thueVat = $this->calculateVatAmount($tienSauGiam);

        $hoaDon = HoaDon::create([
            'ma_hoa_don' => $validated['ma_hoa_don'] ?? $this->generateInvoiceCode(),
            'id_khach_hang' => $validated['id_khach_hang'],
            'id_nhan_vien' => $request->user()->id_nhan_vien,
            'kenh_ban' => 'he_thong',
            'trang_thai_xu_ly' => 'da_xac_nhan',
            'tong_tien' => $tongTien,
            'giam_gia' => $giamGia,
            'thue_vat' => $thueVat,
            'tien_thanh_toan' => $tienSauGiam + $thueVat,
            'ngay_ban' => $validated['ngay_ban'] ?? now(),
        ]);

        $hoaDon->load(['khachHang', 'nhanVien.vaiTro']);

        return response()->json([
            'message' => 'Tạo hóa đơn thành công.',
            'data' => $hoaDon,
        ], 201);
    }

    public function search(SearchHoaDonRequest $request): JsonResponse
    {
        $keyword = $request->validated()['q'];

        $hoaDons = $this->baseQuery()
            ->where(function (Builder $query) use ($keyword): void {
                $query->where('ma_hoa_don', 'like', '%' . $keyword . '%')
                    ->orWhereHas('khachHang', function (Builder $khachHangQuery) use ($keyword): void {
                        $khachHangQuery->where('ten_khach_hang', 'like', '%' . $keyword . '%')
                            ->orWhere('so_dien_thoai', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('nhanVien', function (Builder $nhanVienQuery) use ($keyword): void {
                        $nhanVienQuery->where('ho_ten', 'like', '%' . $keyword . '%')
                            ->orWhere('ten_dang_nhap', 'like', '%' . $keyword . '%');
                    });
            })
            ->get();

        return response()->json([
            'message' => 'Tìm kiếm hóa đơn thành công.',
            'data' => $hoaDons,
        ]);
    }

    public function pendingNotifications(): JsonResponse
    {
        $recentQuery = $this->baseQuery()
            ->where('kenh_ban', 'he_thong')
            ->where('trang_thai_xu_ly', 'cho_xac_nhan')
            ->where('ngay_ban', '>=', now()->subDays(2));

        $tongThongBao = (clone $recentQuery)->count();
        $hoaDons = (clone $recentQuery)
            ->limit(8)
            ->get()
            ->map(function (HoaDon $hoaDon): array {
                return [
                    'id_hoa_don' => $hoaDon->id_hoa_don,
                    'ma_hoa_don' => $hoaDon->ma_hoa_don,
                    'ngay_ban' => optional($hoaDon->ngay_ban)?->toIso8601String(),
                    'tong_tien' => (float) $hoaDon->tien_thanh_toan,
                    'khach_hang' => [
                        'ten_khach_hang' => $hoaDon->khachHang?->ten_khach_hang,
                        'so_dien_thoai' => $hoaDon->khachHang?->so_dien_thoai,
                    ],
                    'trang_thai' => $hoaDon->latestLichSuDonHang?->trang_thai ?: 'Chờ xác nhận',
                    'trang_thai_xu_ly' => $hoaDon->trang_thai_xu_ly,
                    'kenh_ban' => $hoaDon->kenh_ban,
                    'ghi_chu' => $hoaDon->latestLichSuDonHang?->ghi_chu,
                    'thoi_gian_cap_nhat' => optional($hoaDon->latestLichSuDonHang?->thoi_gian)?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Lấy thông báo đơn hàng mới thành công.',
            'data' => $hoaDons,
            'tong_thong_bao' => $tongThongBao,
        ]);
    }

    public function statistics(HoaDonStatisticsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = $this->revenueQuery();

        if (! empty($filters['from'])) {
            $query->whereDate('ngay_ban', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('ngay_ban', '<=', $filters['to']);
        }

        $tongHoaDon = (clone $query)->count();
        $tongTien = (float) (clone $query)->sum('tong_tien');
        $tongGiamGia = (float) (clone $query)->sum('giam_gia');
        $tongThueVat = (float) (clone $query)->sum('thue_vat');
        $tongThanhToan = (float) (clone $query)->sum('tien_thanh_toan');
        $doanhThuHeThong = (float) (clone $query)->where('kenh_ban', 'he_thong')->sum('tien_thanh_toan');
        $doanhThuTaiQuay = (float) (clone $query)->where('kenh_ban', 'tai_quay')->sum('tien_thanh_toan');
        $hoaDonHeThong = (clone $query)->where('kenh_ban', 'he_thong')->count();
        $hoaDonTaiQuay = (clone $query)->where('kenh_ban', 'tai_quay')->count();

        $doanhThuTheoNgay = (clone $query)
            ->selectRaw('date(ngay_ban) as ngay, count(*) as so_hoa_don, sum(tien_thanh_toan) as doanh_thu')
            ->groupByRaw('date(ngay_ban)')
            ->orderBy('ngay')
            ->get();

        $doanhThuTheoNhanVien = (clone $query)
            ->join('nhan_viens', 'nhan_viens.id_nhan_vien', '=', 'hoa_dons.id_nhan_vien')
            ->selectRaw('hoa_dons.id_nhan_vien, nhan_viens.ho_ten, count(*) as so_hoa_don, sum(hoa_dons.tien_thanh_toan) as doanh_thu')
            ->groupBy('hoa_dons.id_nhan_vien', 'nhan_viens.ho_ten')
            ->orderByDesc('doanh_thu')
            ->get();

        return response()->json([
            'message' => 'Thống kê doanh thu thành công.',
            'data' => [
                'bo_loc' => [
                    'from' => $filters['from'] ?? null,
                    'to' => $filters['to'] ?? null,
                ],
                'tong_quan' => [
                    'tong_so_hoa_don' => $tongHoaDon,
                    'tong_tien' => round($tongTien, 2),
                    'tong_giam_gia' => round($tongGiamGia, 2),
                    'tong_thue_vat' => round($tongThueVat, 2),
                    'tong_tien_thanh_toan' => round($tongThanhToan, 2),
                    'gia_tri_trung_binh' => $tongHoaDon > 0 ? round($tongThanhToan / $tongHoaDon, 2) : 0,
                    'doanh_thu_he_thong' => round($doanhThuHeThong, 2),
                    'doanh_thu_tai_quay' => round($doanhThuTaiQuay, 2),
                    'hoa_don_he_thong' => $hoaDonHeThong,
                    'hoa_don_tai_quay' => $hoaDonTaiQuay,
                ],
                'doanh_thu_theo_ngay' => $doanhThuTheoNgay,
                'doanh_thu_theo_nhan_vien' => $doanhThuTheoNhanVien,
            ],
        ]);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        if (! $this->canProcessSystemOrder($request)) {
            return response()->json([
                'message' => 'Tài khoản này chưa đăng nhập kênh hệ thống nên không thể xác nhận đơn online.',
            ], 403);
        }

        $validated = $request->validate([
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ]);

        $hoaDon = DB::transaction(function () use ($request, $id, $validated): HoaDon {
            $hoaDon = HoaDon::query()
                ->with($this->orderActionRelations())
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (! $hoaDon) {
                throw ValidationException::withMessages([
                    'id_hoa_don' => ['Không tìm thấy hóa đơn.'],
                ]);
            }

            if ($hoaDon->trang_thai_xu_ly !== 'cho_xac_nhan') {
                throw ValidationException::withMessages([
                    'trang_thai' => ['Chỉ đơn hàng đang chờ xác nhận mới được xác nhận.'],
                ]);
            }

            $hoaDon->update([
                'id_nhan_vien' => $request->user()->id_nhan_vien,
                'kenh_ban' => 'he_thong',
                'trang_thai_xu_ly' => 'da_xac_nhan',
                'ly_do_tu_choi' => null,
            ]);

            LichSuDonHang::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'trang_thai' => 'Đã xác nhận',
                'ghi_chu' => $validated['ghi_chu'] ?? 'Nhân viên hệ thống đã xác nhận đơn hàng.',
                'thoi_gian' => now(),
                'id_nhan_vien' => $request->user()->id_nhan_vien,
            ]);

            return $this->freshOrderForResponse($hoaDon->id_hoa_don);
        });

        $this->sendConfirmedMail($hoaDon);

        return response()->json([
            'message' => 'Xác nhận đơn hàng thành công. Hệ thống đã gửi email cho khách hàng.',
            'data' => $hoaDon,
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        if (! $this->canProcessSystemOrder($request)) {
            return response()->json([
                'message' => 'Tài khoản này chưa đăng nhập kênh hệ thống nên không thể từ chối đơn online.',
            ], 403);
        }

        $validated = $request->validate([
            'ly_do_tu_choi' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'ly_do_tu_choi.required' => 'Vui lòng nhập lý do từ chối đơn hàng.',
            'ly_do_tu_choi.min' => 'Lý do từ chối phải có ít nhất 5 ký tự.',
        ]);

        $hoaDon = DB::transaction(function () use ($request, $id, $validated): HoaDon {
            $hoaDon = HoaDon::query()
                ->with($this->orderActionRelations())
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (! $hoaDon) {
                throw ValidationException::withMessages([
                    'id_hoa_don' => ['Không tìm thấy hóa đơn.'],
                ]);
            }

            if ($hoaDon->trang_thai_xu_ly !== 'cho_xac_nhan') {
                throw ValidationException::withMessages([
                    'trang_thai' => ['Chỉ đơn hàng đang chờ xác nhận mới được từ chối.'],
                ]);
            }

            $this->restoreReservedInventory($hoaDon);
            $this->restoreCustomerRewardsAndCoupon($hoaDon);

            $hoaDon->update([
                'id_nhan_vien' => $request->user()->id_nhan_vien,
                'kenh_ban' => 'he_thong',
                'trang_thai_xu_ly' => 'tu_choi',
                'ly_do_tu_choi' => $validated['ly_do_tu_choi'],
            ]);

            LichSuDonHang::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'trang_thai' => 'Từ chối',
                'ghi_chu' => $validated['ly_do_tu_choi'],
                'thoi_gian' => now(),
                'id_nhan_vien' => $request->user()->id_nhan_vien,
            ]);

            return $this->freshOrderForResponse($hoaDon->id_hoa_don);
        });

        $this->sendRejectedMail($hoaDon);

        return response()->json([
            'message' => 'Đã từ chối đơn hàng và gửi email thông báo lý do cho khách hàng.',
            'data' => $hoaDon,
        ]);
    }

    private function baseQuery(): Builder
    {
        return HoaDon::query()
            ->whereHas('chiTiets')
            ->with([
                'khachHang:id_khach_hang,ten_khach_hang,so_dien_thoai,email',
                'nhanVien:id_nhan_vien,ten_dang_nhap,ho_ten,id_vai_tro',
                'nhanVien.vaiTro:id_vai_tro,ten_vai_tro',
                'latestLichSuDonHang',
            ])
            ->orderByDesc('ngay_ban')
            ->orderByDesc('id_hoa_don');
    }

    private function revenueQuery(): Builder
    {
        return HoaDon::query()
            ->whereHas('chiTiets')
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh']);
    }

    private function canProcessSystemOrder(Request $request): bool
    {
        $nhanVien = $request->user();

        if (! $nhanVien instanceof NhanVien) {
            return false;
        }

        $role = strtolower((string) $nhanVien->vaiTro?->ten_vai_tro);
        if ($role === 'admin') {
            return true;
        }

        $token = $request->user()?->currentAccessToken();
        $tokenId = $token && method_exists($token, 'getKey') ? $token->getKey() : null;

        return NhanVienDangNhapLog::query()
            ->where('id_nhan_vien', $nhanVien->id_nhan_vien)
            ->where('kenh_dang_nhap', 'he_thong')
            ->whereNull('thoi_gian_dang_xuat')
            ->when($tokenId, fn ($query) => $query->where('token_id', $tokenId))
            ->exists();
    }

    private function orderActionRelations(): array
    {
        return [
            'khachHang',
            'nhanVien',
            'maGiamGia',
            'thanhToan',
            'chiTiets.loThuoc.thuoc',
            'lichSuDonHangs',
            'latestLichSuDonHang',
        ];
    }

    private function freshOrderForResponse(int $idHoaDon): HoaDon
    {
        return $this->baseQuery()
            ->with([
                'thanhToan',
                'chiTiets.loThuoc.thuoc',
                'lichSuDonHangs',
            ])
            ->findOrFail($idHoaDon);
    }

    private function restoreReservedInventory(HoaDon $hoaDon): void
    {
        foreach ($hoaDon->chiTiets as $detail) {
            LoThuoc::query()
                ->whereKey($detail->id_lo)
                ->lockForUpdate()
                ->increment('so_luong_con', (int) $detail->so_luong);
        }
    }

    private function restoreCustomerRewardsAndCoupon(HoaDon $hoaDon): void
    {
        $khachHang = KhachHang::query()
            ->whereKey($hoaDon->id_khach_hang)
            ->lockForUpdate()
            ->first();

        if ($khachHang) {
            $khachHang->forceFill([
                'diem_tich_luy' => max(
                    0,
                    (int) $khachHang->diem_tich_luy
                    + (int) ($hoaDon->diem_da_su_dung ?? 0)
                    - (int) ($hoaDon->diem_da_cong ?? 0)
                ),
            ])->save();
        }

        if (! $hoaDon->ma_giam_gia_id) {
            return;
        }

        $usage = MaGiamGiaLuotDung::query()
            ->where('ma_giam_gia_id', $hoaDon->ma_giam_gia_id)
            ->where('id_khach_hang', $hoaDon->id_khach_hang)
            ->lockForUpdate()
            ->first();

        if (! $usage) {
            return;
        }

        if ((int) $usage->so_lan_su_dung <= 1) {
            $usage->delete();
            return;
        }

        $usage->decrement('so_lan_su_dung');
    }

    private function buildMailItems(HoaDon $hoaDon): array
    {
        return $hoaDon->chiTiets
            ->map(function ($detail): array {
                $thuoc = $detail->loThuoc?->thuoc;
                $heSo = max(1, (int) ($detail->he_so_quy_doi_ban ?? 1));

                return [
                    'ten' => $thuoc?->ten_thuoc ?: 'Sản phẩm',
                    'donVi' => $detail->don_vi_ban ?: $thuoc?->don_vi_tinh ?: '-',
                    'soLuong' => (float) $detail->so_luong / $heSo,
                    'gia' => (float) $detail->gia_ban,
                    'thanhTien' => (float) $detail->thanh_tien,
                ];
            })
            ->values()
            ->all();
    }

    private function sendConfirmedMail(HoaDon $hoaDon): void
    {
        if (! filled($hoaDon->khachHang?->email)) {
            return;
        }

        try {
            Mail::to($hoaDon->khachHang->email)->send(new OrderConfirmedMail(
                $hoaDon->khachHang,
                $hoaDon,
                $this->buildMailItems($hoaDon),
                [
                    'payment_method_label' => $this->paymentLabel($hoaDon->thanhToan?->phuong_thuc),
                    'shipping_address' => $hoaDon->khachHang?->dia_chi,
                ]
            ));
        } catch (\Throwable $exception) {
            Log::warning('Khong the gui mail xac nhan don hang.', [
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendRejectedMail(HoaDon $hoaDon): void
    {
        if (! filled($hoaDon->khachHang?->email)) {
            return;
        }

        try {
            Mail::to($hoaDon->khachHang->email)->send(new OrderRejectedMail(
                $hoaDon->khachHang,
                $hoaDon,
                (string) $hoaDon->ly_do_tu_choi,
                $this->buildMailItems($hoaDon)
            ));
        } catch (\Throwable $exception) {
            Log::warning('Khong the gui mail tu choi don hang.', [
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function paymentLabel(?string $method): string
    {
        return match ((string) $method) {
            'momo' => 'MoMo',
            'zalopay' => 'ZaloPay',
            'the_atm' => 'Thẻ ATM',
            'the_quoc_te' => 'Thẻ quốc tế',
            default => 'Tiền mặt',
        };
    }

    private function generateInvoiceCode(): string
    {
        do {
            $code = 'HD' . now()->format('YmdHis') . random_int(10, 99);
        } while (HoaDon::query()->where('ma_hoa_don', $code)->exists());

        return $code;
    }

    private function calculateVatAmount(float $amountAfterDiscount): float
    {
        return round(max($amountAfterDiscount, 0) * 0.1, 2);
    }
}
