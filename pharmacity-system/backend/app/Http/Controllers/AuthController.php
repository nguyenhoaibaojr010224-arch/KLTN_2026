<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\KhachHang;
use App\Models\NhanVien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'ten_khach_hang' => 'required|string|min:5|max:100',
            'so_dien_thoai' => 'required|string|size:10|unique:khach_hangs,so_dien_thoai',
            'email' => 'required|email|unique:khach_hangs,email',
            'dia_chi' => 'required|string|min:5|max:100',
            'mat_khau' => 'required|string|min:6|confirmed',
        ]);

        $validated['mat_khau'] = Hash::make($request->mat_khau);

        $khachHang = KhachHang::create($validated);
        $token = base64_encode($khachHang->email . '|' . time());
        $authToken = $khachHang->createToken('auth_token', ['customer'])->plainTextToken;

        return response()->json([
            'message' => 'Dang ky thanh cong. Vui long xac thuc email.',
            'token' => $authToken,
            'type' => 'customer',
            'khach_hang' => $khachHang,
            'user' => $khachHang,
            'verify_link' => url("/api/email/verify/{$token}"),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $password = (string) $request->string('password');

        if ($request->filled('ten_dang_nhap')) {
            $nhanVien = NhanVien::with('vaiTro')
                ->where('ten_dang_nhap', (string) $request->string('ten_dang_nhap'))
                ->first();

            if (! $nhanVien || ! Hash::check($password, $nhanVien->mat_khau)) {
                throw ValidationException::withMessages([
                    'tai_khoan' => ['Thong tin dang nhap khong chinh xac.'],
                ]);
            }

            if ($nhanVien->trang_thai !== 'active') {
                return response()->json(['message' => 'Tai khoan da bi khoa.'], 403);
            }

            $role = $this->normalizeRole($nhanVien->vaiTro?->ten_vai_tro);

            if (! in_array($role, ['admin', 'staff'], true)) {
                return response()->json([
                    'message' => 'Chi admin va nhan vien moi duoc dang nhap bang ten dang nhap he thong.',
                ], 403);
            }

            $nhanVien->tokens()->delete();

            return response()->json([
                'token' => $nhanVien->createToken('auth_token', [$role])->plainTextToken,
                'message' => 'Dang nhap thanh cong.',
                'user' => $nhanVien,
                'type' => $role,
            ]);
        }

        $khachHang = KhachHang::query()
            ->when($request->filled('email'), fn ($query) => $query->where('email', (string) $request->string('email')))
            ->when($request->filled('so_dien_thoai'), fn ($query) => $query->where('so_dien_thoai', (string) $request->string('so_dien_thoai')))
            ->first();

        if (! $khachHang || ! Hash::check($password, $khachHang->mat_khau)) {
            throw ValidationException::withMessages([
                'tai_khoan' => ['Thong tin dang nhap khong chinh xac.'],
            ]);
        }

        $khachHang->tokens()->delete();

        return response()->json([
            'token' => $khachHang->createToken('auth_token', ['customer'])->plainTextToken,
            'message' => 'Dang nhap thanh cong.',
            'user' => $khachHang,
            'type' => 'customer',
        ]);
    }

    public function verifyEmail($token)
    {
        try {
            $decoded = base64_decode($token);
            $email = explode('|', $decoded)[0];

            $khachHang = KhachHang::where('email', $email)->first();

            if (! $khachHang) {
                return response()->json(['message' => 'Token khong hop le.'], 400);
            }

            if ($khachHang->email_verified) {
                return response()->json(['message' => 'Email da duoc xac thuc truoc do.']);
            }

            $khachHang->update([
                'email_verified' => true,
                'email_verified_at' => now(),
            ]);

            return response()->json(['message' => 'Xac thuc email thanh cong.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token khong hop le.'], 400);
        }
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if ($user instanceof NhanVien) {
            $user->load('vaiTro', 'bangCap');
        }

        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if ($user instanceof KhachHang) {
            $validated = $request->validate([
                'ten_khach_hang' => 'sometimes|required|string|min:5|max:100',
                'so_dien_thoai' => 'sometimes|required|string|size:10|unique:khach_hangs,so_dien_thoai,' . $user->id_khach_hang . ',id_khach_hang',
                'dia_chi' => 'sometimes|required|string|min:5|max:100',
                'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }

                $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
            }

            $user->update($validated);
        } elseif ($user instanceof NhanVien) {
            $validated = $request->validate([
                'ho_ten' => 'sometimes|required|string|min:5|max:100',
            ]);
            $user->update($validated);
        }

        return response()->json(['message' => 'Cap nhat ho so thanh cong.', 'user' => $user->fresh()]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if (! Hash::check($request->current_password, $user->mat_khau)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mat khau hien tai khong dung.'],
            ]);
        }

        $user->update([
            'mat_khau' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Doi mat khau thanh cong.']);
    }

    private function normalizeRole(?string $role): string
    {
        $normalized = Str::of((string) $role)
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();

        if ($normalized === 'admin') {
            return 'admin';
        }

        if (in_array($normalized, ['staff', 'nhan_vien', 'nhanvien'], true)) {
            return 'staff';
        }

        return $normalized;
    }
}
