<?php

namespace App\Http\Controllers;

use App\Models\VaiTro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VaiTroController extends Controller
{
    public function index()
    {
        return response()->json(VaiTro::all());
    }

    public function show($id)
    {
        $vaiTro = VaiTro::find($id);

        if (!$vaiTro) {
            return response()->json(['message' => 'Không tìm thấy vai trò'], 404);
        }

        return response()->json($vaiTro);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_vai_tro' => 'required|string|unique:vai_tros,ten_vai_tro',
            'mo_ta' => 'nullable|string',
        ]);

        $vaiTro = VaiTro::create($validated);
        return response()->json($vaiTro, 201);
    }

    public function update(Request $request, $id)
    {
        $vaiTro = VaiTro::find($id);

        if (!$vaiTro) {
            return response()->json(['message' => 'Không tìm thấy vai trò'], 404);
        }

        $validated = $request->validate([
            'ten_vai_tro' => ['sometimes', 'required', 'string', Rule::unique('vai_tros')->ignore($vaiTro->id_vai_tro, 'id_vai_tro')],
            'mo_ta' => 'nullable|string',
        ]);

        $vaiTro->update($validated);
        return response()->json($vaiTro);
    }

    public function destroy($id)
    {
        $vaiTro = VaiTro::find($id);

        if (!$vaiTro) {
            return response()->json(['message' => 'Không tìm thấy vai trò'], 404);
        }

        $vaiTro->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        $vaiTros = VaiTro::where('ten_vai_tro', 'like', '%' . $query . '%')
            ->orWhere('mo_ta', 'like', '%' . $query . '%')
            ->get();

        return response()->json($vaiTros);
    }
}
