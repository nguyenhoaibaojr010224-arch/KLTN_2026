<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerCheckoutRequest;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\KhuyenMai;
use App\Models\LichSuDonHang;
use App\Models\LoThuoc;
use App\Models\MaGiamGia;
use App\Models\MaGiamGiaLuotDung;
use App\Models\NhanVien;
use App\Models\ThanhToan;
use App\Models\Thuoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $khachHang = $request->user();

        if (! $khachHang instanceof KhachHang) {
            return response()->json([
                'message' => 'Chỉ khách hàng mới có thể xem lịch sử đơn hàng.',
            ], 403);
        }

        $hoaDons = HoaDon::query()
            ->where('id_khach_hang', $khachHang->id_khach_hang)
            ->whereHas('chiTiets')
            ->with([
                'latestLichSuDonHang',
                'chiTiets:id,id_hoa_don,id_lo,so_luong,gia_ban,thanh_tien',
                'chiTiets.loThuoc:id_lo,id_thuoc',
                'chiTiets.loThuoc.thuoc:ma_thuoc,ten_thuoc,don_vi_tinh,id_loai_thuoc',
                'chiTiets.loThuoc.thuoc.loaiThuoc:id,ten_loai',
            ])
            ->orderByDesc('ngay_ban')
            ->orderByDesc('id_hoa_don')
            ->get();

        return response()->json([
            'message' => 'Lấy lịch sử đơn hàng thành công.',
            'data' => $hoaDons->map(fn (HoaDon $hoaDon) => $this->transformCustomerOrder($hoaDon))->values(),
        ]);
    }

    public function store(StoreCustomerCheckoutRequest $request): JsonResponse
    {
        $khachHang = $request->user();

        if (! $khachHang instanceof KhachHang) {
            return response()->json([
                'message' => 'Chỉ khách hàng mới có thể đặt hàng trực tuyến.',
            ], 403);
        }

        $validated = $request->validated();
        $nhanVienXuLy = $this->resolveProcessingEmployee();

        if (! $nhanVienXuLy) {
            return response()->json([
                'message' => 'Hệ thống chưa có nhân viên xử lý đơn hàng.',
            ], 422);
        }

        $hoaDon = DB::transaction(function () use ($validated, $khachHang, $nhanVienXuLy) {
            $requestedItems = collect($validated['items'])
                ->groupBy('ma_thuoc')
                ->map(fn (Collection $items) => $items->sum(fn (array $item) => (int) $item['so_luong']));

            $tongTien = 0;
            $detailRows = [];
            $summaryItems = [];

            foreach ($requestedItems as $maThuoc => $soLuongDat) {
                $thuoc = Thuoc::query()
                    ->with([
                        'loaiThuoc',
                        'nhaSanXuat',
                        'khuyenMais' => fn ($query) => $query->latest(),
                    ])
                    ->find($maThuoc);

                if (! $thuoc) {
                    throw ValidationException::withMessages([
                        'items' => ["Không tìm thấy thuốc {$maThuoc}."],
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

                $tonKhaDung = (int) $loThuocs->sum('so_luong_con');

                if ($tonKhaDung < $soLuongDat) {
                    throw ValidationException::withMessages([
                        'items' => ["Thuốc {$thuoc->ten_thuoc} chỉ còn {$tonKhaDung} sản phẩm trong kho."],
                    ]);
                }

                $giaGoc = (int) round((float) $thuoc->gia_ban);
                $khuyenMai = $this->resolveActivePromotion($thuoc);
                $giaBan = $khuyenMai ? $khuyenMai->tinhGiaSauGiam($giaGoc) : $giaGoc;
                $thanhTienThuoc = $giaBan * $soLuongDat;

                $tongTien += $thanhTienThuoc;
                $summaryItems[] = [
                    'id' => $thuoc->ma_thuoc,
                    'maThuoc' => $thuoc->ma_thuoc,
                    'ten' => $thuoc->ten_thuoc,
                    'donVi' => $thuoc->don_vi_tinh,
                    'gia' => $giaBan,
                    'giaGoc' => $giaGoc,
                    'soLuong' => $soLuongDat,
                    'loai' => $thuoc->loaiThuoc?->ten_loai,
                    'moTa' => $this->buildDescription($thuoc),
                    'imageTone' => 'pink',
                    'nhaSanXuat' => $thuoc->nhaSanXuat?->ten_nha_san_xuat,
                    'tonKho' => max($tonKhaDung - $soLuongDat, 0),
                    'promoTags' => $khuyenMai
                        ? array_values(array_filter([$khuyenMai->nhan_hien_thi ?: $khuyenMai->ten_khuyen_mai]))
                        : [],
                ];

                $soLuongCanTru = $soLuongDat;

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

                    $detailRows[] = [
                        'id_lo' => $loThuoc->id_lo,
                        'so_luong' => $soLuongLay,
                        'gia_ban' => $giaBan,
                        'thanh_tien' => $giaBan * $soLuongLay,
                    ];

                    $soLuongCanTru -= $soLuongLay;
                }
            }

            [$maGiamGia, $giamGia, $usageRecord] = $this->resolveOrderDiscount(
                $validated['ma_giam_gia'] ?? null,
                $tongTien,
                $khachHang
            );

            $hoaDon = HoaDon::create([
                'ma_hoa_don' => $this->generateInvoiceCode(),
                'id_khach_hang' => $khachHang->id_khach_hang,
                'id_nhan_vien' => $nhanVienXuLy->id_nhan_vien,
                'tong_tien' => $tongTien,
                'giam_gia' => $giamGia,
                'tien_thanh_toan' => max($tongTien - $giamGia, 0),
                'ngay_ban' => now(),
            ]);

            foreach ($detailRows as $detailRow) {
                ChiTietHoaDon::create([
                    'id_hoa_don' => $hoaDon->id_hoa_don,
                    'id_lo' => $detailRow['id_lo'],
                    'so_luong' => $detailRow['so_luong'],
                    'gia_ban' => $detailRow['gia_ban'],
                    'thanh_tien' => $detailRow['thanh_tien'],
                ]);
            }

            ThanhToan::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'phuong_thuc' => $this->mapPaymentMethod($validated['phuong_thuc_thanh_toan']),
                'so_tien' => $hoaDon->tien_thanh_toan,
                'thoi_gian' => now(),
                'ma_giao_dich' => $this->requiresTransactionCode($validated['phuong_thuc_thanh_toan'])
                    ? $this->generateTransactionCode()
                    : null,
            ]);

            LichSuDonHang::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'trang_thai' => 'Chờ xác nhận',
                'ghi_chu' => $this->buildOrderNote(
                    $validated['dia_chi_giao_hang'] ?? null,
                    $validated['ghi_chu'] ?? null
                ),
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
                        'id_khach_hang' => $khachHang->id_khach_hang,
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
        $invoice = $hoaDon['hoa_don'];

        return response()->json([
            'message' => 'Đặt hàng thành công.',
            'data' => [
                'id_hoa_don' => $invoice->id_hoa_don,
                'ma_hoa_don' => $invoice->ma_hoa_don,
                'ngay_ban' => optional($invoice->ngay_ban)->toIso8601String(),
                'trang_thai' => 'Chờ xác nhận',
                'tong_tien' => (float) $invoice->tong_tien,
                'giam_gia' => (float) $invoice->giam_gia,
                'tien_thanh_toan' => (float) $invoice->tien_thanh_toan,
                'items' => $hoaDon['items'],
            ],
        ], 201);
    }

    private function resolveProcessingEmployee(): ?NhanVien
    {
        return NhanVien::query()
            ->where('trang_thai', 'active')
            ->orderByRaw("
                CASE
                    WHEN ten_dang_nhap = 'staff' THEN 0
                    WHEN ten_dang_nhap = 'admin' THEN 1
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
                'ma_giam_gia' => ['Mã giảm giá không hợp lệ hoặc đã hết hiệu lực.'],
            ]);
        }

        if ($tongTien < (int) $maGiamGia->gia_tri_don_toi_thieu) {
            throw ValidationException::withMessages([
                'ma_giam_gia' => ['Đơn hàng chưa đạt giá trị tối thiểu để dùng mã này.'],
            ]);
        }

        $usageRecord = MaGiamGiaLuotDung::query()
            ->where('ma_giam_gia_id', $maGiamGia->id)
            ->where('id_khach_hang', $khachHang->id_khach_hang)
            ->lockForUpdate()
            ->first();

        $soLanDaDung = (int) ($usageRecord?->so_lan_su_dung ?? 0);

        if (
            $maGiamGia->gioi_han_moi_khach !== null
            && $soLanDaDung >= (int) $maGiamGia->gioi_han_moi_khach
        ) {
            throw ValidationException::withMessages([
                'ma_giam_gia' => ['Bạn đã dùng hết số lần cho phép của mã giảm giá này.'],
            ]);
        }

        return [
            $maGiamGia,
            min($maGiamGia->tinhTienGiam($tongTien), $tongTien),
            $usageRecord,
        ];
    }

    private function mapPaymentMethod(string $method): string
    {
        return match ($method) {
            'cod' => 'tien_mat',
            'momo' => 'momo',
            'zalopay' => 'zalopay',
            'atm' => 'the_atm',
            'international' => 'the_quoc_te',
            default => 'tien_mat',
        };
    }

    private function requiresTransactionCode(string $method): bool
    {
        return $method !== 'cod';
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

    private function buildOrderNote(?string $diaChiGiaoHang, ?string $ghiChu): ?string
    {
        $parts = array_filter([
            filled($diaChiGiaoHang) ? 'Địa chỉ giao hàng: ' . trim($diaChiGiaoHang) : null,
            filled($ghiChu) ? 'Ghi chú: ' . trim($ghiChu) : null,
        ]);

        return $parts ? implode(PHP_EOL, $parts) : null;
    }

    private function buildDescription(Thuoc $thuoc): string
    {
        $loai = $thuoc->loaiThuoc?->ten_loai ?: 'thuốc thông dụng';

        return "{$thuoc->ten_thuoc} là sản phẩm thuộc nhóm {$loai}, phù hợp cho nhu cầu chăm sóc sức khỏe thông thường và nên dùng theo hướng dẫn của dược sĩ.";
    }

    private function transformCustomerOrder(HoaDon $hoaDon): array
    {
        $items = $hoaDon->chiTiets
            ->groupBy(fn (ChiTietHoaDon $item) => $item->loThuoc?->id_thuoc ?: "detail-{$item->id}")
            ->map(function (Collection $group) {
                /** @var \App\Models\ChiTietHoaDon|null $firstItem */
                $firstItem = $group->first();
                $thuoc = $firstItem?->loThuoc?->thuoc;

                return [
                    'id' => $thuoc?->ma_thuoc ?: "detail-{$firstItem?->id}",
                    'ma_thuoc' => $thuoc?->ma_thuoc,
                    'ten' => $thuoc?->ten_thuoc ?: 'Sản phẩm',
                    'loai' => $thuoc?->loaiThuoc?->ten_loai ?: '',
                    'don_vi' => $thuoc?->don_vi_tinh ?: '',
                    'mo_ta' => $thuoc ? $this->buildDescription($thuoc) : 'Thông tin sản phẩm đang được cập nhật.',
                    'so_luong' => (int) $group->sum('so_luong'),
                    'gia_ban' => (float) ($firstItem?->gia_ban ?? 0),
                    'thanh_tien' => (float) $group->sum('thanh_tien'),
                ];
            })
            ->values();

        return [
            'id_hoa_don' => $hoaDon->id_hoa_don,
            'ma_hoa_don' => $hoaDon->ma_hoa_don,
            'ngay_ban' => optional($hoaDon->ngay_ban)->toIso8601String(),
            'trang_thai' => $hoaDon->latestLichSuDonHang?->trang_thai ?: 'Chờ xác nhận',
            'thoi_gian_cap_nhat_trang_thai' => optional($hoaDon->latestLichSuDonHang?->thoi_gian)->toIso8601String(),
            'tong_tien' => (float) $hoaDon->tong_tien,
            'giam_gia' => (float) $hoaDon->giam_gia,
            'tien_thanh_toan' => (float) $hoaDon->tien_thanh_toan,
            'tong_so_san_pham' => (int) $items->sum('so_luong'),
            'items' => $items,
        ];
    }
}
