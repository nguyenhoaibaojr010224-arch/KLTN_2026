<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordWithTokenRequest;
use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StorePasswordResetRequest;
use App\Http\Requests\ValidatePasswordResetTokenRequest;
use App\Models\KhachHang;
use App\Models\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function request(StorePasswordResetRequest $request): JsonResponse
    {
        PasswordReset::query()
            ->where('email', $request->validated()['email'])
            ->where('used', false)
            ->update(['expired_at' => now()]);

        $record = PasswordReset::create([
            'email' => $request->validated()['email'],
            'token' => Str::random(64),
            'expired_at' => now()->addHours(2),
            'used' => false,
        ]);

        return response()->json([
            'message' => 'Tao yeu cau reset password thanh cong.',
            'data' => $record,
        ], 201);
    }

    public function validateToken(ValidatePasswordResetTokenRequest $request): JsonResponse
    {
        $record = PasswordReset::query()->where('token', $request->validated()['token'])->first();

        if (! $record) {
            return response()->json(['message' => 'Token reset password khong hop le.'], 404);
        }

        return response()->json([
            'message' => 'Kiem tra token thanh cong.',
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
            return response()->json(['message' => 'Thong tin reset password khong hop le.'], 404);
        }

        if ($record->used) {
            return response()->json(['message' => 'Token nay da duoc su dung.'], 422);
        }

        if ($record->expired_at->isPast()) {
            return response()->json(['message' => 'Token reset password da het han.'], 422);
        }

        $khachHang = KhachHang::query()->where('email', $record->email)->first();

        if (! $khachHang) {
            return response()->json(['message' => 'Khong tim thay tai khoan can reset password.'], 404);
        }

        $khachHang->update([
            'mat_khau' => Hash::make($request->validated()['password']),
        ]);

        $record->update(['used' => true]);

        return response()->json(['message' => 'Dat lai mat khau thanh cong.']);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lay danh sach password reset thanh cong.',
            'data' => PasswordReset::query()->latest()->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $record = PasswordReset::find($id);

        if (! $record) {
            return response()->json(['message' => 'Khong tim thay yeu cau reset password.'], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet password reset thanh cong.',
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
            'message' => 'Tim kiem password reset thanh cong.',
            'data' => $records,
        ]);
    }
}
