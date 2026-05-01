<?php

namespace App\Http\Controllers;

use App\Models\NhanVien;
use App\Models\ThongTinNhanVien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class NhanVienController extends Controller
{
    public function index()
    {
        return response()->json($this->baseQuery()->get());
    }

    public function show($id)
    {
        $nhanVien = $this->baseQuery()->find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        return response()->json($nhanVien);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'so_dien_thoai' => [
                'required',
                'string',
                'size:10',
                Rule::unique('thong_tin_nhan_viens', 'so_dien_thoai'),
                Rule::unique('nhan_viens', 'ten_dang_nhap'),
            ],
            'mat_khau' => 'required|string|min:6',
            'ho_ten' => 'required|string|min:5|max:100',
            'id_vai_tro' => 'required|exists:vai_tros,id_vai_tro',
            'id_bang_cap' => 'required|exists:bang_caps,id_bang_cap',
            'trang_thai' => 'sometimes|in:active,inactive'
        ]);

        $nhanVien = DB::transaction(function () use ($validated) {
            $nhanVien = NhanVien::create([
                'ten_dang_nhap' => $validated['so_dien_thoai'],
                'mat_khau' => Hash::make($validated['mat_khau']),
                'ho_ten' => $validated['ho_ten'],
                'id_vai_tro' => $validated['id_vai_tro'],
                'id_bang_cap' => $validated['id_bang_cap'],
                'trang_thai' => $validated['trang_thai'] ?? 'active',
            ]);

            ThongTinNhanVien::create($this->defaultProfilePayload($nhanVien, $validated['so_dien_thoai']));

            return $nhanVien->load('vaiTro', 'bangCap', 'thongTinNhanVien');
        });

        return response()->json($nhanVien, 201);
    }

    public function update(Request $request, $id)
    {
        $nhanVien = NhanVien::find($id);

        if (!$nhanVien) {
            return response()->json(['message' => 'Không tìm thấy nhân viên'], 404);
        }

        $validated = $request->validate([
            'so_dien_thoai' => [
                'sometimes',
                'required',
                'string',
                'size:10',
                Rule::unique('thong_tin_nhan_viens', 'so_dien_thoai')->ignore($nhanVien->id_nhan_vien, 'id_nhan_vien'),
                Rule::unique('nhan_viens', 'ten_dang_nhap')->ignore($nhanVien->id_nhan_vien, 'id_nhan_vien'),
            ],
            'ho_ten' => 'sometimes|required|string|min:5|max:100',
            'id_vai_tro' => 'sometimes|required|exists:vai_tros,id_vai_tro',
            'id_bang_cap' => 'sometimes|required|exists:bang_caps,id_bang_cap',
            'trang_thai' => 'sometimes|in:active,inactive'
        ]);

        DB::transaction(function () use ($nhanVien, $validated) {
            $nhanVienPayload = collect($validated)
                ->except('so_dien_thoai')
                ->toArray();

            if (! empty($validated['so_dien_thoai'])) {
                $nhanVienPayload['ten_dang_nhap'] = $validated['so_dien_thoai'];
            }

            if (! empty($nhanVienPayload)) {
                $nhanVien->update($nhanVienPayload);
            }

            if (! empty($validated['so_dien_thoai'])) {
                $profile = $nhanVien->thongTinNhanVien()->first();

                if ($profile) {
                    $profile->update([
                        'so_dien_thoai' => $validated['so_dien_thoai'],
                    ]);
                } else {
                    ThongTinNhanVien::create($this->defaultProfilePayload($nhanVien, $validated['so_dien_thoai']));
                }
            }
        });

        $nhanVien->load('vaiTro', 'bangCap', 'thongTinNhanVien');

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
        
        $nhanViens = $this->baseQuery()
            ->where(function ($nhanVienQuery) use ($query) {
                $nhanVienQuery->where('ten_dang_nhap', 'like', '%' . $query . '%')
                    ->orWhere('ho_ten', 'like', '%' . $query . '%')
                    ->orWhereHas('thongTinNhanVien', function ($profileQuery) use ($query) {
                        $profileQuery->where('so_dien_thoai', 'like', '%' . $query . '%')
                            ->orWhere('email', 'like', '%' . $query . '%');
                    });
            })
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
        
        $nhanVien->load('vaiTro', 'bangCap', 'thongTinNhanVien');

        return response()->json(['message' => 'Cập nhật vai trò thành công', 'nhan_vien' => $nhanVien]);
    }

    private function baseQuery()
    {
        return NhanVien::with('vaiTro', 'bangCap', 'thongTinNhanVien')
            ->orderBy('id_nhan_vien');
    }

    private function defaultProfilePayload(NhanVien $nhanVien, string $phone): array
    {
        return [
            'id_nhan_vien' => $nhanVien->id_nhan_vien,
            'so_dien_thoai' => $phone,
            'email' => 'nhanvien' . $nhanVien->id_nhan_vien . '@pharmago.local',
            'dia_chi' => 'Chưa cập nhật địa chỉ',
            'ngay_sinh' => now()->subYears(20)->toDateString(),
            'ngay_vao_lam' => now()->toDateString(),
        ];
    }
}
