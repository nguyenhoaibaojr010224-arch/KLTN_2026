<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\NhanVien;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HoaDonApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_customer_can_login_with_their_own_credentials(): void
    {
        $admin = $this->createNhanVien('admin_user', 'admin');
        $customer = KhachHang::factory()->create();

        $adminResponse = $this->postJson('/api/login', [
            'ten_dang_nhap' => $admin->ten_dang_nhap,
            'password' => 'password',
        ]);

        $adminResponse
            ->assertOk()
            ->assertJsonPath('type', 'admin');

        $customerResponse = $this->postJson('/api/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $customerResponse
            ->assertOk()
            ->assertJsonPath('type', 'customer');
    }

    public function test_staff_can_create_and_search_hoa_don(): void
    {
        $staff = $this->createNhanVien('staff_user', 'nhan_vien');
        $khachHang = KhachHang::factory()->create();

        Sanctum::actingAs($staff, ['staff']);

        $createResponse = $this->postJson('/api/hoa-dons', [
            'ma_hoa_don' => 'HDTEST0001',
            'id_khach_hang' => $khachHang->id_khach_hang,
            'tong_tien' => 500000,
            'giam_gia' => 50000,
            'ngay_ban' => now()->toDateTimeString(),
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.ma_hoa_don', 'HDTEST0001')
            ->assertJsonPath('data.tien_thanh_toan', '450000.00');

        $searchResponse = $this->getJson('/api/hoa-dons/search?q=HDTEST');

        $searchResponse
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_view_statistics(): void
    {
        $admin = $this->createNhanVien('admin_stats', 'admin');
        $staff = $this->createNhanVien('staff_stats', 'nhan_vien');
        $khachHang = KhachHang::factory()->create();

        HoaDon::factory()->create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'id_khach_hang' => $khachHang->id_khach_hang,
            'tong_tien' => 1000000,
            'giam_gia' => 100000,
            'tien_thanh_toan' => 900000,
            'ngay_ban' => now()->subDay(),
        ]);

        HoaDon::factory()->create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'id_khach_hang' => $khachHang->id_khach_hang,
            'tong_tien' => 500000,
            'giam_gia' => 0,
            'tien_thanh_toan' => 500000,
            'ngay_ban' => now(),
        ]);

        Sanctum::actingAs($admin, ['admin']);

        $response = $this->getJson('/api/admin/hoa-dons/statistics');

        $response
            ->assertOk()
            ->assertJsonPath('data.tong_quan.tong_so_hoa_don', 2)
            ->assertJsonPath('data.tong_quan.tong_tien_thanh_toan', 1400000);
    }

    public function test_customer_cannot_access_invoice_routes(): void
    {
        $customer = KhachHang::factory()->create();

        Sanctum::actingAs($customer, ['customer']);

        $response = $this->getJson('/api/hoa-dons');

        $response->assertStatus(403);
    }

    private function createNhanVien(string $username, string $roleName): NhanVien
    {
        $role = VaiTro::firstOrCreate(['ten_vai_tro' => $roleName], ['mo_ta' => $roleName]);
        $bangCap = BangCap::factory()->create();

        return NhanVien::factory()->create([
            'ten_dang_nhap' => $username,
            'mat_khau' => 'password',
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $bangCap->id_bang_cap,
            'trang_thai' => 'active',
        ]);
    }
}
