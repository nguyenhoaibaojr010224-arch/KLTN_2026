<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\KhachHang;
use App\Models\NhanVien;
use App\Models\ThongTinNhanVien;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginWithEmailOrPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_login_with_email_or_phone_number(): void
    {
        KhachHang::factory()->create([
            'so_dien_thoai' => '0901234567',
            'email' => 'login.customer@example.com',
            'mat_khau' => 'Password@123',
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/login', [
            'so_dien_thoai' => 'login.customer@example.com',
            'password' => 'Password@123',
        ])
            ->assertOk()
            ->assertJsonPath('type', 'customer')
            ->assertJsonPath('user.email', 'login.customer@example.com')
            ->assertJsonStructure(['token']);

        $this->postJson('/api/login', [
            'so_dien_thoai' => '0901234567',
            'password' => 'Password@123',
        ])
            ->assertOk()
            ->assertJsonPath('type', 'customer')
            ->assertJsonPath('user.so_dien_thoai', '0901234567')
            ->assertJsonStructure(['token']);
    }

    public function test_staff_can_login_with_email_or_phone_number(): void
    {
        $staffRole = VaiTro::factory()->create([
            'ten_vai_tro' => 'nhan_vien',
        ]);
        $bangCap = BangCap::factory()->create();
        $staff = NhanVien::factory()->create([
            'id_vai_tro' => $staffRole->id_vai_tro,
            'id_bang_cap' => $bangCap->id_bang_cap,
            'mat_khau' => 'Password@123',
            'trang_thai' => 'active',
        ]);

        ThongTinNhanVien::factory()->create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'so_dien_thoai' => '0907654321',
            'email' => 'staff.login@example.com',
        ]);

        $this->postJson('/api/login', [
            'so_dien_thoai' => 'staff.login@example.com',
            'password' => 'Password@123',
        ])
            ->assertStatus(428)
            ->assertJsonPath('staff_channel_required', true);

        $this->postJson('/api/login', [
            'so_dien_thoai' => 'staff.login@example.com',
            'password' => 'Password@123',
            'kenh_dang_nhap' => 'he_thong',
        ])
            ->assertOk()
            ->assertJsonPath('type', 'staff')
            ->assertJsonPath('user.email', 'staff.login@example.com')
            ->assertJsonStructure(['token']);

        \App\Models\NhanVienDangNhapLog::query()->update([
            'thoi_gian_dang_xuat' => now(),
            'dang_hoat_dong' => null,
        ]);

        $this->postJson('/api/login', [
            'so_dien_thoai' => '0907654321',
            'password' => 'Password@123',
            'kenh_dang_nhap' => 'he_thong',
        ])
            ->assertOk()
            ->assertJsonPath('type', 'staff')
            ->assertJsonPath('user.so_dien_thoai', '0907654321')
            ->assertJsonStructure(['token']);
    }
}
