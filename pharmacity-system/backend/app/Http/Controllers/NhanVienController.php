<?php

namespace App\Http\Controllers;

use App\Models\NhanVien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class NhanVienController extends Controller
{
    public function index()
    {
        return response()->json(NhanVien::with('vaiTro', 'bangCap')->get());
    }

    public function show($id)
    {
        $nhanVien = NhanVien::with('vaiTro', 'bangCap')->find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        return response()->json($nhanVien);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ten_dang_nhap' => 'required|string|min:5|max:50|unique:nhan_viens,ten_dang_nhap',
            'mat_khau' => 'required|string|min:6',
            'ho_ten' => 'required|string|min:5|max:100',
            'id_vai_tro' => 'required|exists:vai_tros,id_vai_tro',
            'id_bang_cap' => 'required|exists:bang_caps,id_bang_cap',
            'trang_thai' => 'sometimes|in:active,inactive'
        ]);

        $validated['mat_khau'] = Hash::make($request->mat_khau);
        
        $nhanVien = NhanVien::create($validated);
        $nhanVien->load('vaiTro', 'bangCap');

        return response()->json($nhanVien, 201);
    }

    public function update(Request $request, $id)
    {
        $nhanVien = NhanVien::find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        $validated = $request->validate([
            'ten_dang_nhap' => ['sometimes', 'required', 'string', 'min:5', 'max:50', Rule::unique('nhan_viens')->ignore($nhanVien->id_nhan_vien, 'id_nhan_vien')],
            'ho_ten' => 'sometimes|required|string|min:5|max:100',
            'id_vai_tro' => 'sometimes|required|exists:vai_tros,id_vai_tro',
            'id_bang_cap' => 'sometimes|required|exists:bang_caps,id_bang_cap',
            'trang_thai' => 'sometimes|in:active,inactive'
        ]);

        $nhanVien->update($validated);
        $nhanVien->load('vaiTro', 'bangCap');

        return response()->json($nhanVien);
    }

    public function destroy($id)
    {
        $nhanVien = NhanVien::find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        $nhanVien->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $nhanViens = NhanVien::with('vaiTro', 'bangCap')
            ->where('ten_dang_nhap', 'like', '%' . $query . '%')
            ->orWhere('ho_ten', 'like', '%' . $query . '%')
            ->get();

        return response()->json($nhanViens);
    }

    public function changePassword(Request $request, $id)
    {
        $nhanVien = NhanVien::find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        $request->validate([
            'new_password' => 'required|string|min:6|confirmed'
        ]);

        $nhanVien->update([
            'mat_khau' => Hash::make($request->new_password)
        ]);

        return response()->json(['message' => 'Cập nhật mật khẩu thành công', 'nhan_vien' => $nhanVien]);
    }

    public function changeRole(Request $request, $id)
    {
        $nhanVien = NhanVien::find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        $request->validate([
            'id_vai_tro' => 'required|exists:vai_tros,id_vai_tro'
        ]);

        $nhanVien->update([
            'id_vai_tro' => $request->id_vai_tro
        ]);
        
        $nhanVien->load('vaiTro', 'bangCap');

        return response()->json(['message' => 'Cập nhật vai trò thành công', 'nhan_vien' => $nhanVien]);
    }
}
