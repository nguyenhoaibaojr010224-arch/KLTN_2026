<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateRangeRequest;
use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StorePhieuNhapRequest;
use App\Models\ChiTietPhieuNhap;
use App\Models\LoThuoc;
use App\Models\PhieuNhap;
use App\Models\Thuoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PhieuNhapController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lay danh sach phieu nhap thanh cong.',
            'data' => $this->baseQuery()->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->baseQuery()->find($id);

        if (! $record) {
            return response()->json(['message' => 'Khong tim thay phieu nhap.'], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet phieu nhap thanh cong.',
            'data' => $record,
        ]);
    }

    public function store(StorePhieuNhapRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $record = DB::transaction(function () use ($request, $validated): PhieuNhap {
            $phieuNhap = PhieuNhap::create([
                'ma_phieu_nhap' => $this->generateReceiptCode(),
                'id_nha_san_xuat' => $validated['id_nha_san_xuat'],
                'id_nhan_vien' => $request->user()->id_nhan_vien,
                'so_hoa_don_giay' => $validated['so_hoa_don_giay'] ?? null,
                'ngay_hoa_don' => $validated['ngay_hoa_don'] ?? null,
                'chung_tu_url' => $validated['chung_tu_url'] ?? null,
                'ghi_chu' => $validated['ghi_chu'] ?? null,
                'tong_tien' => 0,
                'ngay_nhap' => $validated['ngay_nhap'] ?? now(),
            ]);

            foreach ($validated['chi_tiets'] ?? [] as $detail) {
                $this->createLotFromReceiptDetail($phieuNhap, $detail);
            }

            $this->recalculateTotal($phieuNhap->id_phieu_nhap);

            return $phieuNhap->refresh();
        });

        $record->load($this->receiptRelations());

        return response()->json([
            'message' => 'Tao phieu nhap thanh cong.',
            'data' => $record,
        ], 201);
    }

    public function search(SearchKeywordRequest $request): JsonResponse
    {
        $keyword = $request->validated()['q'];

        $records = $this->baseQuery()
            ->where(function ($query) use ($keyword): void {
                $query->where('id_phieu_nhap', 'like', '%' . $keyword . '%')
                    ->orWhere('ngay_nhap', 'like', '%' . $keyword . '%')
                    ->orWhereHas('nhaSanXuat', function ($nhaSanXuatQuery) use ($keyword): void {
                        $nhaSanXuatQuery->where('ten_nha_san_xuat', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('nhanVien', function ($nhanVienQuery) use ($keyword): void {
                        $nhanVienQuery->where('ho_ten', 'like', '%' . $keyword . '%')
                            ->orWhere('ten_dang_nhap', 'like', '%' . $keyword . '%');
                    });
            })
            ->get();

        return response()->json([
            'message' => 'Tim kiem phieu nhap thanh cong.',
            'data' => $records,
        ]);
    }

    public function statistics(DateRangeRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = PhieuNhap::query();

        if (! empty($filters['from'])) {
            $query->whereDate('ngay_nhap', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('ngay_nhap', '<=', $filters['to']);
        }

        $tongPhieuNhap = (clone $query)->count();
        $tongTien = (float) (clone $query)->sum('tong_tien');

        $theoNgay = (clone $query)
            ->selectRaw('date(ngay_nhap) as ngay, count(*) as so_phieu, sum(tong_tien) as tong_tien')
            ->groupByRaw('date(ngay_nhap)')
            ->orderBy('ngay')
            ->get();

        return response()->json([
            'message' => 'Thong ke phieu nhap thanh cong.',
            'data' => [
                'bo_loc' => [
                    'from' => $filters['from'] ?? null,
                    'to' => $filters['to'] ?? null,
                ],
                'tong_quan' => [
                    'tong_so_phieu_nhap' => $tongPhieuNhap,
                    'tong_tien_nhap' => round($tongTien, 2),
                ],
                'theo_ngay' => $theoNgay,
            ],
        ]);
    }

    private function baseQuery()
    {
        return PhieuNhap::query()
            ->with($this->receiptRelations())
            ->orderByDesc('id_phieu_nhap');
    }

    private function receiptRelations(): array
    {
        return [
            'nhaSanXuat:id,ten_nha_san_xuat',
            'nhanVien:id_nhan_vien,ten_dang_nhap,ho_ten,id_vai_tro',
            'nhanVien.vaiTro:id_vai_tro,ten_vai_tro',
            'chiTiets',
            'chiTiets.loThuoc:id_lo,so_lo,id_thuoc,ngay_san_xuat,han_su_dung,don_vi_nhap,don_vi_co_so,so_luong_nhap_goc,so_luong_nhap,so_luong_con,he_so_quy_doi_nhap,gia_nhap,gia_nhap_quy_doi',
            'chiTiets.loThuoc.thuoc:ma_thuoc,ten_thuoc,don_vi_tinh,don_vi_co_so,he_so_quy_doi,quy_cach_don_vi',
        ];
    }

    private function createLotFromReceiptDetail(PhieuNhap $phieuNhap, array $detail): void
    {
        $this->validateDateRange($detail['ngay_san_xuat'], $detail['han_su_dung']);

        $thuoc = Thuoc::query()->findOrFail($detail['id_thuoc']);
        $unitOption = $this->resolveUnitOption($thuoc, $detail['don_vi_nhap']);
        $soLuongNhapGoc = (int) $detail['so_luong_nhap_goc'];
        $heSoQuyDoi = max(1, (int) ($unitOption['so_luong_quy_doi'] ?? 1));
        $soLuongQuyDoi = $soLuongNhapGoc * $heSoQuyDoi;
        $giaNhap = (int) round((float) $detail['gia_nhap']);
        $giaNhapQuyDoi = round($giaNhap / $heSoQuyDoi, 2);
        $thanhTien = $soLuongNhapGoc * $giaNhap;

        $loThuoc = LoThuoc::create([
            'id_thuoc' => $detail['id_thuoc'],
            'so_lo' => trim((string) $detail['so_lo']),
            'ngay_san_xuat' => $detail['ngay_san_xuat'],
            'han_su_dung' => $detail['han_su_dung'],
            'don_vi_nhap' => $unitOption['ten_don_vi'],
            'don_vi_co_so' => $thuoc->baseUnitName(),
            'so_luong_nhap_goc' => $soLuongNhapGoc,
            'he_so_quy_doi_nhap' => $heSoQuyDoi,
            'so_luong_nhap' => $soLuongQuyDoi,
            'so_luong_con' => $soLuongQuyDoi,
            'gia_nhap' => $giaNhap,
            'gia_nhap_quy_doi' => $giaNhapQuyDoi,
        ]);

        ChiTietPhieuNhap::create([
            'id_phieu_nhap' => $phieuNhap->id_phieu_nhap,
            'id_lo' => $loThuoc->id_lo,
            'don_vi_nhap' => $unitOption['ten_don_vi'],
            'don_vi_co_so' => $thuoc->baseUnitName(),
            'so_luong_nhap_goc' => $soLuongNhapGoc,
            'he_so_quy_doi_nhap' => $heSoQuyDoi,
            'so_luong' => $soLuongQuyDoi,
            'gia_nhap' => $giaNhap,
            'gia_nhap_quy_doi' => $giaNhapQuyDoi,
            'thanh_tien' => $thanhTien,
        ]);
    }

    private function resolveUnitOption(Thuoc $thuoc, string $requestedUnit): array
    {
        $matchedUnit = $thuoc->findUnitOption($requestedUnit);

        if (! $matchedUnit) {
            throw ValidationException::withMessages([
                'chi_tiets' => ["Don vi {$requestedUnit} khong hop le cho thuoc {$thuoc->ten_thuoc}."],
            ]);
        }

        return $matchedUnit;
    }

    private function validateDateRange(string $ngaySanXuat, string $hanSuDung): void
    {
        if (Carbon::parse($hanSuDung)->lte(Carbon::parse($ngaySanXuat))) {
            throw ValidationException::withMessages([
                'chi_tiets' => ['Han su dung phai sau ngay san xuat.'],
            ]);
        }
    }

    private function recalculateTotal(int $idPhieuNhap): void
    {
        $tongTien = ChiTietPhieuNhap::query()
            ->where('id_phieu_nhap', $idPhieuNhap)
            ->selectRaw('coalesce(sum(coalesce(thanh_tien, so_luong * gia_nhap)), 0) as tong_tien')
            ->value('tong_tien');

        PhieuNhap::query()
            ->where('id_phieu_nhap', $idPhieuNhap)
            ->update(['tong_tien' => $tongTien]);
    }

    private function generateReceiptCode(): string
    {
        do {
            $code = 'PN' . now()->format('YmdHis') . random_int(10, 99);
        } while (PhieuNhap::query()->where('ma_phieu_nhap', $code)->exists());

        return $code;
    }
}
