<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StoreThongTinNhanVienRequest;
use App\Http\Requests\UpdateOwnThongTinNhanVienRequest;
use App\Http\Requests\UpdateThongTinNhanVienRequest;
use App\Models\ThongTinNhanVien;
use Illuminate\Http\JsonResponse;

class ThongTinNhanVienController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lay danh sach thong tin nhan vien thanh cong.',
            'data' => $this->baseQuery()->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->baseQuery()->find($id);

        if (! $record) {
            return response()->json(['message' => 'Khong tim thay thong tin nhan vien.'], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet thong tin nhan vien thanh cong.',
            'data' => $record,
        ]);
    }

    public function store(StoreThongTinNhanVienRequest $request): JsonResponse
    {
        $record = ThongTinNhanVien::create($request->validated());
        $record->load(['nhanVien.vaiTro', 'nhanVien.bangCap']);

        return response()->json([
            'message' => 'Tao thong tin nhan vien thanh cong.',
            'data' => $record,
        ], 201);
    }

    public function update(UpdateThongTinNhanVienRequest $request, int $id): JsonResponse
    {
        $record = ThongTinNhanVien::find($id);

        if (! $record) {
            return response()->json(['message' => 'Khong tim thay thong tin nhan vien.'], 404);
        }

        $record->update($request->validated());
        $record->load(['nhanVien.vaiTro', 'nhanVien.bangCap']);

        return response()->json([
            'message' => 'Cap nhat thong tin nhan vien thanh cong.',
            'data' => $record,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = ThongTinNhanVien::find($id);

        if (! $record) {
            return response()->json(['message' => 'Khong tim thay thong tin nhan vien.'], 404);
        }

        $record->delete();

        return response()->json(['message' => 'Xoa thong tin nhan vien thanh cong.']);
    }

    public function search(SearchKeywordRequest $request): JsonResponse
    {
        $keyword = $request->validated()['q'];

        $records = $this->baseQuery()
            ->where(function ($query) use ($keyword): void {
                $query->where('so_dien_thoai', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('dia_chi', 'like', '%' . $keyword . '%')
                    ->orWhereHas('nhanVien', function ($nhanVienQuery) use ($keyword): void {
                        $nhanVienQuery->where('ho_ten', 'like', '%' . $keyword . '%')
                            ->orWhere('ten_dang_nhap', 'like', '%' . $keyword . '%');
                    });
            })
            ->get();

        return response()->json([
            'message' => 'Tim kiem thong tin nhan vien thanh cong.',
            'data' => $records,
        ]);
    }

    public function me(): JsonResponse
    {
        $record = $this->baseQuery()->find(request()->user()->id_nhan_vien);

        if (! $record) {
            return response()->json(['message' => 'Nhan vien chua co thong tin chi tiet.'], 404);
        }

        return response()->json([
            'message' => 'Lay ho so nhan vien thanh cong.',
            'data' => $record,
        ]);
    }

    public function updateMe(UpdateOwnThongTinNhanVienRequest $request): JsonResponse
    {
        $record = ThongTinNhanVien::find(request()->user()->id_nhan_vien);

        if (! $record) {
            return response()->json(['message' => 'Nhan vien chua co thong tin chi tiet.'], 404);
        }

        $record->update($request->validated());
        $record->load(['nhanVien.vaiTro', 'nhanVien.bangCap']);

        return response()->json([
            'message' => 'Cap nhat thong tin ca nhan thanh cong.',
            'data' => $record,
        ]);
    }

    private function baseQuery()
    {
        return ThongTinNhanVien::query()
            ->with([
                'nhanVien:id_nhan_vien,ten_dang_nhap,ho_ten,id_vai_tro,id_bang_cap,trang_thai',
                'nhanVien.vaiTro:id_vai_tro,ten_vai_tro',
                'nhanVien.bangCap:id_bang_cap,ten_bang_cap',
            ])
            ->orderByDesc('id_nhan_vien');
    }
}
