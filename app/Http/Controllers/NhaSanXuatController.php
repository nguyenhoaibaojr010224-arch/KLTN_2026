<?php

namespace App\Http\Controllers;

use App\Models\NhaSanXuat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NhaSanXuatController extends Controller
{
    public function index()
    {
        return response()->json(NhaSanXuat::all());
    }

    public function show($id)
    {
        $nha = NhaSanXuat::find($id);

        if (!$nha) {
            return response()->json(['message' => 'Không tìm thấy nhà sản xuất'], 404);
        }

        return response()->json($nha);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_nha_san_xuat' => 'required|string|min:3|max:100|unique:nha_san_xuats,ten_nha_san_xuat',
            'nuoc_san_xuat' => 'nullable|string|min:2|max:100',
            'dia_chi' => 'nullable|string|min:5|max:200',
            'so_dien_thoai' => 'nullable|string|min:10|max:15',
        ]);

        $nha = NhaSanXuat::create($validated);
        return response()->json($nha, 201);
    }

    public function update(Request $request, $id)
    {
        $nha = NhaSanXuat::find($id);

        if (!$nha) {
            return response()->json(['message' => 'Không tìm thấy nhà sản xuất'], 404);
        }

        $validated = $request->validate([
            'ten_nha_san_xuat' => ['sometimes', 'required', 'string', 'min:3', 'max:100', Rule::unique('nha_san_xuats')->ignore($nha->id)],
            'nuoc_san_xuat' => 'nullable|string|min:2|max:100',
            'dia_chi' => 'nullable|string|min:5|max:200',
            'so_dien_thoai' => 'nullable|string|min:10|max:15',
        ]);

        $nha->update($validated);
        return response()->json($nha);
    }

    public function destroy($id)
    {
        $nha = NhaSanXuat::find($id);

        if (!$nha) {
            return response()->json(['message' => 'Không tìm thấy nhà sản xuất'], 404);
        }

        $nha->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        $results = NhaSanXuat::where('ten_nha_san_xuat', 'like', '%' . $query . '%')
            ->orWhere('nuoc_san_xuat', 'like', '%' . $query . '%')
            ->orWhere('dia_chi', 'like', '%' . $query . '%')
            ->get();

        return response()->json($results);
    }
}
