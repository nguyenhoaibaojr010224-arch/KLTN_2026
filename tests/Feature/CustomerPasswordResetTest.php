<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCodeMail;
use App\Models\KhachHang;
use App\Models\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_request_password_reset_code_by_email(): void
    {
        Mail::fake();

        $khachHang = KhachHang::factory()->create([
            'email' => 'forgot.customer@example.com',
        ]);

        $response = $this->postJson('/api/password-resets/request', [
            'email' => $khachHang->email,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', $khachHang->email);

        $record = PasswordReset::query()->where('email', $khachHang->email)->firstOrFail();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $record->token);

        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use ($khachHang, $record): bool {
            $rendered = $mail->render();

            return $mail->khachHang->is($khachHang)
                && $mail->code === $record->token
                && $mail->envelope()->subject === 'Mã đặt lại mật khẩu PharmaGo'
                && str_contains($rendered, 'Đặt lại mật khẩu PharmaGo')
                && str_contains($rendered, 'Xin chào')
                && str_contains($rendered, 'Mã đặt lại mật khẩu có hiệu lực trong 15 phút');
        });
    }

    public function test_customer_can_reset_password_with_email_code(): void
    {
        Mail::fake();

        $khachHang = KhachHang::factory()->create([
            'email' => 'reset.customer@example.com',
            'mat_khau' => 'OldPassword@123',
        ]);

        $this->postJson('/api/password-resets/request', [
            'email' => $khachHang->email,
        ])->assertCreated();

        $record = PasswordReset::query()->where('email', $khachHang->email)->firstOrFail();

        $response = $this->postJson('/api/password-resets/reset', [
            'email' => $khachHang->email,
            'token' => $record->token,
            'password' => 'NewPassword@123',
            'password_confirmation' => 'NewPassword@123',
        ]);

        $response->assertOk();

        $khachHang->refresh();
        $record->refresh();

        $this->assertTrue(Hash::check('NewPassword@123', $khachHang->mat_khau));
        $this->assertTrue($record->used);
    }
}
