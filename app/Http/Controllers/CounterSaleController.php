<?php

namespace App\Http\Controllers;

use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\KhuyenMai;
use App\Models\LichSuDonHang;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use App\Models\ThanhToan;
use App\Models\Thuoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CounterSaleController extends Controller
{
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
            'phuong_thuc_thanh_toan' => ['required', 'string', 'in:tien_mat,momo,zalopay,the_atm,the_quoc_te'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => 'Vui lòng chọn ít nhất một sản phẩm để bán tại quầy.',
            'items.*.so_luong.min' => 'Số lượng bán phải lớn hơn 0.',
            'phuong_thuc_thanh_toan.required' => 'Vui lòng chọn phương thức thanh toán.',
        ]);

        $result = DB::transaction(function () use ($validated, $nhanVien): array {
            $khachVangLai = $this->resolveWalkInCustomer();
            [$tongTien, $detailRows, $summaryItems] = $this->buildSaleDetails($validated['items']);
            $thueVat = $this->calculateVatAmount($tongTien);
            $tienThanhToan = $tongTien + $thueVat;

            $hoaDon = HoaDon::create([
                'ma_hoa_don' => $this->generateInvoiceCode('TQ'),
                'id_khach_hang' => $khachVangLai->id_khach_hang,
                'id_nhan_vien' => $nhanVien->id_nhan_vien,
                'kenh_ban' => 'tai_quay',
                'trang_thai_xu_ly' => 'hoan_thanh',
                'tong_tien' => $tongTien,
                'giam_gia' => 0,
                'giam_gia_ma' => 0,
                'giam_gia_diem' => 0,
                'thue_vat' => $thueVat,
                'tien_thanh_toan' => $tienThanhToan,
                'diem_da_su_dung' => 0,
                'diem_da_cong' => 0,
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
            ];
        });

        return response()->json([
            'message' => 'Thanh toán tại quầy thành công.',
            'data' => [
                ...$result['hoa_don']->toArray(),
                'items' => $result['items'],
            ],
        ], 201);
    }

    private function buildSaleDetails(array $items): array
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

        foreach ($requestedItems->pluck('ma_thuoc')->unique() as $maThuoc) {
            $thuoc = Thuoc::query()
                ->with(['nhaSanXuat', 'khuyenMais' => fn ($query) => $query->latest()])
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
            ->first(fn (KhuyenMai $item) => $item->trang_thai === 'active'
                && $item->ngay_bat_dau?->lte(now())
                && ($item->ngay_ket_thuc === null || $item->ngay_ket_thuc->gte(now())));
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
}
