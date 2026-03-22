<?php

namespace App\Http\Controllers;

use App\Models\LoaiThuoc;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoaiThuocController extends Controller
{
    public function index()
    {
        return response()->json(LoaiThuoc::all());
    }

    public function show($id)
    {
        $loai = LoaiThuoc::find($id);

        if (!$loai) {
            return response()->json(['message' => 'Không tìm thấy loại thuốc'], 404);
        }

        return response()->json($loai);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_loai' => 'required|string|min:3|max:100|unique:loai_thuocs,ten_loai',
            'mo_ta' => 'nullable|string',
        ]);

        $loai = LoaiThuoc::create($validated);
        return response()->json($loai, 201);
    }

    public function update(Request $request, $id)
    {
        $loai = LoaiThuoc::find($id);

        if (!$loai) {
            return response()->json(['message' => 'Không tìm thấy loại thuốc'], 404);
        }

        $validated = $request->validate([
            'ten_loai' => ['sometimes', 'required', 'string', 'min:3', 'max:100', Rule::unique('loai_thuocs')->ignore($loai->id)],
            'mo_ta' => 'nullable|string',
        ]);

        $loai->update($validated);
        return response()->json($loai);
    }

    public function destroy($id)
    {
        $loai = LoaiThuoc::find($id);

        if (!$loai) {
            return response()->json(['message' => 'Không tìm thấy loại thuốc'], 404);
        }

        $loai->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        $results = LoaiThuoc::where('ten_loai', 'like', '%' . $query . '%')
            ->orWhere('mo_ta', 'like', '%' . $query . '%')
            ->get();

        return response()->json($results);
    }
}
