<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateRangeRequest;
use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StorePhieuNhapRequest;
use App\Models\PhieuNhap;
use Illuminate\Http\JsonResponse;

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
        $record = PhieuNhap::create([
            'id_nha_san_xuat' => $request->validated()['id_nha_san_xuat'],
            'id_nhan_vien' => $request->user()->id_nhan_vien,
            'tong_tien' => 0,
            'ngay_nhap' => $request->validated()['ngay_nhap'] ?? now(),
        ]);

        $record->load(['nhaSanXuat', 'nhanVien.vaiTro']);

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
            ->with([
                'nhaSanXuat:id,ten_nha_san_xuat',
                'nhanVien:id_nhan_vien,ten_dang_nhap,ho_ten,id_vai_tro',
                'nhanVien.vaiTro:id_vai_tro,ten_vai_tro',
                'chiTiets',
            ])
            ->orderByDesc('id_phieu_nhap');
    }
}
