<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterKhachHangRequest;
use App\Models\KhachHang;
use App\Models\MaGiamGia;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const STAFF_SESSION_HOURS = 8;
    private const ACTIVE_STAFF_SESSION_MESSAGE = 'Hiện tại hệ thống đang có người đăng nhập';
    private const ACTIVE_COUNTER_SESSION_MESSAGE = 'Hiện tại tại quầy đang có người đăng nhập';

    public function register(RegisterKhachHangRequest $request)
    {
        $validated = $request->validated();

        [$khachHang, $firstOrderCoupon] = DB::transaction(function () use ($validated): array {
            $khachHang = KhachHang::create($validated);

            return [
                $khachHang,
                $this->createFirstOrderCoupon($khachHang),
            ];
        });

        app(EmailVerificationController::class)->issueCode($khachHang->email);

        return response()->json([
            'message' => 'Đăng ký thành công. Vui lòng xác thực email.',
            'type' => 'customer',
            'khach_hang' => $khachHang,
            'user' => $khachHang,
            'first_order_coupon' => [
                'ma_giam_gia' => $firstOrderCoupon->ma_giam_gia,
                'gia_tri' => (int) $firstOrderCoupon->gia_tri,
                'loai_ap_dung' => $firstOrderCoupon->loai_ap_dung,
            ],
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
        $requestedLoginChannel = $request->input('kenh_dang_nhap');
        $loginChannel = $this->normalizeLoginChannel($requestedLoginChannel);

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

            if ($role === 'staff' && ! $this->hasLoginChannel($requestedLoginChannel)) {
                return response()->json([
                    'message' => 'Vui lòng chọn kênh đăng nhập nhân viên.',
                    'staff_channel_required' => true,
                    'type' => 'staff',
                ], 428);
            }

            $issuedAt = now();
            $tokenExpiresAt = $role === 'staff'
                ? $issuedAt->copy()->addHours(self::STAFF_SESSION_HOURS)
                : null;

            if ($role === 'staff') {
                try {
                    return $this->loginStaff($request, $nhanVien, $role, $loginChannel, $issuedAt, $tokenExpiresAt);
                } catch (QueryException $exception) {
                    if ($this->isActiveStaffSessionUniqueViolation($exception)) {
                        return response()->json([
                            'message' => $this->activeSessionMessage($loginChannel),
                        ], 409);
                    }

                    throw $exception;
                }
            }

            $nhanVien->tokens()->delete();
            $accessToken = $nhanVien->createToken('auth_token', [$role], $tokenExpiresAt);

            return response()->json([
                'token' => $accessToken->plainTextToken,
                'message' => 'Đăng nhập thành công.',
                'user' => $nhanVien,
                'type' => $role,
                'login_channel' => 'he_thong',
                'expires_at' => $tokenExpiresAt?->toISOString(),
                'work_session' => null,
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
                'message' => 'Tài khoản chưa xác minh email. Vui lòng nhập mã đã gửi đến Gmail để hoàn tất đăng nhập.',
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
            'login_channel' => null,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($user instanceof NhanVien) {
            $user->loadMissing('vaiTro');

            if ($this->normalizeRole($user->vaiTro?->ten_vai_tro) === 'staff') {
                $this->closeCurrentStaffSession($user, $this->tokenId($token), now(), 'manual');
            }
        }

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        } elseif ($user && method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Đăng xuất thành công.',
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

            if (array_key_exists('ngay_sinh', $thongTinPayload) && blank($thongTinPayload['ngay_sinh'])) {
                unset($thongTinPayload['ngay_sinh']);
            }

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

    private function createFirstOrderCoupon(KhachHang $khachHang): MaGiamGia
    {
        return MaGiamGia::create([
            'ma_giam_gia' => $this->generateFirstOrderCouponCode($khachHang),
            'ten_ma' => 'Uu dai don dau tien',
            'mo_ta' => 'Giam 10% cho don hang dau tien sau khi dang ky.',
            'loai_ap_dung' => 'phan_tram',
            'gia_tri' => 10,
            'gia_tri_don_toi_thieu' => 0,
            'gioi_han_moi_khach' => 1,
            'ngay_bat_dau' => now(),
            'ngay_ket_thuc' => null,
            'trang_thai' => 'active',
            'id_khach_hang' => $khachHang->id_khach_hang,
            'loai_ma' => 'first_order',
            'tu_dong_ap_dung' => true,
        ]);
    }

    private function generateFirstOrderCouponCode(KhachHang $khachHang): string
    {
        do {
            $code = Str::upper('WELCOME10-' . $khachHang->id_khach_hang . '-' . Str::random(6));
        } while (MaGiamGia::query()->where('ma_giam_gia', $code)->exists());

        return $code;
    }

    private function loginStaff(Request $request, NhanVien $nhanVien, string $role, string $loginChannel, $issuedAt, $tokenExpiresAt)
    {
        return DB::transaction(function () use ($request, $nhanVien, $role, $loginChannel, $issuedAt, $tokenExpiresAt) {
            $this->closeExpiredStaffSessions($issuedAt);

            $activeSession = NhanVienDangNhapLog::query()
                ->where('kenh_dang_nhap', $loginChannel)
                ->whereNull('thoi_gian_dang_xuat')
                ->lockForUpdate()
                ->first();

            if ($activeSession) {
                return response()->json([
                    'message' => $this->activeSessionMessage($loginChannel),
                ], 409);
            }

            $otherChannelSession = NhanVienDangNhapLog::query()
                ->where('id_nhan_vien', $nhanVien->id_nhan_vien)
                ->where('kenh_dang_nhap', '!=', $loginChannel)
                ->whereNull('thoi_gian_dang_xuat')
                ->lockForUpdate()
                ->first();

            if ($otherChannelSession) {
                return response()->json([
                    'message' => 'Tài khoản nhân viên này đang đăng nhập ở kênh khác. Vui lòng đăng xuất trước.',
                ], 409);
            }

            $nhanVien->tokens()->delete();
            $accessToken = $nhanVien->createToken('auth_token', [$role, 'channel:' . $loginChannel], $tokenExpiresAt);

            $workSession = NhanVienDangNhapLog::create([
                'id_nhan_vien' => $nhanVien->id_nhan_vien,
                'token_id' => $accessToken->accessToken->getKey(),
                'kenh_dang_nhap' => $loginChannel,
                'thoi_gian_dang_nhap' => $issuedAt,
                'het_han_luc' => $tokenExpiresAt,
                'dang_hoat_dong' => true,
                'dia_chi_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'token' => $accessToken->plainTextToken,
                'message' => 'Đăng nhập thành công.',
                'user' => $nhanVien,
                'type' => $role,
                'login_channel' => $loginChannel,
                'expires_at' => $tokenExpiresAt?->toISOString(),
                'work_session' => [
                    'id' => $workSession->id,
                    'kenh_dang_nhap' => $workSession->kenh_dang_nhap,
                    'thoi_gian_dang_nhap' => $workSession->thoi_gian_dang_nhap?->toISOString(),
                    'het_han_luc' => $workSession->het_han_luc?->toISOString(),
                ],
            ]);
        });
    }

    private function closeCurrentStaffSession(NhanVien $nhanVien, ?int $tokenId, $logoutAt, string $reason): void
    {
        $query = NhanVienDangNhapLog::query()
            ->where('id_nhan_vien', $nhanVien->id_nhan_vien)
            ->whereNull('thoi_gian_dang_xuat');

        if ($tokenId) {
            $query->where('token_id', $tokenId);
        }

        $log = $query
            ->latest('thoi_gian_dang_nhap')
            ->first();

        if (! $log && $tokenId) {
            $log = NhanVienDangNhapLog::query()
                ->where('id_nhan_vien', $nhanVien->id_nhan_vien)
                ->whereNull('thoi_gian_dang_xuat')
                ->latest('thoi_gian_dang_nhap')
                ->first();
        }

        if (! $log) {
            return;
        }

        $this->finishStaffSession($log, $logoutAt, $reason);
    }

    private function closeExpiredStaffSessions($now): void
    {
        NhanVienDangNhapLog::query()
            ->whereNull('thoi_gian_dang_xuat')
            ->lockForUpdate()
            ->get()
            ->each(function (NhanVienDangNhapLog $log) use ($now): void {
                $expiresAt = $log->het_han_luc
                    ?: $log->thoi_gian_dang_nhap?->copy()->addHours(self::STAFF_SESSION_HOURS);

                if (! $expiresAt || $expiresAt->gt($now)) {
                    return;
                }

                $this->finishStaffSession($log, $expiresAt->copy(), 'expired');
            });
    }

    private function finishStaffSession(NhanVienDangNhapLog $log, $logoutAt, string $reason): void
    {
        $loginAt = $log->thoi_gian_dang_nhap ?: $logoutAt;

        if ($logoutAt->lt($loginAt)) {
            $logoutAt = $loginAt->copy();
        }

        $log->update([
            'thoi_gian_dang_xuat' => $logoutAt,
            'thoi_luong_giay' => (int) $loginAt->diffInSeconds($logoutAt),
            'ly_do_dang_xuat' => $reason,
            'dang_hoat_dong' => null,
        ]);
    }

    private function isActiveStaffSessionUniqueViolation(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'nv_login_logs_single_active_session_unique')
            || str_contains($message, 'nv_login_logs_channel_active_unique')
            || str_contains($message, 'dang_hoat_dong');
    }

    private function normalizeLoginChannel(mixed $channel): string
    {
        return $channel === 'tai_quay' ? 'tai_quay' : 'he_thong';
    }

    private function hasLoginChannel(mixed $channel): bool
    {
        return in_array($channel, ['he_thong', 'tai_quay'], true);
    }

    private function activeSessionMessage(string $channel): string
    {
        return $channel === 'tai_quay'
            ? self::ACTIVE_COUNTER_SESSION_MESSAGE
            : self::ACTIVE_STAFF_SESSION_MESSAGE;
    }

    private function tokenId($token): ?int
    {
        if (! $token || ! method_exists($token, 'getKey')) {
            return null;
        }

        $key = $token->getKey();

        return is_numeric($key) ? (int) $key : null;
    }
}
