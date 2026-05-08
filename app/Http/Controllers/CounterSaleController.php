<?php

namespace App\Http\Controllers;

use App\Models\ChiTietHoaDon;
use App\Models\CounterSalePayosSession;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\KhuyenMai;
use App\Models\LichSuDonHang;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use App\Models\ThanhToan;
use App\Models\Thuoc;
use App\Services\PayosService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CounterSaleController extends Controller
{
    private const COUNTER_CUSTOMER_TOKEN_MINUTES = 60;
    private const REWARD_VND_PER_POINT = 1000;
    private const REWARD_REDEEM_POINTS = 1000;
    private const REWARD_REDEEM_VALUE = 10000;

    public function authenticateCustomer(Request $request): JsonResponse
    {
        $nhanVien = $request->user();

        if (! $nhanVien instanceof NhanVien) {
            return response()->json([
                'message' => 'Chỉ nhân viên mới được bán tại quầy.',
            ], 403);
        }

        if (! $this->hasActiveCounterSession($nhanVien, $request)) {
            return response()->json([
                'message' => 'Tài khoản này chưa đăng nhập kênh tại quầy.',
            ], 403);
        }

        $validated = $request->validate([
            'tai_khoan' => ['required', 'string', 'max:100'],
            'mat_khau' => ['required', 'string', 'min:6'],
        ], [
            'tai_khoan.required' => 'Vui lòng nhập email hoặc số điện thoại khách hàng.',
            'mat_khau.required' => 'Vui lòng nhập mật khẩu khách hàng.',
        ]);

        $taiKhoan = (string) $validated['tai_khoan'];
        $khachHang = KhachHang::query()
            ->where('so_dien_thoai', $taiKhoan)
            ->orWhere('email', $taiKhoan)
            ->first();

        if (! $khachHang || ! Hash::check((string) $validated['mat_khau'], $khachHang->mat_khau)) {
            throw ValidationException::withMessages([
                'tai_khoan' => ['Thông tin khách hàng không chính xác.'],
            ]);
        }

        if (! $khachHang->email_verified) {
            return response()->json([
                'message' => 'Tài khoản khách hàng chưa xác minh email.',
            ], 403);
        }

        return response()->json([
            'message' => 'Đã chọn khách hàng cho hóa đơn tại quầy.',
            'data' => [
                'khach_hang' => $this->counterCustomerPayload($khachHang),
                'customer_token' => $this->createCounterCustomerToken($khachHang),
            ],
        ]);
    }

    public function findCustomerByPhone(Request $request): JsonResponse
    {
        $nhanVien = $request->user();

        if (! $nhanVien instanceof NhanVien) {
            return response()->json([
                'message' => 'Chỉ nhân viên mới được bán tại quầy.',
            ], 403);
        }

        if (! $this->hasActiveCounterSession($nhanVien, $request)) {
            return response()->json([
                'message' => 'Tài khoản này chưa đăng nhập kênh tại quầy.',
            ], 403);
        }

        $validated = $request->validate([
            'so_dien_thoai' => ['required', 'string', 'regex:/^\d{10}$/'],
        ], [
            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại khách hàng.',
            'so_dien_thoai.regex' => 'Số điện thoại khách hàng phải gồm đúng 10 chữ số.',
        ]);

        $phone = (string) $validated['so_dien_thoai'];

        $khachHang = KhachHang::query()
            ->where('so_dien_thoai', $phone)
            ->first();

        if (! $khachHang) {
            throw ValidationException::withMessages([
                'so_dien_thoai' => ['Không tìm thấy khách hàng với số điện thoại này.'],
            ]);
        }

        return response()->json([
            'message' => 'Đã chọn khách hàng cho hóa đơn tại quầy.',
            'data' => [
                'khach_hang' => $this->counterCustomerPayload($khachHang),
                'customer_token' => $this->createCounterCustomerToken($khachHang),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $nhanVien = $request->user();

        if (! $nhanVien instanceof NhanVien) {
            return response()->json([
                'message' => 'Chỉ nhân viên mới được bán tại quầy.',
            ], 403);
        }

        if (! $this->hasActiveCounterSession($nhanVien, $request)) {
            return response()->json([
                'message' => 'Tài khoản này chưa đăng nhập kênh tại quầy.',
            ], 403);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.ma_thuoc' => ['required', 'string', 'exists:thuocs,ma_thuoc'],
            'items.*.so_luong' => ['required', 'integer', 'min:1'],
            'items.*.don_vi' => ['nullable', 'string', 'max:50'],
            'phuong_thuc_thanh_toan' => ['required', 'string', 'in:tien_mat'],
            'customer_token' => ['nullable', 'string'],
            'su_dung_diem' => ['nullable', 'boolean'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => 'Vui lòng chọn ít nhất một sản phẩm để bán tại quầy.',
            'items.*.so_luong.min' => 'Số lượng bán phải lớn hơn 0.',
            'phuong_thuc_thanh_toan.required' => 'Vui lòng chọn phương thức thanh toán.',
            'phuong_thuc_thanh_toan.in' => 'Thanh toán tiền mặt mới tạo hóa đơn trực tiếp. PayOS cần tạo mã QR riêng.',
        ]);

        $result = DB::transaction(function () use ($validated, $nhanVien): array {
            [$khachHang, $isRegisteredCustomer] = $this->resolveSaleCustomer($validated['customer_token'] ?? null);
            [$tongTien, $detailRows, $summaryItems] = $this->buildSaleDetails($validated['items']);
            [$giamGiaDiem, $diemDaSuDung] = $isRegisteredCustomer
                ? $this->resolveRewardPointDiscount((bool) ($validated['su_dung_diem'] ?? false), $tongTien, $khachHang)
                : [0, 0];
            $tienSauGiam = max($tongTien - $giamGiaDiem, 0);
            $thueVat = $this->calculateVatAmount($tienSauGiam);
            $tienThanhToan = $tienSauGiam + $thueVat;
            $diemDaCong = $isRegisteredCustomer ? $this->calculateRewardPointsEarned($tongTien) : 0;

            if ($isRegisteredCustomer) {
                $khachHang->forceFill([
                    'diem_tich_luy' => max(0, (int) $khachHang->diem_tich_luy - $diemDaSuDung + $diemDaCong),
                ])->save();
            }

            $hoaDon = HoaDon::create([
                'ma_hoa_don' => $this->generateInvoiceCode('TQ'),
                'id_khach_hang' => $khachHang->id_khach_hang,
                'id_nhan_vien' => $nhanVien->id_nhan_vien,
                'kenh_ban' => 'tai_quay',
                'trang_thai_xu_ly' => 'hoan_thanh',
                'tong_tien' => $tongTien,
                'giam_gia' => $giamGiaDiem,
                'giam_gia_ma' => 0,
                'giam_gia_diem' => $giamGiaDiem,
                'thue_vat' => $thueVat,
                'tien_thanh_toan' => $tienThanhToan,
                'diem_da_su_dung' => $diemDaSuDung,
                'diem_da_cong' => $diemDaCong,
                'diem_thuong_da_xu_ly' => $isRegisteredCustomer,
                'ngay_ban' => now(),
            ]);

            foreach ($detailRows as $detailRow) {
                ChiTietHoaDon::create([
                    'id_hoa_don' => $hoaDon->id_hoa_don,
                    'id_lo' => $detailRow['id_lo'],
                    'don_vi_ban' => $detailRow['don_vi_ban'],
                    'he_so_quy_doi_ban' => $detailRow['he_so_quy_doi_ban'],
                    'so_luong' => $detailRow['so_luong'],
                    'gia_ban' => $detailRow['gia_ban'],
                    'thanh_tien' => $detailRow['thanh_tien'],
                ]);
            }

            ThanhToan::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'phuong_thuc' => $validated['phuong_thuc_thanh_toan'],
                'so_tien' => $tienThanhToan,
                'thoi_gian' => now(),
                'ma_giao_dich' => $validated['phuong_thuc_thanh_toan'] === 'tien_mat'
                    ? null
                    : $this->generateTransactionCode(),
            ]);

            LichSuDonHang::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'trang_thai' => 'Hoàn thành tại quầy',
                'ghi_chu' => $validated['ghi_chu'] ?? 'Bán trực tiếp tại quầy.',
                'thoi_gian' => now(),
                'id_nhan_vien' => $nhanVien->id_nhan_vien,
            ]);

            $hoaDon->load(['khachHang', 'nhanVien', 'thanhToan', 'latestLichSuDonHang']);

            return [
                'hoa_don' => $hoaDon,
                'items' => $summaryItems,
                'khach_hang' => $isRegisteredCustomer ? $this->counterCustomerPayload($khachHang->fresh()) : null,
            ];
        });

        return response()->json([
            'message' => 'Thanh toán tại quầy thành công.',
            'data' => [
                ...$result['hoa_don']->toArray(),
                'items' => $result['items'],
                'khach_hang_tich_diem' => $result['khach_hang'],
            ],
        ], 201);
    }

    public function createPayos(Request $request, PayosService $payos): JsonResponse
    {
        $nhanVien = $request->user();

        if (! $nhanVien instanceof NhanVien) {
            return response()->json([
                'message' => 'Chỉ nhân viên mới được bán tại quầy.',
            ], 403);
        }

        if (! $this->hasActiveCounterSession($nhanVien, $request)) {
            return response()->json([
                'message' => 'Tài khoản này chưa đăng nhập kênh tại quầy.',
            ], 403);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.ma_thuoc' => ['required', 'string', 'exists:thuocs,ma_thuoc'],
            'items.*.so_luong' => ['required', 'integer', 'min:1'],
            'items.*.don_vi' => ['nullable', 'string', 'max:50'],
            'phuong_thuc_thanh_toan' => ['required', 'string', 'in:payos'],
            'customer_token' => ['nullable', 'string'],
            'su_dung_diem' => ['nullable', 'boolean'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => 'Vui lòng chọn ít nhất một sản phẩm để bán tại quầy.',
            'items.*.so_luong.min' => 'Số lượng bán phải lớn hơn 0.',
            'phuong_thuc_thanh_toan.required' => 'Vui lòng chọn phương thức thanh toán.',
            'phuong_thuc_thanh_toan.in' => 'Phương thức thanh toán PayOS không hợp lệ.',
        ]);

        $session = DB::transaction(function () use ($validated, $nhanVien, $payos): CounterSalePayosSession {
            [$khachHang, $isRegisteredCustomer] = $this->resolveSaleCustomer($validated['customer_token'] ?? null);
            [$tongTien, $detailRows, $summaryItems] = $this->buildSaleDetails($validated['items'], false);
            [$giamGiaDiem, $diemDaSuDung] = $isRegisteredCustomer
                ? $this->resolveRewardPointDiscount((bool) ($validated['su_dung_diem'] ?? false), $tongTien, $khachHang)
                : [0, 0];
            $tienSauGiam = max($tongTien - $giamGiaDiem, 0);
            $thueVat = $this->calculateVatAmount($tienSauGiam);
            $tienThanhToan = $tienSauGiam + $thueVat;
            $diemDaCong = $isRegisteredCustomer ? $this->calculateRewardPointsEarned($tongTien) : 0;

            $session = CounterSalePayosSession::create([
                'session_key' => (string) Str::uuid(),
                'id_nhan_vien' => $nhanVien->id_nhan_vien,
                'id_khach_hang' => $khachHang->id_khach_hang,
                'is_registered_customer' => $isRegisteredCustomer,
                'status' => 'pending',
                'items_payload' => array_values($validated['items']),
                'detail_rows' => $detailRows,
                'summary_items' => $summaryItems,
                'tong_tien' => $tongTien,
                'giam_gia_diem' => $giamGiaDiem,
                'thue_vat' => $thueVat,
                'tien_thanh_toan' => $tienThanhToan,
                'diem_da_su_dung' => $diemDaSuDung,
                'diem_da_cong' => $diemDaCong,
                'ghi_chu' => $validated['ghi_chu'] ?? null,
            ]);

            $orderCode = $this->generateCounterPayosOrderCode($session);
            $session->forceFill(['payos_order_code' => $orderCode])->save();

            try {
                $payosLink = $payos->createPaymentLinkFromData(
                    $orderCode,
                    (int) round($tienThanhToan),
                    'PHARMAGO TQ ' . $session->id,
                    array_map(fn (array $item): array => [
                        'name' => $item['ten'] ?? 'San pham',
                        'quantity' => (int) ($item['so_luong'] ?? 1),
                        'price' => (int) ($item['gia_ban'] ?? 0),
                    ], $summaryItems)
                );
            } catch (\RuntimeException $exception) {
                throw ValidationException::withMessages([
                    'payos' => [$exception->getMessage()],
                ]);
            }

            $session->forceFill([
                'payos_payment_link_id' => $payosLink['payment_link_id'],
                'payos_checkout_url' => $payosLink['checkout_url'],
                'payos_qr_code' => $payosLink['qr_code'],
                'payos_payload' => $payosLink['raw'],
            ])->save();

            return $session->fresh(['khachHang']);
        });

        return response()->json([
            'message' => 'Đã tạo mã QR PayOS tại quầy.',
            'data' => $this->formatCounterPayosSession($session),
        ], 201);
    }

    public function payosStatus(Request $request, string $sessionKey): JsonResponse
    {
        $nhanVien = $request->user();

        if (! $nhanVien instanceof NhanVien) {
            return response()->json([
                'message' => 'Chỉ nhân viên mới được bán tại quầy.',
            ], 403);
        }

        $session = CounterSalePayosSession::query()
            ->with(['khachHang', 'hoaDon.thanhToan'])
            ->where('session_key', $sessionKey)
            ->where('id_nhan_vien', $nhanVien->id_nhan_vien)
            ->first();

        if (! $session) {
            return response()->json([
                'message' => 'Không tìm thấy phiên PayOS tại quầy.',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatCounterPayosSession($session),
        ]);
    }

    public function cancelPayos(Request $request, string $sessionKey): JsonResponse
    {
        $nhanVien = $request->user();

        if (! $nhanVien instanceof NhanVien) {
            return response()->json([
                'message' => 'Chỉ nhân viên mới được bán tại quầy.',
            ], 403);
        }

        if (! $this->hasActiveCounterSession($nhanVien, $request)) {
            return response()->json([
                'message' => 'Tài khoản này chưa đăng nhập kênh tại quầy.',
            ], 403);
        }

        DB::transaction(function () use ($sessionKey, $nhanVien): void {
            $session = CounterSalePayosSession::query()
                ->where('session_key', $sessionKey)
                ->where('id_nhan_vien', $nhanVien->id_nhan_vien)
                ->lockForUpdate()
                ->first();

            if (! $session) {
                throw ValidationException::withMessages([
                    'payos' => ['Không tìm thấy phiên PayOS tại quầy.'],
                ]);
            }

            if ($session->status === 'paid' || $session->id_hoa_don) {
                throw ValidationException::withMessages([
                    'payos' => ['Phiên PayOS này đã thanh toán nên không thể hủy.'],
                ]);
            }

            $session->forceFill([
                'status' => 'canceled',
                'error_message' => null,
            ])->save();
        });

        return response()->json([
            'message' => 'Đã hủy thanh toán PayOS tại quầy.',
            'data' => null,
        ]);
    }

    public function completePayosSessionFromWebhook(CounterSalePayosSession $session, array $data, array $payload, $paidAt = null): void
    {
        if ($session->status === 'paid' && $session->id_hoa_don) {
            return;
        }

        if ($session->status === 'canceled') {
            $session->forceFill([
                'payos_payload' => $data ?: $payload,
                'payos_paid_at' => $paidAt,
            ])->save();

            return;
        }

        $khachHang = KhachHang::query()
            ->whereKey($session->id_khach_hang)
            ->lockForUpdate()
            ->first();

        if (! $khachHang) {
            $khachHang = $this->resolveWalkInCustomer();
        }

        if ($session->is_registered_customer) {
            $khachHang->forceFill([
                'diem_tich_luy' => max(
                    0,
                    (int) $khachHang->diem_tich_luy - (int) $session->diem_da_su_dung + (int) $session->diem_da_cong
                ),
            ])->save();
        }

        $hoaDon = HoaDon::create([
            'ma_hoa_don' => $this->generateInvoiceCode('TQ'),
            'id_khach_hang' => $khachHang->id_khach_hang,
            'id_nhan_vien' => $session->id_nhan_vien,
            'kenh_ban' => 'tai_quay',
            'trang_thai_xu_ly' => 'hoan_thanh',
            'tong_tien' => $session->tong_tien,
            'giam_gia' => $session->giam_gia_diem,
            'giam_gia_ma' => 0,
            'giam_gia_diem' => $session->giam_gia_diem,
            'thue_vat' => $session->thue_vat,
            'tien_thanh_toan' => $session->tien_thanh_toan,
            'diem_da_su_dung' => $session->diem_da_su_dung,
            'diem_da_cong' => $session->diem_da_cong,
            'diem_thuong_da_xu_ly' => $session->is_registered_customer,
            'ngay_ban' => $paidAt ?: now(),
        ]);

        foreach ($session->detail_rows ?: [] as $detailRow) {
            $loThuoc = LoThuoc::query()
                ->whereKey((int) ($detailRow['id_lo'] ?? 0))
                ->lockForUpdate()
                ->first();
            $soLuong = (int) ($detailRow['so_luong'] ?? 0);

            if (! $loThuoc || $soLuong <= 0 || (int) $loThuoc->so_luong_con < $soLuong) {
                throw ValidationException::withMessages([
                    'items' => ['Không đủ tồn kho để hoàn tất thanh toán PayOS tại quầy.'],
                ]);
            }

            $loThuoc->so_luong_con = max((int) $loThuoc->so_luong_con - $soLuong, 0);
            $loThuoc->save();

            ChiTietHoaDon::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'id_lo' => $loThuoc->id_lo,
                'don_vi_ban' => $detailRow['don_vi_ban'],
                'he_so_quy_doi_ban' => $detailRow['he_so_quy_doi_ban'],
                'so_luong' => $soLuong,
                'gia_ban' => $detailRow['gia_ban'],
                'thanh_tien' => $detailRow['thanh_tien'],
            ]);
        }

        ThanhToan::create([
            'id_hoa_don' => $hoaDon->id_hoa_don,
            'phuong_thuc' => 'payos',
            'so_tien' => $session->tien_thanh_toan,
            'thoi_gian' => $paidAt ?: now(),
            'ma_giao_dich' => $data['reference'] ?? $data['transactionReference'] ?? $this->generateTransactionCode(),
            'trang_thai' => 'paid',
            'payos_order_code' => $session->payos_order_code,
            'payos_payment_link_id' => $data['paymentLinkId'] ?? $session->payos_payment_link_id,
            'payos_checkout_url' => $session->payos_checkout_url,
            'payos_qr_code' => $session->payos_qr_code,
            'payos_payload' => $data ?: $payload,
            'payos_paid_at' => $paidAt ?: now(),
        ]);

        LichSuDonHang::create([
            'id_hoa_don' => $hoaDon->id_hoa_don,
            'trang_thai' => 'Hoàn thành tại quầy',
            'ghi_chu' => $session->ghi_chu ?: 'PayOS đã xác nhận thanh toán tại quầy.',
            'thoi_gian' => $paidAt ?: now(),
            'id_nhan_vien' => $session->id_nhan_vien,
        ]);

        $session->forceFill([
            'status' => 'paid',
            'id_hoa_don' => $hoaDon->id_hoa_don,
            'payos_payment_link_id' => $data['paymentLinkId'] ?? $session->payos_payment_link_id,
            'payos_payload' => $data ?: $payload,
            'payos_paid_at' => $paidAt ?: now(),
            'error_message' => null,
        ])->save();
    }

    private function buildSaleDetails(array $items, bool $deductStock = true): array
    {
        $requestedItems = collect($items)
            ->map(function (array $item): array {
                $selectedUnit = trim((string) ($item['don_vi'] ?? ''));

                return [
                    'ma_thuoc' => (string) $item['ma_thuoc'],
                    'so_luong' => (int) $item['so_luong'],
                    'don_vi' => $selectedUnit !== '' ? $selectedUnit : null,
                ];
            })
            ->groupBy(fn (array $item): string => $item['ma_thuoc'] . '|' . mb_strtolower((string) ($item['don_vi'] ?? '')))
            ->map(function (Collection $items): array {
                $firstItem = $items->first();

                return [
                    'ma_thuoc' => (string) ($firstItem['ma_thuoc'] ?? ''),
                    'so_luong' => $items->sum(fn (array $item) => (int) $item['so_luong']),
                    'don_vi' => $firstItem['don_vi'] ?? null,
                ];
            })
            ->values();

        $loadedThuocs = [];
        $lockedLotsByThuoc = [];

        KhuyenMai::deleteExpired();

        foreach ($requestedItems->pluck('ma_thuoc')->unique() as $maThuoc) {
            $thuoc = Thuoc::query()
                ->with(['nhaSanXuat', 'khuyenMais' => fn ($query) => $query->dangHoatDong()->latest()])
                ->find($maThuoc);

            if (! $thuoc) {
                throw ValidationException::withMessages([
                    'items' => ["Không tìm thấy thuốc {$maThuoc}."],
                ]);
            }

            $loadedThuocs[$maThuoc] = $thuoc;
            $lockedLotsByThuoc[$maThuoc] = LoThuoc::query()
                ->where('id_thuoc', $maThuoc)
                ->where('so_luong_con', '>', 0)
                ->orderByRaw('CASE WHEN han_su_dung IS NULL THEN 1 ELSE 0 END')
                ->orderBy('han_su_dung')
                ->orderBy('id_lo')
                ->lockForUpdate()
                ->get();
        }

        $requestedItems = $requestedItems
            ->map(function (array $requestedItem) use ($loadedThuocs): array {
                $thuoc = $loadedThuocs[$requestedItem['ma_thuoc']];
                $selectedUnit = $this->resolveSelectedUnit($thuoc, $requestedItem['don_vi'] ?? null);
                $selectedUnitFactor = $this->resolveUnitFactor($thuoc, $selectedUnit);

                return [
                    ...$requestedItem,
                    'don_vi' => $selectedUnit,
                    'he_so_quy_doi_ban' => $selectedUnitFactor,
                    'so_luong_quy_doi' => (int) $requestedItem['so_luong'] * $selectedUnitFactor,
                ];
            })
            ->values();

        foreach ($requestedItems->groupBy('ma_thuoc') as $maThuoc => $group) {
            $thuoc = $loadedThuocs[$maThuoc];
            $tongSoLuongDat = $group->sum(fn (array $item): int => (int) ($item['so_luong_quy_doi'] ?? 0));
            $tonKhaDung = (int) $lockedLotsByThuoc[$maThuoc]->sum('so_luong_con');

            if ($tonKhaDung < $tongSoLuongDat) {
                throw ValidationException::withMessages([
                    'items' => ["Thuốc {$thuoc->ten_thuoc} chỉ còn {$tonKhaDung} {$thuoc->baseUnitName()} trong kho."],
                ]);
            }
        }

        $tongTien = 0;
        $detailRows = [];
        $summaryItems = [];

        foreach ($requestedItems as $requestedItem) {
            $maThuoc = $requestedItem['ma_thuoc'];
            $thuoc = $loadedThuocs[$maThuoc];
            $loThuocs = $lockedLotsByThuoc[$maThuoc];
            $soLuongDat = (int) $requestedItem['so_luong'];
            $selectedUnit = (string) $requestedItem['don_vi'];
            $selectedUnitFactor = (int) $requestedItem['he_so_quy_doi_ban'];
            $soLuongDatQuyDoi = (int) $requestedItem['so_luong_quy_doi'];
            $giaGoc = $this->resolveUnitBasePrice($thuoc, $selectedUnit);
            $khuyenMai = $this->resolveActivePromotion($thuoc);
            $giaBan = $khuyenMai ? $khuyenMai->tinhGiaSauGiam($giaGoc) : $giaGoc;
            $thanhTienThuoc = $giaBan * $soLuongDat;

            $tongTien += $thanhTienThuoc;
            $summaryItems[] = [
                'ma_thuoc' => $thuoc->ma_thuoc,
                'ten' => $thuoc->ten_thuoc,
                'don_vi' => $selectedUnit,
                'so_luong' => $soLuongDat,
                'gia_ban' => $giaBan,
                'thanh_tien' => $thanhTienThuoc,
                'hinh_anh_url' => $thuoc->hinh_anh_url,
            ];

            $soLuongCanTru = $soLuongDatQuyDoi;
            $thanhTienConLai = (float) $thanhTienThuoc;

            foreach ($loThuocs as $loThuoc) {
                if ($soLuongCanTru <= 0) {
                    break;
                }

                $soLuongLay = min($soLuongCanTru, (int) $loThuoc->so_luong_con);

                if ($soLuongLay <= 0) {
                    continue;
                }

                $loThuoc->so_luong_con = max((int) $loThuoc->so_luong_con - $soLuongLay, 0);

                if ($deductStock) {
                    $loThuoc->save();
                }

                $thanhTienTheoLo = $soLuongCanTru === $soLuongLay
                    ? $thanhTienConLai
                    : round(($soLuongLay / max($selectedUnitFactor, 1)) * $giaBan, 2);

                $detailRows[] = [
                    'id_lo' => $loThuoc->id_lo,
                    'don_vi_ban' => $selectedUnit,
                    'he_so_quy_doi_ban' => $selectedUnitFactor,
                    'so_luong' => $soLuongLay,
                    'gia_ban' => $giaBan,
                    'thanh_tien' => $thanhTienTheoLo,
                ];

                $soLuongCanTru -= $soLuongLay;
                $thanhTienConLai = round($thanhTienConLai - $thanhTienTheoLo, 2);
            }
        }

        return [$tongTien, $detailRows, $summaryItems];
    }

    private function hasActiveCounterSession(NhanVien $nhanVien, Request $request): bool
    {
        $token = $request->user()?->currentAccessToken();
        $tokenId = $token && method_exists($token, 'getKey') ? $token->getKey() : null;

        return NhanVienDangNhapLog::query()
            ->where('id_nhan_vien', $nhanVien->id_nhan_vien)
            ->where('kenh_dang_nhap', 'tai_quay')
            ->whereNull('thoi_gian_dang_xuat')
            ->when($tokenId, fn ($query) => $query->where('token_id', $tokenId))
            ->exists();
    }

    private function resolveWalkInCustomer(): KhachHang
    {
        $email = 'khachvanglai@pharmago.local';
        $khachHang = KhachHang::query()->where('email', $email)->lockForUpdate()->first();

        if ($khachHang) {
            return $khachHang;
        }

        return KhachHang::create([
            'ten_khach_hang' => 'Khách vãng lai',
            'so_dien_thoai' => $this->generateWalkInPhone(),
            'dia_chi' => 'Bán trực tiếp tại quầy',
            'diem_tich_luy' => 0,
            'mat_khau' => Hash::make(Str::random(32)),
            'email' => $email,
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function generateWalkInPhone(): string
    {
        for ($number = 1; $number <= 9999; $number++) {
            $phone = '9999' . str_pad((string) $number, 6, '0', STR_PAD_LEFT);

            if (! KhachHang::query()->where('so_dien_thoai', $phone)->exists()) {
                return $phone;
            }
        }

        return '9999' . random_int(100000, 999999);
    }

    private function resolveSaleCustomer(?string $customerToken): array
    {
        if (! $customerToken) {
            return [$this->resolveWalkInCustomer(), false];
        }

        $payload = $this->decodeCounterCustomerToken($customerToken);
        $issuedAt = Carbon::createFromTimestamp((int) ($payload['issued_at'] ?? 0));

        if ($issuedAt->lt(now()->subMinutes(self::COUNTER_CUSTOMER_TOKEN_MINUTES))) {
            throw ValidationException::withMessages([
                'customer_token' => ['Phiên khách hàng tại quầy đã hết hạn. Vui lòng đăng nhập khách hàng lại.'],
            ]);
        }

        $khachHang = KhachHang::query()
            ->whereKey((int) ($payload['id_khach_hang'] ?? 0))
            ->lockForUpdate()
            ->first();

        if (! $khachHang) {
            throw ValidationException::withMessages([
                'customer_token' => ['Không tìm thấy khách hàng đã chọn.'],
            ]);
        }

        return [$khachHang, true];
    }

    private function createCounterCustomerToken(KhachHang $khachHang): string
    {
        return Crypt::encryptString(json_encode([
            'id_khach_hang' => $khachHang->id_khach_hang,
            'issued_at' => now()->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    private function decodeCounterCustomerToken(string $customerToken): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($customerToken), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            throw ValidationException::withMessages([
                'customer_token' => ['Phiên khách hàng tại quầy không hợp lệ.'],
            ]);
        }

        return is_array($payload) ? $payload : [];
    }

    private function counterCustomerPayload(KhachHang $khachHang): array
    {
        return [
            'id_khach_hang' => $khachHang->id_khach_hang,
            'ten_khach_hang' => $khachHang->ten_khach_hang,
            'so_dien_thoai' => $khachHang->so_dien_thoai,
            'email' => $khachHang->email,
            'diem_tich_luy' => (int) $khachHang->diem_tich_luy,
        ];
    }

    private function formatCounterPayosSession(CounterSalePayosSession $session): array
    {
        $session->loadMissing(['khachHang', 'hoaDon']);

        return [
            'session_key' => $session->session_key,
            'status' => $session->status,
            'id_hoa_don' => $session->id_hoa_don,
            'hoa_don' => $session->hoaDon ? [
                'id_hoa_don' => $session->hoaDon->id_hoa_don,
                'ma_hoa_don' => $session->hoaDon->ma_hoa_don,
                'kenh_ban' => $session->hoaDon->kenh_ban,
                'trang_thai_xu_ly' => $session->hoaDon->trang_thai_xu_ly,
                'tien_thanh_toan' => $this->formatMoneyValue($session->hoaDon->tien_thanh_toan),
            ] : null,
            'items' => $session->summary_items ?: [],
            'tong_tien' => $this->formatMoneyValue($session->tong_tien),
            'giam_gia_diem' => $this->formatMoneyValue($session->giam_gia_diem),
            'thue_vat' => $this->formatMoneyValue($session->thue_vat),
            'tien_thanh_toan' => $this->formatMoneyValue($session->tien_thanh_toan),
            'diem_da_su_dung' => (int) $session->diem_da_su_dung,
            'diem_da_cong' => (int) $session->diem_da_cong,
            'khach_hang_tich_diem' => $session->is_registered_customer && $session->khachHang
                ? $this->counterCustomerPayload($session->khachHang)
                : null,
            'payos' => [
                'order_code' => $session->payos_order_code ? (int) $session->payos_order_code : null,
                'payment_link_id' => $session->payos_payment_link_id,
                'checkout_url' => $session->payos_checkout_url,
                'qr_code' => $session->payos_qr_code,
                'status' => $session->payos_payload['data']['status'] ?? null,
            ],
            'error_message' => $session->error_message,
        ];
    }

    private function formatMoneyValue(mixed $value): int
    {
        return (int) round((float) $value);
    }

    private function resolveRewardPointDiscount(bool $wantsToUsePoints, int|float $amount, KhachHang $khachHang): array
    {
        if (! $wantsToUsePoints || $amount <= 0) {
            return [0, 0];
        }

        $availablePointBlocks = intdiv((int) $khachHang->diem_tich_luy, self::REWARD_REDEEM_POINTS);
        $orderValueBlocks = intdiv((int) floor($amount), self::REWARD_REDEEM_VALUE);
        $blocksToUse = min($availablePointBlocks, $orderValueBlocks);

        if ($blocksToUse <= 0) {
            return [0, 0];
        }

        return [
            $blocksToUse * self::REWARD_REDEEM_VALUE,
            $blocksToUse * self::REWARD_REDEEM_POINTS,
        ];
    }

    private function calculateRewardPointsEarned(int|float $productAmount): int
    {
        return intdiv((int) floor(max((float) $productAmount, 0)), self::REWARD_VND_PER_POINT);
    }

    private function resolveSelectedUnit(Thuoc $thuoc, mixed $requestedUnit): string
    {
        $matchedUnit = $thuoc->findUnitOption(is_string($requestedUnit) ? $requestedUnit : null);

        if ($matchedUnit) {
            return (string) ($matchedUnit['ten_don_vi'] ?? $thuoc->don_vi_tinh);
        }

        $normalizedRequestedUnit = trim((string) $requestedUnit);

        throw ValidationException::withMessages([
            'items' => ["Đơn vị {$normalizedRequestedUnit} không hợp lệ cho thuốc {$thuoc->ten_thuoc}."],
        ]);
    }

    private function resolveUnitFactor(Thuoc $thuoc, string $selectedUnit): int
    {
        $matchedUnit = $thuoc->findUnitOption($selectedUnit);

        if (! $matchedUnit) {
            throw ValidationException::withMessages([
                'items' => ["Không tìm thấy hệ số quy đổi cho đơn vị {$selectedUnit} của thuốc {$thuoc->ten_thuoc}."],
            ]);
        }

        return max(1, (int) ($matchedUnit['so_luong_quy_doi'] ?? 1));
    }

    private function resolveUnitBasePrice(Thuoc $thuoc, string $selectedUnit): int
    {
        $matchedUnit = $thuoc->findUnitOption($selectedUnit);

        if (! $matchedUnit || ! is_numeric($matchedUnit['gia_ban'] ?? null) || (int) $matchedUnit['gia_ban'] < 1) {
            throw ValidationException::withMessages([
                'items' => ["Giá bán cho đơn vị {$selectedUnit} của thuốc {$thuoc->ten_thuoc} chưa hợp lệ."],
            ]);
        }

        return (int) $matchedUnit['gia_ban'];
    }

    private function resolveActivePromotion(Thuoc $thuoc): ?KhuyenMai
    {
        return $thuoc->khuyenMais
            ->first(fn (KhuyenMai $item) => $item->isDangHoatDong());
    }

    private function calculateVatAmount(int|float $amount): float
    {
        return round(max((float) $amount, 0) * 0.1, 2);
    }

    private function generateInvoiceCode(string $prefix = 'HD'): string
    {
        do {
            $code = $prefix . Carbon::now()->format('YmdHis') . random_int(10, 99);
        } while (HoaDon::query()->where('ma_hoa_don', $code)->exists());

        return $code;
    }

    private function generateTransactionCode(): string
    {
        do {
            $code = 'POS' . Carbon::now()->format('YmdHis') . random_int(100, 999);
        } while (ThanhToan::query()->where('ma_giao_dich', $code)->exists());

        return $code;
    }

    private function generateCounterPayosOrderCode(CounterSalePayosSession $session): int
    {
        return 900000000000 + (int) $session->id;
    }
}
