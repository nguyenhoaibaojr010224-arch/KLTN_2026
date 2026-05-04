<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\KhachHang;
use App\Models\NhanVien;
use App\Models\ThongTinNhanVien;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_profile_update_returns_birth_date_without_timezone_shift(): void
    {
        $customer = KhachHang::factory()->create([
            'ten_khach_hang' => 'Khach Hang Cu',
            'so_dien_thoai' => '0902222333',
            'email' => 'customer.profile@example.com',
            'dia_chi' => '123 Nguyen Trai',
            'ngay_sinh' => '1995-03-18',
            'gioi_tinh' => 'Nam',
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($customer, ['customer']);

        $response = $this->putJson('/api/profile', [
            'ten_khach_hang' => 'Khach Hang Moi',
            'so_dien_thoai' => $customer->so_dien_thoai,
            'email' => $customer->email,
            'dia_chi' => $customer->dia_chi,
            'ngay_sinh' => '1995-03-18',
            'gioi_tinh' => $customer->gioi_tinh,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.ngay_sinh', '1995-03-18');
    }

    public function test_staff_profile_update_ignores_blank_birth_date(): void
    {
        $staffRole = VaiTro::factory()->create([
            'ten_vai_tro' => 'nhan_vien',
        ]);
        $bangCap = BangCap::factory()->create();
        $staff = NhanVien::factory()->create([
            'id_vai_tro' => $staffRole->id_vai_tro,
            'id_bang_cap' => $bangCap->id_bang_cap,
            'ho_ten' => 'Nhan Vien Cu',
            'trang_thai' => 'active',
        ]);
        $profile = ThongTinNhanVien::factory()->create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'so_dien_thoai' => '0901111222',
            'email' => 'staff.profile@example.com',
            'dia_chi' => '123 Nguyen Trai',
            'ngay_sinh' => '1995-03-18',
            'gioi_tinh' => 'Nam',
        ]);

        Sanctum::actingAs($staff, ['staff']);

        $response = $this->putJson('/api/profile', [
            'ho_ten' => 'Nhan Vien Moi',
            'so_dien_thoai' => $profile->so_dien_thoai,
            'email' => $profile->email,
            'dia_chi' => $profile->dia_chi,
            'ngay_sinh' => '',
            'gioi_tinh' => $profile->gioi_tinh,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.ngay_sinh', '1995-03-18');

        $this->assertSame('1995-03-18', $profile->refresh()->ngay_sinh->toDateString());
    }
}
