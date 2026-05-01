<?php

namespace App\Http\Controllers;

use App\Models\KhachHang;
use App\Http\Requests\StoreKhachHangRequest;
use App\Http\Requests\UpdateKhachHangRequest;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KhachHangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lay danh sach khach hang thanh cong.',
            'data' => $this->baseQuery()->get()->map(fn (KhachHang $khachHang): array => $this->transformCustomerSummary($khachHang)),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreKhachHangRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $khachHang = $this->detailQuery()->find($id);

        if (! $khachHang) {
            return response()->json([
                'message' => 'Khong tim thay khach hang.',
            ], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet khach hang thanh cong.',
            'data' => $this->transformCustomerDetail($khachHang),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKhachHangRequest $request, KhachHang $khachHang)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KhachHang $khachHang)
    {
        //
    }

    public function search(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('q', ''));

        $khachHangs = $this->baseQuery()
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $customerQuery) use ($keyword): void {
                    $customerQuery->where('ten_khach_hang', 'like', '%' . $keyword . '%')
                        ->orWhere('so_dien_thoai', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%')
                        ->orWhere('dia_chi', 'like', '%' . $keyword . '%');
                });
            })
            ->get()
            ->map(fn (KhachHang $khachHang): array => $this->transformCustomerSummary($khachHang));

        return response()->json([
            'message' => 'Tim kiem khach hang thanh cong.',
            'data' => $khachHangs,
        ]);
    }

    private function baseQuery(): Builder
    {
        return KhachHang::query()
            ->withCount(['hoaDons as tong_so_don_hang' => fn (Builder $query) => $query->whereHas('chiTiets')])
            ->withSum(['hoaDons as tong_tien_da_mua' => fn (Builder $query) => $query->whereHas('chiTiets')], 'tien_thanh_toan')
            ->orderByDesc('id_khach_hang');
    }

    private function detailQuery(): Builder
    {
        return $this->baseQuery()
            ->with([
                'hoaDons' => fn ($query) => $query
                    ->whereHas('chiTiets')
                    ->with([
                        'latestLichSuDonHang',
                        'thanhToan:id_hoa_don,phuong_thuc,so_tien,thoi_gian,ma_giao_dich',
                        'chiTiets:id,id_hoa_don,id_lo,don_vi_ban,he_so_quy_doi_ban,so_luong,gia_ban,thanh_tien',
                        'chiTiets.loThuoc:id_lo,id_thuoc',
                        'chiTiets.loThuoc.thuoc:ma_thuoc,ten_thuoc,don_vi_tinh,hinh_anh',
                    ])
                    ->orderByDesc('ngay_ban')
                    ->orderByDesc('id_hoa_don'),
            ]);
    }

    private function transformCustomerSummary(KhachHang $khachHang): array
    {
        return [
            'id_khach_hang' => $khachHang->id_khach_hang,
            'ten_khach_hang' => $khachHang->ten_khach_hang,
            'so_dien_thoai' => $khachHang->so_dien_thoai,
            'email' => $khachHang->email,
            'dia_chi' => $khachHang->dia_chi,
            'ngay_sinh' => optional($khachHang->ngay_sinh)?->toDateString(),
            'gioi_tinh' => $khachHang->gioi_tinh,
            'diem_tich_luy' => (int) ($khachHang->diem_tich_luy ?? 0),
            'email_verified' => (bool) $khachHang->email_verified,
            'avatar_url' => $khachHang->avatar_url,
            'tong_so_don_hang' => (int) ($khachHang->tong_so_don_hang ?? 0),
            'tong_tien_da_mua' => (float) ($khachHang->tong_tien_da_mua ?? 0),
        ];
    }

    private function transformCustomerDetail(KhachHang $khachHang): array
    {
        return [
            ...$this->transformCustomerSummary($khachHang),
            'lich_su_don_hang' => $khachHang->hoaDons
                ->map(fn (HoaDon $hoaDon): array => $this->transformOrder($hoaDon))
                ->values(),
        ];
    }

    private function transformOrder(HoaDon $hoaDon): array
    {
        return [
            'id_hoa_don' => $hoaDon->id_hoa_don,
            'ma_hoa_don' => $hoaDon->ma_hoa_don,
            'ngay_ban' => optional($hoaDon->ngay_ban)?->toIso8601String(),
            'trang_thai' => $hoaDon->latestLichSuDonHang?->trang_thai ?: 'Thanh cong',
            'tong_tien' => (float) $hoaDon->tong_tien,
            'giam_gia' => (float) $hoaDon->giam_gia,
            'thue_vat' => (float) $hoaDon->thue_vat,
            'tien_thanh_toan' => (float) $hoaDon->tien_thanh_toan,
            'phuong_thuc_thanh_toan' => $hoaDon->thanhToan?->phuong_thuc,
            'ma_giao_dich' => $hoaDon->thanhToan?->ma_giao_dich,
            'items' => $hoaDon->chiTiets
                ->map(fn (ChiTietHoaDon $chiTiet): array => [
                    'id' => $chiTiet->id,
                    'ma_thuoc' => $chiTiet->loThuoc?->thuoc?->ma_thuoc,
                    'ten_thuoc' => $chiTiet->loThuoc?->thuoc?->ten_thuoc ?: 'San pham',
                    'hinh_anh_url' => $chiTiet->loThuoc?->thuoc?->hinh_anh_url,
                    'don_vi' => $chiTiet->don_vi_ban ?: $chiTiet->loThuoc?->thuoc?->don_vi_tinh,
                    'so_luong' => (int) $chiTiet->so_luong,
                    'gia_ban' => (float) $chiTiet->gia_ban,
                    'thanh_tien' => (float) $chiTiet->thanh_tien,
                ])
                ->values(),
        ];
    }
}
