<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StoreLichSuDonHangRequest;
use App\Models\HoaDon;
use App\Models\LichSuDonHang;
use Illuminate\Http\JsonResponse;

class LichSuDonHangController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lay danh sach lich su don hang thanh cong.',
            'data' => $this->baseQuery()->get(),
        ]);
    }

    public function indexByHoaDon(int $idHoaDon): JsonResponse
    {
        return response()->json([
            'message' => 'Lay lich su cua hoa don thanh cong.',
            'data' => $this->baseQuery()->where('id_hoa_don', $idHoaDon)->get(),
        ]);
    }

    public function search(SearchKeywordRequest $request): JsonResponse
    {
        $keyword = $request->validated()['q'];

        $records = $this->baseQuery()
            ->where(function ($query) use ($keyword): void {
                $query->where('trang_thai', 'like', '%' . $keyword . '%')
                    ->orWhere('ghi_chu', 'like', '%' . $keyword . '%')
                    ->orWhereHas('hoaDon', function ($hoaDonQuery) use ($keyword): void {
                        $hoaDonQuery->where('ma_hoa_don', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('nhanVien', function ($nhanVienQuery) use ($keyword): void {
                        $nhanVienQuery->where('ho_ten', 'like', '%' . $keyword . '%');
                    });
            })
            ->get();

        return response()->json([
            'message' => 'Tim kiem lich su don hang thanh cong.',
            'data' => $records,
        ]);
    }

    public function store(StoreLichSuDonHangRequest $request, int $idHoaDon): JsonResponse
    {
        $hoaDon = HoaDon::find($idHoaDon);

        if (! $hoaDon) {
            return response()->json(['message' => 'Khong tim thay hoa don.'], 404);
        }

        $record = LichSuDonHang::create([
            'id_hoa_don' => $idHoaDon,
            'trang_thai' => $request->validated()['trang_thai'],
            'ghi_chu' => $request->validated()['ghi_chu'] ?? null,
            'thoi_gian' => $request->validated()['thoi_gian'] ?? now(),
            'id_nhan_vien' => $request->user()->id_nhan_vien,
        ]);

        $record->load(['hoaDon', 'nhanVien']);

        return response()->json([
            'message' => 'Them lich su don hang thanh cong.',
            'data' => $record,
        ], 201);
    }

    private function baseQuery()
    {
        return LichSuDonHang::query()
            ->with([
                'hoaDon:id_hoa_don,ma_hoa_don',
                'nhanVien:id_nhan_vien,ho_ten,ten_dang_nhap',
            ])
            ->orderByDesc('thoi_gian')
            ->orderByDesc('id_lich_su');
    }
}
