<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerCheckoutRequest;
use App\Mail\OrderConfirmedMail;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\KhuyenMai;
use App\Models\LichSuDonHang;
use App\Models\LoThuoc;
use App\Models\MaGiamGia;
use App\Models\MaGiamGiaLuotDung;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use App\Models\ThanhToan;
use App\Models\Thuoc;
use App\Services\PayosService;
use App\Services\RewardPointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    private const REWARD_VND_PER_POINT = 1000;
    private const REWARD_REDEEM_POINTS = 1000;
    private const REWARD_REDEEM_VALUE = 10000;

    public function index(Request $request): JsonResponse
    {
        $khachHang = $request->user();

        if (! $khachHang instanceof KhachHang) {
            return response()->json([
                'message' => 'Chi khach hang moi co the xem lich su don hang.',
            ], 403);
        }

        $hoaDons = HoaDon::query()
            ->where('id_khach_hang', $khachHang->id_khach_hang)
            ->whereHas('chiTiets')
            ->with($this->customerOrderRelations())
            ->orderByDesc('ngay_ban')
            ->orderByDesc('id_hoa_don')
            ->get();

        return response()->json([
            'message' => 'Lay lich su don hang thanh cong.',
            'data' => $hoaDons->map(fn (HoaDon $hoaDon) => $this->transformCustomerOrder($hoaDon))->values(),
        ]);
    }

    public function store(StoreCustomerCheckoutRequest $request, PayosService $payos): JsonResponse
    {
        $khachHang = $request->user();

        if (! $khachHang instanceof KhachHang) {
            return response()->json([
                'message' => 'Chi khach hang moi co the dat hang truc tuyen.',
            ], 403);
        }

        $validated = $request->validated();
        $nhanVienXuLy = $this->resolveProcessingEmployee();

        if (($validated['phuong_thuc_thanh_toan'] ?? null) === 'payos' && ! $payos->isConfigured()) {
            return response()->json([
                'message' => 'Chưa cấu hình PayOS. Vui lòng điền PAYOS_CLIENT_ID, PAYOS_API_KEY và PAYOS_CHECKSUM_KEY trong .env.',
            ], 422);
        }

        if (! $nhanVienXuLy) {
            return response()->json([
                'message' => 'He thong chua co nhan vien xu ly don hang.',
            ], 422);
        }

        $checkoutResult = DB::transaction(function () use ($validated, $khachHang, $nhanVienXuLy, $payos) {
            $lockedKhachHang = KhachHang::query()
                ->whereKey($khachHang->id_khach_hang)
                ->lockForUpdate()
                ->firstOrFail();

            $requestedItems = collect($validated['items'])
                ->map(function (array $item): array {
                    $selectedUnit = trim((string) ($item['don_vi'] ?? ''));

                    return [
                        'ma_thuoc' => (string) $item['ma_thuoc'],
                        'so_luong' => (int) $item['so_luong'],
                        'don_vi' => $selectedUnit !== '' ? $selectedUnit : null,
                    ];
                })
                ->groupBy(function (array $item): string {
                    return $item['ma_thuoc'] . '|' . mb_strtolower((string) ($item['don_vi'] ?? ''));
                })
                ->map(function (Collection $items): array {
                    $firstItem = $items->first();

                    return [
                        'ma_thuoc' => (string) ($firstItem['ma_thuoc'] ?? ''),
                        'so_luong' => $items->sum(fn (array $item) => (int) $item['so_luong']),
                        'don_vi' => $firstItem['don_vi'] ?? null,
                    ];
                })
                ->values();

            $tongTien = 0;
            $detailRows = [];
            $summaryItems = [];
            $loadedThuocs = [];
            $lockedLotsByThuoc = [];

            foreach ($requestedItems->pluck('ma_thuoc')->unique() as $maThuoc) {
                $thuoc = Thuoc::query()
                    ->with([
                        'nhaSanXuat',
                        'khuyenMais' => fn ($query) => $query->latest(),
                    ])
                    ->find($maThuoc);

                if (! $thuoc) {
                    throw ValidationException::withMessages([
                        'items' => ["Khong tim thay thuoc {$maThuoc}."],
                    ]);
                }

                $loThuocs = LoThuoc::query()
                    ->where('id_thuoc', $maThuoc)
                    ->where('so_luong_con', '>', 0)
                    ->orderByRaw('CASE WHEN han_su_dung IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('han_su_dung')
                    ->orderBy('id_lo')
                    ->lockForUpdate()
                    ->get();

                $loadedThuocs[$maThuoc] = $thuoc;
                $lockedLotsByThuoc[$maThuoc] = $loThuocs;
            }

            $requestedItems = $requestedItems
                ->map(function (array $requestedItem) use ($loadedThuocs): array {
                    /** @var \App\Models\Thuoc $thuoc */
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

            $requestedQuantityByThuoc = $requestedItems
                ->groupBy('ma_thuoc')
                ->map(fn (Collection $items) => $items->sum(fn (array $item) => (int) ($item['so_luong_quy_doi'] ?? 0)));

            foreach ($requestedQuantityByThuoc as $maThuoc => $tongSoLuongDat) {
                /** @var \Illuminate\Support\Collection<int, \App\Models\LoThuoc> $loThuocs */
                $loThuocs = $lockedLotsByThuoc[$maThuoc];
                /** @var \App\Models\Thuoc $thuoc */
                $thuoc = $loadedThuocs[$maThuoc];
                $tonKhaDung = (int) $loThuocs->sum('so_luong_con');

                if ($tonKhaDung < $tongSoLuongDat) {
                    throw ValidationException::withMessages([
                        'items' => ["Thuoc {$thuoc->ten_thuoc} chi con {$tonKhaDung} {$thuoc->baseUnitName()} trong kho."],
                    ]);
                }
            }

            foreach ($requestedItems as $requestedItem) {
                $maThuoc = $requestedItem['ma_thuoc'];
                $soLuongDat = (int) $requestedItem['so_luong'];
                /** @var \App\Models\Thuoc $thuoc */
                $thuoc = $loadedThuocs[$maThuoc];
                /** @var \Illuminate\Support\Collection<int, \App\Models\LoThuoc> $loThuocs */
                $loThuocs = $lockedLotsByThuoc[$maThuoc];
                $tonKhaDung = (int) $loThuocs->sum('so_luong_con');
                $selectedUnit = (string) $requestedItem['don_vi'];
                $selectedUnitFactor = (int) ($requestedItem['he_so_quy_doi_ban'] ?? 1);
                $soLuongDatQuyDoi = (int) ($requestedItem['so_luong_quy_doi'] ?? ($soLuongDat * $selectedUnitFactor));

                $giaGoc = $this->resolveUnitBasePrice($thuoc, $selectedUnit);
                $khuyenMai = $this->resolveActivePromotion($thuoc);
                $giaBan = $khuyenMai ? $khuyenMai->tinhGiaSauGiam($giaGoc) : $giaGoc;
                $thanhTienThuoc = $giaBan * $soLuongDat;

                $tongTien += $thanhTienThuoc;
                $summaryItems[] = [
                    'id' => $thuoc->ma_thuoc . '::' . mb_strtolower($selectedUnit),
                    'maThuoc' => $thuoc->ma_thuoc,
                    'ten' => $thuoc->ten_thuoc,
                    'donVi' => $selectedUnit,
                    'gia' => $giaBan,
                    'giaGoc' => $giaGoc,
                    'soLuong' => $soLuongDat,
                    'loai' => $this->resolveThuocNhanText($thuoc),
                    'moTa' => $this->buildDescription($thuoc),
                    'imageTone' => 'pink',
                    'nhaSanXuat' => $thuoc->nhaSanXuat?->ten_nha_san_xuat,
                    'tonKho' => (int) floor(max($tonKhaDung - $soLuongDatQuyDoi, 0) / max($selectedUnitFactor, 1)),
                    'promoTags' => $khuyenMai
                        ? array_values(array_filter([$khuyenMai->nhan_hien_thi ?: $khuyenMai->ten_khuyen_mai]))
                        : [],
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
                    $loThuoc->save();

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

            [$maGiamGia, $giamGiaMa, $usageRecord] = $this->resolveOrderDiscount(
                $validated['ma_giam_gia'] ?? null,
                $tongTien,
                $lockedKhachHang
            );

            $tienSauMa = max($tongTien - $giamGiaMa, 0);
            [$giamGiaDiem, $diemDaSuDung] = $this->resolveRewardPointDiscount(
                (bool) ($validated['su_dung_diem'] ?? false),
                $tienSauMa,
                $lockedKhachHang
            );

            $tongGiamGia = $giamGiaMa + $giamGiaDiem;
            $tienSauGiam = max($tongTien - $tongGiamGia, 0);
            $thueVat = $this->calculateVatAmount($tienSauGiam);
            $tienThanhToan = $tienSauGiam + $thueVat;
            $diemDaCong = $this->calculateRewardPointsEarned($tongTien);

            $hoaDon = HoaDon::create([
                'ma_hoa_don' => $this->generateInvoiceCode(),
                'id_khach_hang' => $lockedKhachHang->id_khach_hang,
                'id_nhan_vien' => $nhanVienXuLy->id_nhan_vien,
                'ma_giam_gia_id' => $maGiamGia?->id,
                'kenh_ban' => 'he_thong',
                'trang_thai_xu_ly' => 'cho_xac_nhan',
                'tong_tien' => $tongTien,
                'giam_gia' => $tongGiamGia,
                'giam_gia_ma' => $giamGiaMa,
                'giam_gia_diem' => $giamGiaDiem,
                'thue_vat' => $thueVat,
                'tien_thanh_toan' => $tienThanhToan,
                'diem_da_su_dung' => $diemDaSuDung,
                'diem_da_cong' => $diemDaCong,
                'diem_thuong_da_xu_ly' => false,
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

            $paymentMethod = (string) $validated['phuong_thuc_thanh_toan'];
            $thanhToan = ThanhToan::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'phuong_thuc' => $this->mapPaymentMethod($paymentMethod),
                'so_tien' => $hoaDon->tien_thanh_toan,
                'thoi_gian' => now(),
                'ma_giao_dich' => $this->requiresTransactionCode($paymentMethod)
                    ? $this->generateTransactionCode()
                    : null,
                'trang_thai' => $paymentMethod === 'payos' ? 'pending' : 'paid',
            ]);

            if ($paymentMethod === 'payos') {
                try {
                    $payosLink = $payos->createPaymentLink($hoaDon, array_map(fn (array $item): array => [
                        'name' => $item['ten'] ?? 'San pham',
                        'quantity' => (int) ($item['soLuong'] ?? 1),
                        'price' => (int) ($item['gia'] ?? 0),
                    ], $summaryItems));
                } catch (\RuntimeException $exception) {
                    throw ValidationException::withMessages([
                        'payos' => [$exception->getMessage()],
                    ]);
                }

                $thanhToan->forceFill([
                    'payos_order_code' => $payosLink['order_code'],
                    'payos_payment_link_id' => $payosLink['payment_link_id'],
                    'payos_checkout_url' => $payosLink['checkout_url'],
                    'payos_qr_code' => $payosLink['qr_code'],
                    'payos_payload' => $payosLink['raw'],
                ])->save();
            }

            $orderNote = $this->buildOrderNote(
                $validated['dia_chi_giao_hang'] ?? null,
                $validated['ghi_chu'] ?? null
            );

            LichSuDonHang::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'trang_thai' => 'Chờ xác nhận',
                'ghi_chu' => $orderNote,
                'thoi_gian' => now(),
                'id_nhan_vien' => $nhanVienXuLy->id_nhan_vien,
            ]);

            if ($maGiamGia) {
                if ($usageRecord) {
                    $usageRecord->increment('so_lan_su_dung');
                    $usageRecord->update(['lan_su_dung_cuoi' => now()]);
                } else {
                    MaGiamGiaLuotDung::create([
                        'ma_giam_gia_id' => $maGiamGia->id,
                        'id_khach_hang' => $lockedKhachHang->id_khach_hang,
                        'so_lan_su_dung' => 1,
                        'lan_su_dung_cuoi' => now(),
                    ]);
                }
            }

            return [
                'hoa_don' => $hoaDon,
                'items' => $summaryItems,
            ];
        });

        /** @var \App\Models\HoaDon $invoice */
        $invoice = $checkoutResult['hoa_don'];

        $invoice->load($this->customerOrderRelations());

        return response()->json([
            'message' => 'Đặt hàng thành công. Đơn hàng đang chờ nhân viên xác nhận.',
            'data' => $this->transformCustomerOrder($invoice),
        ], 201);
    }

    public function cancelPayos(Request $request, RewardPointService $rewardPoints): JsonResponse
    {
        $khachHang = $request->user();

        if (! $khachHang instanceof KhachHang) {
            return response()->json([
                'message' => 'Bạn cần đăng nhập tài khoản khách hàng để hủy thanh toán PayOS.',
            ], 403);
        }

        $validated = $request->validate([
            'order_code' => ['required', 'integer', 'min:1'],
            'payment_link_id' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
        ], [
            'order_code.required' => 'Thiếu mã đơn hàng PayOS.',
        ]);

        $maHoaDon = DB::transaction(function () use ($khachHang, $validated, $rewardPoints): string {
            $orderCode = (int) $validated['order_code'];
            $paymentLinkId = trim((string) ($validated['payment_link_id'] ?? ''));

            $hoaDon = HoaDon::query()
                ->with($this->customerOrderRelations())
                ->where('id_khach_hang', $khachHang->id_khach_hang)
                ->where(function ($query) use ($orderCode, $paymentLinkId): void {
                    $query->where('id_hoa_don', $orderCode)
                        ->orWhereHas('thanhToan', function ($paymentQuery) use ($orderCode, $paymentLinkId): void {
                            $paymentQuery->where('payos_order_code', $orderCode);

                            if ($paymentLinkId !== '') {
                                $paymentQuery->orWhere('payos_payment_link_id', $paymentLinkId);
                            }
                        });
                })
                ->lockForUpdate()
                ->first();

            if (! $hoaDon) {
                throw ValidationException::withMessages([
                    'order_code' => ['Không tìm thấy đơn hàng PayOS cần hủy.'],
                ]);
            }

            if ($hoaDon->thanhToan?->phuong_thuc !== 'payos') {
                throw ValidationException::withMessages([
                    'order_code' => ['Chỉ có thể hủy đơn thanh toán PayOS bằng thao tác này.'],
                ]);
            }

            if ($hoaDon->thanhToan?->trang_thai === 'paid') {
                throw ValidationException::withMessages([
                    'order_code' => ['Đơn hàng này đã thanh toán nên không thể hủy PayOS.'],
                ]);
            }

            $maHoaDon = (string) $hoaDon->ma_hoa_don;

            $this->restoreReservedInventory($hoaDon);
            $rewardPoints->restore($hoaDon);
            $this->restoreCouponUsage($hoaDon);
            $hoaDon->delete();

            return $maHoaDon;
        });

        return response()->json([
            'message' => "Đơn hàng {$maHoaDon} đã bị hủy.",
            'data' => null,
        ]);
    }

    private function customerOrderRelations(): array
    {
        return [
            'khachHang:id_khach_hang,ten_khach_hang,so_dien_thoai,email,dia_chi,diem_tich_luy',
            'maGiamGia:id,ma_giam_gia,ten_ma,loai_ap_dung,gia_tri',
            'thanhToan:id_hoa_don,phuong_thuc,so_tien,thoi_gian,ma_giao_dich,trang_thai,payos_order_code,payos_payment_link_id,payos_checkout_url,payos_qr_code,payos_paid_at',
            'latestLichSuDonHang',
            'lichSuDonHangs:id_lich_su,id_hoa_don,trang_thai,ghi_chu,thoi_gian,id_nhan_vien',
            'chiTiets:id,id_hoa_don,id_lo,don_vi_ban,he_so_quy_doi_ban,so_luong,gia_ban,thanh_tien',
            'chiTiets.loThuoc:id_lo,id_thuoc',
            'chiTiets.loThuoc.thuoc:ma_thuoc,ten_thuoc,don_vi_tinh,don_vi_co_so,he_so_quy_doi,quy_cach_don_vi,hinh_anh,nhan',
        ];
    }

    private function resolveProcessingEmployee(): ?NhanVien
    {
        $latestLoggedInEmployee = NhanVienDangNhapLog::query()
            ->with('nhanVien')
            ->where('kenh_dang_nhap', 'he_thong')
            ->whereNull('thoi_gian_dang_xuat')
            ->where('dang_hoat_dong', true)
            ->where(function ($query): void {
                $query->whereNull('het_han_luc')
                    ->orWhere('het_han_luc', '>', now());
            })
            ->whereHas('nhanVien', fn ($query) => $query->where('trang_thai', 'active'))
            ->orderByDesc('thoi_gian_dang_nhap')
            ->orderByDesc('id')
            ->first()
            ?->nhanVien;

        if ($latestLoggedInEmployee instanceof NhanVien) {
            return $latestLoggedInEmployee;
        }

        return NhanVien::query()
            ->where('trang_thai', 'active')
            ->orderByRaw("
                CASE
                    WHEN ten_dang_nhap = 'admin' THEN 0
                    WHEN ten_dang_nhap = 'staff' THEN 1
                    ELSE 2
                END
            ")
            ->orderBy('id_nhan_vien')
            ->first();
    }

    private function resolveActivePromotion(Thuoc $thuoc): ?KhuyenMai
    {
        return $thuoc->khuyenMais
            ->first(fn (KhuyenMai $item) => $item->trang_thai === 'active'
                && $item->ngay_bat_dau?->lte(now())
                && ($item->ngay_ket_thuc === null || $item->ngay_ket_thuc->gte(now())));
    }

    private function resolveSelectedUnit(Thuoc $thuoc, mixed $requestedUnit): string
    {
        $matchedUnit = $thuoc->findUnitOption(is_string($requestedUnit) ? $requestedUnit : null);

        if ($matchedUnit) {
            return (string) ($matchedUnit['ten_don_vi'] ?? $thuoc->don_vi_tinh);
        }

        $normalizedRequestedUnit = trim((string) $requestedUnit);

        throw ValidationException::withMessages([
            'items' => ["Don vi {$normalizedRequestedUnit} khong hop le cho thuoc {$thuoc->ten_thuoc}."],
        ]);
    }

    private function resolveUnitFactor(Thuoc $thuoc, string $selectedUnit): int
    {
        $matchedUnit = $thuoc->findUnitOption($selectedUnit);

        if (! $matchedUnit) {
            throw ValidationException::withMessages([
                'items' => ["Khong tim thay he so quy doi cho don vi {$selectedUnit} cua thuoc {$thuoc->ten_thuoc}."],
            ]);
        }

        return max(1, (int) ($matchedUnit['so_luong_quy_doi'] ?? 1));
    }

    private function resolveUnitBasePrice(Thuoc $thuoc, string $selectedUnit): int
    {
        $matchedUnit = $thuoc->findUnitOption($selectedUnit);

        if (! $matchedUnit || ! is_numeric($matchedUnit['gia_ban'] ?? null) || (int) $matchedUnit['gia_ban'] < 1) {
            throw ValidationException::withMessages([
                'items' => ["Gia ban cho don vi {$selectedUnit} cua thuoc {$thuoc->ten_thuoc} chua hop le."],
            ]);
        }

        return (int) $matchedUnit['gia_ban'];
    }

    private function resolveOrderDiscount(?string $code, int $tongTien, KhachHang $khachHang): array
    {
        if (! filled($code) || $tongTien <= 0) {
            return [null, 0, null];
        }

        $normalizedCode = Str::upper(trim($code));

        $maGiamGia = MaGiamGia::query()
            ->dangHoatDong()
            ->whereRaw('UPPER(ma_giam_gia) = ?', [$normalizedCode])
            ->lockForUpdate()
            ->first();

        if (! $maGiamGia) {
            throw ValidationException::withMessages([
                'ma_giam_gia' => ['Ma giam gia khong hop le hoac da het hieu luc.'],
            ]);
        }

        if (! $this->couponCanBeUsedByCustomer($maGiamGia, $khachHang)) {
            throw ValidationException::withMessages([
                'ma_giam_gia' => ['Ma giam gia khong ap dung cho tai khoan nay.'],
            ]);
        }

        if ($this->isFirstOrderCoupon($maGiamGia) && $this->customerHasCompletedOrder($khachHang)) {
            throw ValidationException::withMessages([
                'ma_giam_gia' => ['Ma don dau tien chi ap dung cho don hang dau tien.'],
            ]);
        }

        if ($tongTien < (int) $maGiamGia->gia_tri_don_toi_thieu) {
            throw ValidationException::withMessages([
                'ma_giam_gia' => ['Don hang chua dat gia tri toi thieu de dung ma nay.'],
            ]);
        }

        $usageRecord = MaGiamGiaLuotDung::query()
            ->where('ma_giam_gia_id', $maGiamGia->id)
            ->where('id_khach_hang', $khachHang->id_khach_hang)
            ->lockForUpdate()
            ->first();

        $soLanDaDung = (int) ($usageRecord?->so_lan_su_dung ?? 0);

        if ($maGiamGia->gioi_han_moi_khach !== null && $soLanDaDung >= (int) $maGiamGia->gioi_han_moi_khach) {
            throw ValidationException::withMessages([
                'ma_giam_gia' => ['Ban da dung het so lan cho phep cua ma giam gia nay.'],
            ]);
        }

        return [
            $maGiamGia,
            min($maGiamGia->tinhTienGiam($tongTien), $tongTien),
            $usageRecord,
        ];
    }

    private function resolveRewardPointDiscount(bool $wantsToUsePoints, int|float $amountAfterCoupon, KhachHang $khachHang): array
    {
        if (! $wantsToUsePoints || $amountAfterCoupon <= 0) {
            return [0, 0];
        }

        $availablePointBlocks = intdiv((int) $khachHang->diem_tich_luy, self::REWARD_REDEEM_POINTS);
        $orderValueBlocks = intdiv((int) floor($amountAfterCoupon), self::REWARD_REDEEM_VALUE);
        $blocksToUse = min($availablePointBlocks, $orderValueBlocks);

        if ($blocksToUse <= 0) {
            return [0, 0];
        }

        return [
            $blocksToUse * self::REWARD_REDEEM_VALUE,
            $blocksToUse * self::REWARD_REDEEM_POINTS,
        ];
    }

    private function calculateRewardPointsEarned(int|float $paidAmount): int
    {
        return intdiv((int) floor(max((float) $paidAmount, 0)), self::REWARD_VND_PER_POINT);
    }

    private function couponCanBeUsedByCustomer(MaGiamGia $maGiamGia, KhachHang $khachHang): bool
    {
        return $maGiamGia->id_khach_hang === null
            || (int) $maGiamGia->id_khach_hang === (int) $khachHang->id_khach_hang;
    }

    private function isFirstOrderCoupon(MaGiamGia $maGiamGia): bool
    {
        return (string) $maGiamGia->loai_ma === 'first_order';
    }

    private function customerHasCompletedOrder(KhachHang $khachHang): bool
    {
        return HoaDon::query()
            ->where('id_khach_hang', $khachHang->id_khach_hang)
            ->whereIn('trang_thai_xu_ly', ['cho_xac_nhan', 'da_xac_nhan', 'hoan_thanh'])
            ->whereHas('chiTiets')
            ->exists();
    }

    private function mapPaymentMethod(string $method): string
    {
        return match ($method) {
            'cod' => 'tien_mat',
            'payos' => 'payos',
            default => 'tien_mat',
        };
    }

    private function mapPaymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cod' => 'Tiền mặt',
            'payos' => 'PayOS',
            default => 'Tiền mặt',
        };
    }

    private function mapStoredPaymentMethodLabel(?string $method): string
    {
        return match ((string) $method) {
            'tien_mat' => 'Tiền mặt',
            'momo' => 'MoMo',
            'zalopay' => 'ZaloPay',
            'the_atm' => 'Thẻ ATM',
            'the_quoc_te' => 'Thẻ quốc tế',
            'payos' => 'PayOS',
            default => 'Tiền mặt',
        };
    }

    private function requiresTransactionCode(string $method): bool
    {
        return ! in_array($method, ['cod', 'payos'], true);
    }

    private function calculateVatAmount(int|float $amountAfterDiscount): float
    {
        return round(max((float) $amountAfterDiscount, 0) * 0.1, 2);
    }

    private function restoreReservedInventory(HoaDon $hoaDon): void
    {
        $hoaDon->loadMissing('chiTiets');

        foreach ($hoaDon->chiTiets as $detail) {
            LoThuoc::query()
                ->whereKey($detail->id_lo)
                ->lockForUpdate()
                ->increment('so_luong_con', (int) $detail->so_luong);
        }
    }

    private function restoreCouponUsage(HoaDon $hoaDon): void
    {
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

    private function generateInvoiceCode(): string
    {
        do {
            $code = 'HD' . Carbon::now()->format('YmdHis') . random_int(10, 99);
        } while (HoaDon::query()->where('ma_hoa_don', $code)->exists());

        return $code;
    }

    private function generateTransactionCode(): string
    {
        do {
            $code = 'PAY' . Carbon::now()->format('YmdHis') . random_int(100, 999);
        } while (ThanhToan::query()->where('ma_giao_dich', $code)->exists());

        return $code;
    }

    private function buildOrderNote(?string $diaChiGiaoHang, ?string $ghiChu, ?string $systemNote = null): ?string
    {
        $parts = array_filter([
            filled($systemNote) ? trim($systemNote) : null,
            filled($diaChiGiaoHang) ? 'Dia chi giao hang: ' . trim($diaChiGiaoHang) : null,
            filled($ghiChu) ? 'Ghi chu: ' . trim($ghiChu) : null,
        ]);

        return $parts ? implode(PHP_EOL, $parts) : null;
    }

    private function parseOrderNote(?string $note): array
    {
        $parsed = [
            'shipping_address' => null,
            'customer_note' => null,
            'system_note' => null,
        ];

        if (! filled($note)) {
            return $parsed;
        }

        $systemNotes = [];

        foreach (preg_split('/\R/u', (string) $note) ?: [] as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            if (Str::startsWith($line, 'Dia chi giao hang:')) {
                $parsed['shipping_address'] = trim(Str::after($line, 'Dia chi giao hang:'));
                continue;
            }

            if (Str::startsWith($line, 'Ghi chu:')) {
                $parsed['customer_note'] = trim(Str::after($line, 'Ghi chu:'));
                continue;
            }

            $systemNotes[] = $line;
        }

        if ($systemNotes) {
            $parsed['system_note'] = implode(PHP_EOL, $systemNotes);
        }

        return $parsed;
    }

    private function sendOrderConfirmationMail(
        KhachHang $khachHang,
        HoaDon $hoaDon,
        array $items,
        string $paymentMethod,
        ?string $diaChiGiaoHang,
        ?string $ghiChu
    ): void {
        if (! filled($khachHang->email)) {
            return;
        }

        try {
            Mail::to($khachHang->email)->send(new OrderConfirmedMail(
                $khachHang,
                $hoaDon,
                $items,
                [
                    'payment_method_label' => $this->mapPaymentMethodLabel($paymentMethod),
                    'shipping_address' => $diaChiGiaoHang,
                    'note' => $ghiChu,
                ]
            ));
        } catch (\Throwable $exception) {
            Log::warning('Khong the gui mail xac nhan don hang.', [
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'ma_hoa_don' => $hoaDon->ma_hoa_don,
                'id_khach_hang' => $khachHang->id_khach_hang,
                'email' => $khachHang->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function buildDescription(Thuoc $thuoc): string
    {
        $loai = $this->resolveThuocNhanText($thuoc) ?: 'thuoc thong dung';

        return "{$thuoc->ten_thuoc} la san pham thuoc thuoc nhom {$loai}, phu hop cho nhu cau cham soc suc khoe thong thuong va nen dung theo huong dan cua duoc si.";
    }

    private function resolveThuocNhanText(Thuoc $thuoc): string
    {
        $rawNhan = trim((string) ($thuoc->nhan ?? ''));

        if ($rawNhan !== '') {
            $items = collect(preg_split('/[\r\n,;|]+/u', $rawNhan) ?: [])
                ->map(fn (string $item): string => trim(preg_replace('/\s+/u', ' ', $item)))
                ->filter()
                ->values();

            if ($items->isNotEmpty()) {
                return $items->implode(', ');
            }
        }

        return '';
    }

    private function buildOrderTimeline(HoaDon $hoaDon): array
    {
        $events = collect();

        if ($hoaDon->ngay_ban) {
            $events->push([
                'id' => 'order-created',
                'label' => 'Đặt hàng',
                'thoi_gian' => optional($hoaDon->ngay_ban)->toIso8601String(),
                'mo_ta' => 'Đơn hàng đã được tạo thành công.',
            ]);
        }

        if ($hoaDon->thanhToan?->thoi_gian) {
            $events->push([
                'id' => 'payment-recorded',
                'label' => 'Thanh toán',
                'thoi_gian' => optional($hoaDon->thanhToan?->thoi_gian)->toIso8601String(),
                'mo_ta' => 'Phương thức: ' . $this->mapStoredPaymentMethodLabel($hoaDon->thanhToan?->phuong_thuc),
            ]);
        }

        foreach ($hoaDon->lichSuDonHangs->sortBy('thoi_gian') as $history) {
            /** @var \App\Models\LichSuDonHang $history */
            $events->push([
                'id' => 'status-' . $history->id_lich_su,
                'label' => $this->normalizeCustomerStatusLabel($history->trang_thai),
                'thoi_gian' => optional($history->thoi_gian)->toIso8601String(),
                'mo_ta' => $this->parseOrderNote($history->ghi_chu)['system_note'],
            ]);
        }

        return $events
            ->filter(fn (array $event) => filled($event['thoi_gian']))
            ->unique(fn (array $event) => implode('|', [
                (string) ($event['label'] ?? ''),
                (string) ($event['thoi_gian'] ?? ''),
                (string) ($event['mo_ta'] ?? ''),
            ]))
            ->values()
            ->all();
    }

    private function normalizeCustomerStatusLabel(?string $status): string
    {
        return match (Str::lower((string) $status)) {
            'thanh cong', 'hoan thanh' => 'Hoàn thành',
            'dang xu ly', 'cho xu ly' => 'Đang xử lý',
            'dang giao' => 'Đang giao',
            'da huy', 'huy' => 'Đã hủy',
            'that bai' => 'Thất bại',
            default => (string) ($status ?: 'Hoàn thành'),
        };
    }

    private function transformCustomerOrder(HoaDon $hoaDon): array
    {
        $parsedOrderNote = $this->parseOrderNote(
            $hoaDon->lichSuDonHangs
                ->sortByDesc('thoi_gian')
                ->first(fn (LichSuDonHang $history) => filled($history->ghi_chu))
                ?->ghi_chu
        );
        $items = $hoaDon->chiTiets
            ->groupBy(fn (ChiTietHoaDon $item) => ($item->loThuoc?->id_thuoc ?: "detail-{$item->id}") . '|' . mb_strtolower((string) ($item->don_vi_ban ?: $item->loThuoc?->thuoc?->don_vi_tinh ?: '')))
            ->map(function (Collection $group) {
                /** @var \App\Models\ChiTietHoaDon|null $firstItem */
                $firstItem = $group->first();
                $thuoc = $firstItem?->loThuoc?->thuoc;
                $donViBan = $firstItem?->don_vi_ban ?: $thuoc?->don_vi_tinh ?: '';
                $heSoQuyDoiBan = max(
                    1,
                    (int) ($firstItem?->he_so_quy_doi_ban ?: ($thuoc ? $this->resolveUnitFactor($thuoc, $donViBan) : 1))
                );
                $tongSoLuongCoSo = (int) $group->sum('so_luong');

                return [
                    'id' => ($thuoc?->ma_thuoc ?: "detail-{$firstItem?->id}") . '::' . mb_strtolower((string) $donViBan),
                    'ma_thuoc' => $thuoc?->ma_thuoc,
                    'ten' => $thuoc?->ten_thuoc ?: 'San pham',
                    'loai' => $thuoc ? $this->resolveThuocNhanText($thuoc) : '',
                    'don_vi' => $donViBan,
                    'mo_ta' => $thuoc ? $this->buildDescription($thuoc) : 'Thong tin san pham dang duoc cap nhat.',
                    'hinh_anh_url' => $thuoc?->hinh_anh_url,
                    'so_luong' => $this->formatDisplayQuantity($tongSoLuongCoSo / $heSoQuyDoiBan),
                    'gia_ban' => (float) ($firstItem?->gia_ban ?? 0),
                    'thanh_tien' => (float) $group->sum('thanh_tien'),
                ];
            })
            ->values();

        return [
            'id_hoa_don' => $hoaDon->id_hoa_don,
            'ma_hoa_don' => $hoaDon->ma_hoa_don,
            'ngay_ban' => optional($hoaDon->ngay_ban)->toIso8601String(),
            'trang_thai' => $hoaDon->latestLichSuDonHang?->trang_thai ?: 'Thanh cong',
            'kenh_ban' => $hoaDon->kenh_ban,
            'trang_thai_xu_ly' => $hoaDon->trang_thai_xu_ly,
            'ly_do_tu_choi' => $hoaDon->ly_do_tu_choi,
            'thoi_gian_cap_nhat_trang_thai' => optional($hoaDon->latestLichSuDonHang?->thoi_gian)->toIso8601String(),
            'tong_tien' => (float) $hoaDon->tong_tien,
            'giam_gia' => (float) $hoaDon->giam_gia,
            'giam_gia_ma' => (float) ($hoaDon->giam_gia_ma ?? 0),
            'giam_gia_diem' => (float) ($hoaDon->giam_gia_diem ?? 0),
            'thue_vat' => (float) $hoaDon->thue_vat,
            'tien_thanh_toan' => (float) $hoaDon->tien_thanh_toan,
            'diem_da_su_dung' => (int) ($hoaDon->diem_da_su_dung ?? 0),
            'diem_da_cong' => (int) ($hoaDon->diem_da_cong ?? 0),
            'diem_thuong_da_xu_ly' => (bool) $hoaDon->diem_thuong_da_xu_ly,
            'diem_hien_tai' => $hoaDon->khachHang?->diem_tich_luy !== null
                ? (int) $hoaDon->khachHang->diem_tich_luy
                : null,
            'ma_giam_gia' => $hoaDon->maGiamGia?->ma_giam_gia,
            'nguoi_nhan' => $hoaDon->khachHang?->ten_khach_hang,
            'so_dien_thoai_nhan' => $hoaDon->khachHang?->so_dien_thoai,
            'dia_chi_giao_hang' => $parsedOrderNote['shipping_address'] ?: $hoaDon->khachHang?->dia_chi,
            'ghi_chu' => $parsedOrderNote['customer_note'],
            'ghi_chu_he_thong' => $parsedOrderNote['system_note'],
            'phuong_thuc_thanh_toan' => $hoaDon->thanhToan?->phuong_thuc,
            'phuong_thuc_thanh_toan_label' => $this->mapStoredPaymentMethodLabel($hoaDon->thanhToan?->phuong_thuc),
            'trang_thai_thanh_toan' => $hoaDon->thanhToan?->trang_thai,
            'ma_giao_dich' => $hoaDon->thanhToan?->ma_giao_dich,
            'thoi_gian_thanh_toan' => optional($hoaDon->thanhToan?->thoi_gian)->toIso8601String(),
            'payos' => $hoaDon->thanhToan?->phuong_thuc === 'payos'
                ? [
                    'order_code' => $hoaDon->thanhToan?->payos_order_code,
                    'payment_link_id' => $hoaDon->thanhToan?->payos_payment_link_id,
                    'checkout_url' => $hoaDon->thanhToan?->payos_checkout_url,
                    'qr_code' => $hoaDon->thanhToan?->payos_qr_code,
                    'status' => $hoaDon->thanhToan?->trang_thai,
                    'paid_at' => optional($hoaDon->thanhToan?->payos_paid_at)->toIso8601String(),
                ]
                : null,
            'tong_so_san_pham' => $this->formatDisplayQuantity((float) $items->sum(fn (array $item) => (float) ($item['so_luong'] ?? 0))),
            'timeline' => $this->buildOrderTimeline($hoaDon),
            'items' => $items,
        ];
    }

    private function formatDisplayQuantity(float $quantity): int|float
    {
        $rounded = round($quantity, 3);

        if (abs($rounded - round($rounded)) < 0.0001) {
            return (int) round($rounded);
        }

        return $rounded;
    }
}
