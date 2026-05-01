<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StoreThanhToanRequest;
use App\Models\HoaDon;
use App\Models\ThanhToan;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ThanhToanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lay danh sach thanh toan thanh cong.',
            'data' => $this->baseQuery()->get(),
        ]);
    }

    public function show(int $idHoaDon): JsonResponse
    {
        $record = $this->baseQuery()->find($idHoaDon);

        if (! $record) {
            return response()->json(['message' => 'Khong tim thay thanh toan.'], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet thanh toan thanh cong.',
            'data' => $record,
        ]);
    }

    public function search(SearchKeywordRequest $request): JsonResponse
    {
        $keyword = $request->validated()['q'];

        $records = $this->baseQuery()
            ->where(function ($query) use ($keyword): void {
                $query->where('phuong_thuc', 'like', '%' . $keyword . '%')
                    ->orWhere('ma_giao_dich', 'like', '%' . $keyword . '%')
                    ->orWhereHas('hoaDon', function ($hoaDonQuery) use ($keyword): void {
                        $hoaDonQuery->where('ma_hoa_don', 'like', '%' . $keyword . '%');
                    });
            })
            ->get();

        return response()->json([
            'message' => 'Tim kiem thanh toan thanh cong.',
            'data' => $records,
        ]);
    }

    public function store(StoreThanhToanRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $hoaDon = HoaDon::find($validated['id_hoa_don']);

        if (! $hoaDon) {
            return response()->json(['message' => 'Khong tim thay hoa don.'], 404);
        }

        $soTien = (float) ($validated['so_tien'] ?? $hoaDon->tien_thanh_toan);

        if ((float) $hoaDon->tien_thanh_toan !== $soTien) {
            throw ValidationException::withMessages([
                'so_tien' => ['So tien thanh toan phai bang tien_thanh_toan cua hoa don.'],
            ]);
        }

        if ($validated['phuong_thuc'] !== 'tien_mat' && empty($validated['ma_giao_dich'])) {
            throw ValidationException::withMessages([
                'ma_giao_dich' => ['Phuong thuc nay bat buoc co ma giao dich.'],
            ]);
        }

        $record = ThanhToan::create([
            'id_hoa_don' => $validated['id_hoa_don'],
            'phuong_thuc' => $validated['phuong_thuc'],
            'so_tien' => $soTien,
            'thoi_gian' => $validated['thoi_gian'] ?? now(),
            'ma_giao_dich' => $validated['ma_giao_dich'] ?? null,
        ]);

        $record->load(['hoaDon.khachHang', 'hoaDon.nhanVien']);

        return response()->json([
            'message' => 'Tao thanh toan thanh cong.',
            'data' => $record,
        ], 201);
    }

    private function baseQuery()
    {
        return ThanhToan::query()
            ->with([
                'hoaDon:id_hoa_don,ma_hoa_don,id_khach_hang,id_nhan_vien,tien_thanh_toan',
                'hoaDon.khachHang:id_khach_hang,ten_khach_hang',
                'hoaDon.nhanVien:id_nhan_vien,ho_ten',
            ])
            ->orderByDesc('ngay_tao');
    }
}
