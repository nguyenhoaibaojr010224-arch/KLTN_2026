<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\KhachHang;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use App\Models\ThongTinNhanVien;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerCheckoutEmployeeAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_checkout_is_attributed_to_latest_logged_in_system_employee(): void
    {
        Mail::fake();

        $defaultStaff = $this->createNhanVien('staff', 'nhan_vien', 'Staff User');
        $loggedInStaff = $this->createNhanVien('0905159357', 'nhan_vien', 'Le Vu Hoang Duy');
        $customer = KhachHang::factory()->create();
        $thuoc = Thuoc::factory()->create([
            'ten_thuoc' => 'Hapacol 250',
            'don_vi_tinh' => 'hop',
            'don_vi_co_so' => 'hop',
            'he_so_quy_doi' => 1,
            'gia_ban' => 231000,
            'trang_thai' => 'con ban',
        ]);
        LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'don_vi_nhap' => 'hop',
            'don_vi_co_so' => 'hop',
            'so_luong_nhap' => 20,
            'so_luong_nhap_goc' => 20,
            'so_luong_con' => 20,
            'he_so_quy_doi_nhap' => 1,
            'gia_nhap' => 100000,
            'gia_nhap_quy_doi' => 100000,
        ]);

        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $defaultStaff->id_nhan_vien,
            'thoi_gian_dang_nhap' => now()->subHour(),
        ]);
        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $loggedInStaff->id_nhan_vien,
            'thoi_gian_dang_nhap' => now(),
        ]);

        Sanctum::actingAs($customer, ['customer']);

        $response = $this->postJson('/api/checkout/orders', [
            'items' => [
                [
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'so_luong' => 1,
                    'don_vi' => 'hop',
                ],
            ],
            'phuong_thuc_thanh_toan' => 'cod',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('hoa_dons', [
            'id_khach_hang' => $customer->id_khach_hang,
            'id_nhan_vien' => $loggedInStaff->id_nhan_vien,
        ]);
        $this->assertDatabaseMissing('hoa_dons', [
            'id_khach_hang' => $customer->id_khach_hang,
            'id_nhan_vien' => $defaultStaff->id_nhan_vien,
        ]);

        Sanctum::actingAs($loggedInStaff, ['staff']);

        $dashboardResponse = $this->getJson('/api/dashboard/staff-performance?date=' . now()->toDateString() . '&month=' . now()->format('Y-m'));

        $dashboardResponse
            ->assertOk()
            ->assertJsonPath('data.hom_nay.nhan_viens.0.id_nhan_vien', $loggedInStaff->id_nhan_vien)
            ->assertJsonPath('data.hom_nay.nhan_viens.0.ho_ten', 'Le Vu Hoang Duy')
            ->assertJsonPath('data.hom_nay.nhan_viens.0.so_hoa_don', 1);
    }

    private function createNhanVien(string $username, string $roleName, string $name): NhanVien
    {
        $role = VaiTro::firstOrCreate(['ten_vai_tro' => $roleName], ['mo_ta' => $roleName]);
        $bangCap = BangCap::factory()->create();
        $nhanVien = NhanVien::factory()->create([
            'ten_dang_nhap' => $username,
            'mat_khau' => 'password',
            'ho_ten' => $name,
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $bangCap->id_bang_cap,
            'trang_thai' => 'active',
        ]);

        ThongTinNhanVien::factory()->create([
            'id_nhan_vien' => $nhanVien->id_nhan_vien,
        ]);

        return $nhanVien->load('thongTinNhanVien');
    }
}
