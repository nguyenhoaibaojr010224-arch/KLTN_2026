<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterKhachHangRequest;
use App\Models\KhachHang;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterKhachHangRequest $request)
    {
        $validated = $request->validated();

        $khachHang = KhachHang::create($validated);
        app(EmailVerificationController::class)->issueCode($khachHang->email);

        return response()->json([
            'message' => 'Đăng ký thành công. Vui lòng xác thực email.',
            'type' => 'customer',
            'khach_hang' => $khachHang,
            'user' => $khachHang,
            'verification' => [
                'email' => $khachHang->email,
                'status' => 'pending',
            ],
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $password = (string) $request->string('password');
        $taiKhoan = (string) $request->string('tai_khoan');

        $nhanVien = NhanVien::with(['vaiTro', 'thongTinNhanVien'])
            ->whereHas('thongTinNhanVien', function ($query) use ($taiKhoan) {
                $query
                    ->where('so_dien_thoai', $taiKhoan)
                    ->orWhere('email', $taiKhoan);
            })
            ->first();

        if ($nhanVien && Hash::check($password, $nhanVien->mat_khau)) {
            if ($nhanVien->trang_thai !== 'active') {
                return response()->json(['message' => 'Tài khoản đã bị khóa.'], 403);
            }

            $role = $this->normalizeRole($nhanVien->vaiTro?->ten_vai_tro);

            if (! in_array($role, ['admin', 'staff'], true)) {
                return response()->json([
                    'message' => 'Chỉ admin và nhân viên mới được đăng nhập bằng số điện thoại hệ thống.',
                ], 403);
            }

            $nhanVien->tokens()->delete();
            NhanVienDangNhapLog::create([
                'id_nhan_vien' => $nhanVien->id_nhan_vien,
                'thoi_gian_dang_nhap' => now(),
                'dia_chi_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'token' => $nhanVien->createToken('auth_token', [$role])->plainTextToken,
                'message' => 'Đăng nhập thành công.',
                'user' => $nhanVien,
                'type' => $role,
            ]);
        }

        $khachHang = KhachHang::query()
            ->where('so_dien_thoai', $taiKhoan)
            ->orWhere('email', $taiKhoan)
            ->first();

        if (! $khachHang || ! Hash::check($password, $khachHang->mat_khau)) {
            throw ValidationException::withMessages([
                'tai_khoan' => ['Thông tin đăng nhập không chính xác.'],
            ]);
        }

        if (! $khachHang->email_verified) {
            return response()->json([
                'message' => 'Tai khoan dang cho xac minh email. Vui long nhap ma xac minh da gui den Gmail.',
                'verification_required' => true,
                'email' => $khachHang->email,
            ], 403);
        }

        $khachHang->tokens()->delete();

        return response()->json([
            'token' => $khachHang->createToken('auth_token', ['customer'])->plainTextToken,
            'message' => 'Đăng nhập thành công.',
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
                return response()->json(['message' => 'Token không hợp lệ.'], 400);
            }

            if ($khachHang->email_verified) {
                return response()->json(['message' => 'Email đã được xác thực trước đó.']);
            }

            $khachHang->update([
                'email_verified' => true,
                'email_verified_at' => now(),
            ]);

            return response()->json(['message' => 'Xác thực email thành công.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token không hợp lệ.'], 400);
        }
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if ($user instanceof NhanVien) {
            $user->load('vaiTro', 'bangCap', 'thongTinNhanVien');
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
                'email' => 'sometimes|required|email|max:100|unique:khach_hangs,email,' . $user->id_khach_hang . ',id_khach_hang',
                'dia_chi' => 'sometimes|nullable|string|min:5|max:100',
                'ngay_sinh' => 'sometimes|nullable|date',
                'gioi_tinh' => 'sometimes|nullable|string|in:Nam,Nữ,Khác',
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
                'so_dien_thoai' => 'sometimes|required|string|size:10|unique:thong_tin_nhan_viens,so_dien_thoai,' . $user->id_nhan_vien . ',id_nhan_vien',
                'email' => 'sometimes|required|email|max:100|unique:thong_tin_nhan_viens,email,' . $user->id_nhan_vien . ',id_nhan_vien',
                'dia_chi' => 'sometimes|nullable|string|min:5|max:100',
                'ngay_sinh' => 'sometimes|nullable|date',
                'gioi_tinh' => 'sometimes|nullable|string|in:Nam,Nữ,Khác',
                'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if (array_key_exists('ho_ten', $validated)) {
                $user->update([
                    'ho_ten' => $validated['ho_ten'],
                ]);
            }

            $thongTinNhanVien = $user->thongTinNhanVien()->firstOrCreate(
                ['id_nhan_vien' => $user->id_nhan_vien],
                [
                    'so_dien_thoai' => $user->thongTinNhanVien?->so_dien_thoai ?? '',
                    'email' => $user->thongTinNhanVien?->email ?? ('nhanvien' . $user->id_nhan_vien . '@example.com'),
                    'dia_chi' => $user->thongTinNhanVien?->dia_chi ?? 'Chưa cập nhật địa chỉ',
                    'ngay_sinh' => $user->thongTinNhanVien?->ngay_sinh ?? now()->subYears(20)->toDateString(),
                    'gioi_tinh' => $user->thongTinNhanVien?->gioi_tinh ?? null,
                    'ngay_vao_lam' => $user->thongTinNhanVien?->ngay_vao_lam ?? now()->toDateString(),
                ]
            );

            $thongTinPayload = collect($validated)
                ->only(['so_dien_thoai', 'email', 'dia_chi', 'ngay_sinh', 'gioi_tinh'])
                ->toArray();

            if ($request->hasFile('avatar')) {
                if ($thongTinNhanVien->avatar) {
                    Storage::disk('public')->delete($thongTinNhanVien->avatar);
                }

                $thongTinPayload['avatar'] = $request->file('avatar')->store('avatars', 'public');
            }

            if (! empty($thongTinPayload)) {
                $thongTinNhanVien->update($thongTinPayload);
            }
        }

        if ($user instanceof NhanVien) {
            $user = $user->fresh()->load('vaiTro', 'bangCap', 'thongTinNhanVien');
        } else {
            $user = $user->fresh();
        }

        return response()->json(['message' => 'Cập nhật hồ sơ thành công.', 'user' => $user]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
            'new_password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        if (! Hash::check($request->current_password, $user->mat_khau)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mật khẩu hiện tại không đúng.'],
            ]);
        }

        $user->update([
            'mat_khau' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Đổi mật khẩu thành công.']);
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
