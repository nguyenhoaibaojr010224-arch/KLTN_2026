<?php

namespace Tests\Feature;

use App\Models\KhachHang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_change_password_to_current_password(): void
    {
        $password = 'Password1!';
        $customer = KhachHang::factory()->create([
            'mat_khau' => Hash::make($password),
        ]);

        Sanctum::actingAs($customer, ['customer']);

        $response = $this->putJson('/api/profile/change-password', [
            'current_password' => $password,
            'new_password' => $password,
            'new_password_confirmation' => $password,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['new_password'])
            ->assertJsonPath('errors.new_password.0', 'Mật khẩu mới không được trùng với mật khẩu hiện tại.');
    }
}
