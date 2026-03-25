<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResendEmailVerificationRequest;
use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StoreEmailVerificationRequest;
use App\Models\EmailVerification;
use App\Models\KhachHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    public function request(StoreEmailVerificationRequest $request): JsonResponse
    {
        return $this->createVerification($request->validated()['email'], 'Tao token xac thuc email thanh cong.');
    }

    public function resend(ResendEmailVerificationRequest $request): JsonResponse
    {
        return $this->createVerification($request->validated()['email'], 'Gui lai token xac thuc email thanh cong.');
    }

    public function verify(string $token): JsonResponse
    {
        $record = EmailVerification::query()->where('token', $token)->first();

        if (! $record) {
            return response()->json(['message' => 'Token xac thuc email khong hop le.'], 404);
        }

        if ($record->verified) {
            return response()->json(['message' => 'Token nay da duoc xac thuc truoc do.']);
        }

        if ($record->expired_at->isPast()) {
            return response()->json(['message' => 'Token xac thuc email da het han.'], 422);
        }

        $khachHang = KhachHang::query()->where('email', $record->email)->first();

        if (! $khachHang) {
            return response()->json(['message' => 'Khong tim thay khach hang tuong ung voi email nay.'], 404);
        }

        $record->update(['verified' => true]);
        $khachHang->update([
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Xac thuc email thanh cong.',
            'data' => [
                'email' => $record->email,
                'verified_at' => now(),
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lay danh sach email verification thanh cong.',
            'data' => EmailVerification::query()->latest()->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $record = EmailVerification::find($id);

        if (! $record) {
            return response()->json(['message' => 'Khong tim thay email verification.'], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet email verification thanh cong.',
            'data' => $record,
        ]);
    }

    public function search(SearchKeywordRequest $request): JsonResponse
    {
        $keyword = $request->validated()['q'];

        $records = EmailVerification::query()
            ->where('email', 'like', '%' . $keyword . '%')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Tim kiem email verification thanh cong.',
            'data' => $records,
        ]);
    }

    private function createVerification(string $email, string $message): JsonResponse
    {
        EmailVerification::query()
            ->where('email', $email)
            ->where('verified', false)
            ->update(['expired_at' => now()]);

        $record = EmailVerification::create([
            'email' => $email,
            'token' => Str::random(64),
            'expired_at' => now()->addHours(24),
            'verified' => false,
        ]);

        return response()->json([
            'message' => $message,
            'data' => $record,
        ], 201);
    }
}
