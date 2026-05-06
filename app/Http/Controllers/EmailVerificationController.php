<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResendEmailVerificationRequest;
use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StoreEmailVerificationRequest;
use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerification;
use App\Models\KhachHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    public function request(StoreEmailVerificationRequest $request): JsonResponse
    {
        return $this->createVerification($request->validated()['email'], 'Mã xác minh email đã được gửi.');
    }

    public function resend(ResendEmailVerificationRequest $request): JsonResponse
    {
        return $this->createVerification($request->validated()['email'], 'Mã xác minh email mới đã được gửi.');
    }

    public function verify(string $token): JsonResponse
    {
        return $this->verifyRecordByToken($token);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:khach_hangs,email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $record = EmailVerification::query()
            ->where('email', $validated['email'])
            ->where('token', $validated['code'])
            ->latest()
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Mã xác minh không đúng'], 422);
        }

        return $this->verifyRecord($record, true);
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
            'message' => 'Tìm kiếm mã xác minh email thành công.',
            'data' => $records,
        ]);
    }

    public function issueCode(string $email): EmailVerification
    {
        EmailVerification::query()
            ->where('email', $email)
            ->where('verified', false)
            ->update(['expired_at' => now()]);

        $khachHang = KhachHang::query()->where('email', $email)->firstOrFail();
        $code = $this->generateCode();

        $record = EmailVerification::create([
            'email' => $email,
            'token' => $code,
            'expired_at' => now()->addMinutes(15),
            'verified' => false,
        ]);

        try {
            Mail::to($email)->send(new EmailVerificationCodeMail($khachHang, $code));
        } catch (\Throwable $exception) {
            Log::warning('Khong the gui ma xac minh email.', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }

        return $record;
    }

    private function createVerification(string $email, string $message): JsonResponse
    {
        $record = $this->issueCode($email);

        return response()->json([
            'message' => $message,
            'data' => [
                'email' => $record->email,
                'expired_at' => $record->expired_at,
            ],
        ], 201);
    }

    private function verifyRecordByToken(string $token): JsonResponse
    {
        $record = EmailVerification::query()->where('token', $token)->first();

        if (! $record) {
            return response()->json(['message' => 'Mã xác minh không đúng'], 422);
        }

        return $this->verifyRecord($record, false);
    }

    private function verifyRecord(EmailVerification $record, bool $issueAuthToken): JsonResponse
    {
        if ($record->verified) {
            return response()->json(['message' => 'Mã xác minh này đã được sử dụng.'], 422);
        }

        if ($record->expired_at->isPast()) {
            return response()->json(['message' => 'Mã xác minh đã hết hạn. Vui lòng gửi lại mã mới.'], 422);
        }

        $khachHang = KhachHang::query()->where('email', $record->email)->first();

        if (! $khachHang) {
            return response()->json(['message' => 'Không tìm thấy khách hàng tương ứng với email này.'], 404);
        }

        $record->update(['verified' => true]);
        $khachHang->update([
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
        $khachHang->tokens()->delete();

        $payload = [
            'message' => 'Xác minh email thành công.',
            'data' => [
                'email' => $record->email,
                'verified_at' => now(),
            ],
        ];

        if ($issueAuthToken) {
            $payload['token'] = $khachHang->createToken('auth_token', ['customer'])->plainTextToken;
            $payload['type'] = 'customer';
            $payload['user'] = $khachHang->fresh();
        }

        return response()->json($payload);
    }

    private function generateCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (EmailVerification::query()->where('token', $code)->where('verified', false)->exists());

        return $code;
    }
}
