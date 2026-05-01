<?php

namespace App\Http\Controllers;

use App\Models\BangCap;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BangCapController extends Controller
{
    public function index()
    {
        return response()->json(BangCap::all());
    }

    public function show($id)
    {
        $bangCap = BangCap::find($id);

        if (!$bangCap) {
            return response()->json(['message' => 'Không tìm thấy bằng cấp'], 404);
        }

        return response()->json($bangCap);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_bang_cap' => 'required|string|unique:bang_caps,ten_bang_cap',
            'mo_ta' => 'nullable|string',
        ]);

        $bangCap = BangCap::create($validated);
        return response()->json($bangCap, 201);
    }

    public function update(Request $request, $id)
    {
        $bangCap = BangCap::find($id);

        if (!$bangCap) {
            return response()->json(['message' => 'Không tìm thấy bằng cấp'], 404);
        }

        $validated = $request->validate([
            'ten_bang_cap' => ['sometimes', 'required', 'string', Rule::unique('bang_caps')->ignore($bangCap->id_bang_cap, 'id_bang_cap')],
            'mo_ta' => 'nullable|string',
        ]);

        $bangCap->update($validated);
        return response()->json($bangCap);
    }

    public function destroy($id)
    {
        $bangCap = BangCap::find($id);

        if (!$bangCap) {
            return response()->json(['message' => 'Không tìm thấy bằng cấp'], 404);
        }

        $bangCap->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        $bangCaps = BangCap::where('ten_bang_cap', 'like', '%' . $query . '%')
            ->orWhere('mo_ta', 'like', '%' . $query . '%')
            ->get();

        return response()->json($bangCaps);
    }
}
