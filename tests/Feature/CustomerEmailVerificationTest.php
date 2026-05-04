<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerification;
use App\Models\KhachHang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_pending_customer_and_sends_verification_code_without_auth_token(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', $this->validRegistrationPayload());

        $response
            ->assertCreated()
            ->assertJsonPath('type', 'customer')
            ->assertJsonPath('verification.email', 'verify.customer@example.com')
            ->assertJsonMissingPath('token');

        $this->assertDatabaseHas('khach_hangs', [
            'email' => 'verify.customer@example.com',
            'email_verified' => false,
        ]);
        $this->assertDatabaseHas('email_verifications', [
            'email' => 'verify.customer@example.com',
            'verified' => false,
        ]);

        $customer = KhachHang::query()->where('email', 'verify.customer@example.com')->firstOrFail();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        Mail::assertSent(EmailVerificationCodeMail::class, function (EmailVerificationCodeMail $mail) use ($customer) {
            $rendered = $mail->render();

            return $mail->khachHang->is($customer)
                && preg_match('/^\d{6}$/', $mail->code) === 1
                && $mail->envelope()->subject === 'Mã xác minh tài khoản PharmaGo'
                && str_contains($rendered, 'Xác minh tài khoản PharmaGo')
                && str_contains($rendered, 'Xin chào Khach Hang Verify')
                && str_contains($rendered, 'Mã xác minh có hiệu lực trong 15 phút');
        });
    }

    public function test_customer_can_verify_email_with_code_and_receive_auth_token(): void
    {
        Mail::fake();

        $this->postJson('/api/register', $this->validRegistrationPayload())->assertCreated();
        $record = EmailVerification::query()->where('email', 'verify.customer@example.com')->firstOrFail();

        $response = $this->postJson('/api/email-verifications/verify-code', [
            'email' => 'verify.customer@example.com',
            'code' => $record->token,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('type', 'customer')
            ->assertJsonPath('user.email', 'verify.customer@example.com')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('khach_hangs', [
            'email' => 'verify.customer@example.com',
            'email_verified' => true,
        ]);
        $this->assertDatabaseHas('email_verifications', [
            'email' => 'verify.customer@example.com',
            'token' => $record->token,
            'verified' => true,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_unverified_customer_cannot_login_until_email_is_verified(): void
    {
        KhachHang::factory()->create([
            'so_dien_thoai' => '0900000111',
            'email' => 'pending.customer@example.com',
            'mat_khau' => 'Password@123',
            'email_verified' => false,
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'so_dien_thoai' => '0900000111',
            'password' => 'Password@123',
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Tài khoản chưa xác minh email. Vui lòng nhập mã đã gửi đến Gmail để hoàn tất đăng nhập.')
            ->assertJsonPath('verification_required', true)
            ->assertJsonPath('email', 'pending.customer@example.com');
    }

    private function validRegistrationPayload(): array
    {
        return [
            'ten_khach_hang' => 'Khach Hang Verify',
            'so_dien_thoai' => '0900000999',
            'email' => 'verify.customer@example.com',
            'dia_chi' => '123 Nguyen Trai',
            'mat_khau' => 'Password@123',
            'mat_khau_confirmation' => 'Password@123',
        ];
    }
}
