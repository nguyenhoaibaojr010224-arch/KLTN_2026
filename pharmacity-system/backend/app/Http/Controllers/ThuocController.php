<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateThuocPriceRequest;
use App\Models\Thuoc;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThuocController extends Controller
{
    public function index()
    {
        return response()->json(
            Thuoc::with(['loaiThuoc', 'nhaSanXuat', 'khuyenMais' => fn ($query) => $query->latest()])
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // In standard Laravel form requests, unique is checked against the DB.
            'ma_thuoc' => 'required|string|max:10|unique:thuocs,ma_thuoc',
            'ten_thuoc' => 'required|string|min:5|max:100',
            'ham_luong' => 'nullable|string',
            'don_vi_tinh' => 'required|string',
            'gia_ban' => 'required|numeric|min:1',
            'trang_thai' => ['nullable', Rule::in(['còn bán', 'ngừng bán'])],
            'id_loai_thuoc' => 'required|exists:loai_thuocs,id',
            'id_nha_san_xuat' => 'required|exists:nha_san_xuats,id',
        ]);

        $thuoc = Thuoc::create($validated);
        return response()->json($thuoc, 201);
    }

    public function show($id)
    {
        $thuoc = Thuoc::with(['loaiThuoc', 'nhaSanXuat', 'khuyenMais' => fn ($query) => $query->latest()])->find($id);

        if (!$thuoc) {
            return response()->json(['message' => 'Không tìm thấy thuốc'], 404);
        }

        return response()->json($thuoc);
    }

    public function update(Request $request, $id)
    {
        $thuoc = Thuoc::find($id);

        if (!$thuoc) {
            return response()->json(['message' => 'Không tìm thấy thuốc'], 404);
        }

        $validated = $request->validate([
            'ma_thuoc' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('thuocs')->ignore($thuoc->ma_thuoc, 'ma_thuoc')],
            'ten_thuoc' => 'sometimes|required|string|min:5|max:100',
            'ham_luong' => 'nullable|string',
            'don_vi_tinh' => 'sometimes|required|string',
            'gia_ban' => 'sometimes|required|numeric|min:1',
            'trang_thai' => ['nullable', Rule::in(['còn bán', 'ngừng bán'])],
            'id_loai_thuoc' => 'sometimes|required|exists:loai_thuocs,id',
            'id_nha_san_xuat' => 'sometimes|required|exists:nha_san_xuats,id',
        ]);

        $thuoc->update($validated);
        return response()->json($thuoc);
    }

    public function destroy($id)
    {
        $thuoc = Thuoc::find($id);

        if (!$thuoc) {
            return response()->json(['message' => 'Không tìm thấy thuốc'], 404);
        }

        $thuoc->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $thuocs = Thuoc::with(['loaiThuoc', 'nhaSanXuat', 'khuyenMais' => fn ($builder) => $builder->latest()])
            ->where('ten_thuoc', 'like', '%' . $query . '%')
            ->orWhere('ma_thuoc', 'like', '%' . $query . '%')
            ->get();

        return response()->json($thuocs);
    }

    public function updateStatus(Request $request, $id)
    {
        $thuoc = Thuoc::find($id);

        if (!$thuoc) {
            return response()->json(['message' => 'Không tìm thấy thuốc'], 404);
        }

        $request->validate([
            'trang_thai' => ['required', Rule::in(['còn bán', 'ngừng bán'])],
        ]);

        $thuoc->update(['trang_thai' => $request->trang_thai]);
        return response()->json($thuoc);
    }

    public function updatePrice(UpdateThuocPriceRequest $request, string $id)
    {
        $thuoc = Thuoc::with(['khuyenMais' => fn ($query) => $query->latest()])->find($id);

        if (! $thuoc) {
            return response()->json(['message' => 'Khong tim thay thuoc'], 404);
        }

        $thuoc->update([
            'gia_ban' => (int) $request->validated('gia_ban'),
        ]);

        return response()->json([
            'message' => 'Cap nhat gia ban thanh cong.',
            'data' => $thuoc->fresh(['loaiThuoc', 'nhaSanXuat', 'khuyenMais' => fn ($query) => $query->latest()]),
        ]);
    }
}
