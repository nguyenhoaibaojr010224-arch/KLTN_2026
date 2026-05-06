<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordWithTokenRequest;
use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StorePasswordResetRequest;
use App\Http\Requests\ValidatePasswordResetTokenRequest;
use App\Mail\PasswordResetCodeMail;
use App\Models\KhachHang;
use App\Models\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    public function request(StorePasswordResetRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        PasswordReset::query()
            ->where('email', $email)
            ->where('used', false)
            ->update(['expired_at' => now()]);

        $khachHang = KhachHang::query()->where('email', $email)->firstOrFail();
        $code = $this->generateCode();

        $record = PasswordReset::create([
            'email' => $email,
            'token' => $code,
            'expired_at' => now()->addMinutes(15),
            'used' => false,
        ]);

        try {
            Mail::to($email)->send(new PasswordResetCodeMail($khachHang, $code));
        } catch (\Throwable $exception) {
            Log::warning('Không thể gửi mã đặt lại mật khẩu.', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Mã đặt lại mật khẩu đã được gửi đến Gmail.',
            'data' => [
                'email' => $record->email,
                'expired_at' => $record->expired_at,
                'token' => $record->token,
            ],
        ], 201);
    }

    public function validateToken(ValidatePasswordResetTokenRequest $request): JsonResponse
    {
        $record = PasswordReset::query()->where('token', $request->validated()['token'])->first();

        if (! $record) {
            return response()->json(['message' => 'Mã xác minh không đúng'], 422);
        }

        return response()->json([
            'message' => 'Kiểm tra token thành công.',
            'data' => [
                'email' => $record->email,
                'used' => $record->used,
                'expired' => $record->expired_at->isPast(),
                'valid' => ! $record->used && ! $record->expired_at->isPast(),
            ],
        ]);
    }

    public function reset(ResetPasswordWithTokenRequest $request): JsonResponse
    {
        $record = PasswordReset::query()
            ->where('email', $request->validated()['email'])
            ->where('token', $request->validated()['token'])
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Mã xác minh không đúng'], 422);
        }

        if ($record->used) {
            return response()->json(['message' => 'Token này đã được sử dụng.'], 422);
        }

        if ($record->expired_at->isPast()) {
            return response()->json(['message' => 'Token đặt lại mật khẩu đã hết hạn.'], 422);
        }

        $khachHang = KhachHang::query()->where('email', $record->email)->first();

        if (! $khachHang) {
            return response()->json(['message' => 'Không tìm thấy tài khoản cần đặt lại mật khẩu.'], 404);
        }

        $khachHang->update([
            'mat_khau' => Hash::make($request->validated()['password']),
        ]);

        $record->update(['used' => true]);

        return response()->json(['message' => 'Đặt lại mật khẩu thành công.']);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lấy danh sách yêu cầu đặt lại mật khẩu thành công.',
            'data' => PasswordReset::query()->latest()->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $record = PasswordReset::find($id);

        if (! $record) {
            return response()->json(['message' => 'Không tìm thấy yêu cầu đặt lại mật khẩu.'], 404);
        }

        return response()->json([
            'message' => 'Lấy chi tiết yêu cầu đặt lại mật khẩu thành công.',
            'data' => $record,
        ]);
    }

    public function search(SearchKeywordRequest $request): JsonResponse
    {
        $keyword = $request->validated()['q'];

        $records = PasswordReset::query()
            ->where('email', 'like', '%' . $keyword . '%')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Tìm kiếm yêu cầu đặt lại mật khẩu thành công.',
            'data' => $records,
        ]);
    }

    private function generateCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (PasswordReset::query()->where('token', $code)->where('used', false)->exists());

        return $code;
    }
}
