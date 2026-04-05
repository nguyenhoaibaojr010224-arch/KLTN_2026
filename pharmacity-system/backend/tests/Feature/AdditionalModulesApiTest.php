<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\KhachHang;
use App\Models\KhuyenMai;
use App\Models\LoaiThuoc;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\NhaSanXuat;
use App\Models\ThongTinNhanVien;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdditionalModulesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_search_thong_tin_nhan_vien(): void
    {
        $admin = $this->createNhanVien('admin_profile', 'admin');
        $staff = $this->createNhanVien('staff_profile', 'nhan_vien', false);

        Sanctum::actingAs($admin, ['admin']);

        $createResponse = $this->postJson('/api/admin/thong-tin-nhan-viens', [
            'id_nhan_vien' => $staff->id_nhan_vien,
            'so_dien_thoai' => '0912345678',
            'email' => 'staff.profile@example.com',
            'dia_chi' => '123 Duong ABC, Quan 1',
            'ngay_sinh' => now()->subYears(25)->format('Y-m-d'),
            'ngay_vao_lam' => now()->subYears(2)->format('Y-m-d'),
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.id_nhan_vien', $staff->id_nhan_vien);

        $searchResponse = $this->getJson('/api/admin/thong-tin-nhan-viens/search?q=0912345678');

        $searchResponse
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_staff_can_create_phieu_nhap_and_detail_with_recalculated_total(): void
    {
        $staff = $this->createNhanVien('staff_phieu', 'nhan_vien');
        $loThuoc = LoThuoc::factory()->create([
            'so_luong_nhap' => 100,
            'so_luong_con' => 100,
            'gia_nhap' => 20000,
        ]);

        Sanctum::actingAs($staff, ['staff']);

        $phieuNhapResponse = $this->postJson('/api/phieu-nhaps', [
            'id_nha_san_xuat' => NhaSanXuat::query()->firstOrCreate(['ten_nha_san_xuat' => 'NSX Test'])->id,
            'ngay_nhap' => now()->toDateTimeString(),
        ]);

        $phieuNhapResponse->assertCreated();
        $phieuNhapId = $phieuNhapResponse->json('data.id_phieu_nhap');

        $detailResponse = $this->postJson("/api/phieu-nhaps/{$phieuNhapId}/chi-tiets", [
            'id_lo' => $loThuoc->id_lo,
            'so_luong' => 20,
            'gia_nhap' => 25000,
        ]);

        $detailResponse->assertCreated();

        $showResponse = $this->getJson("/api/phieu-nhaps/{$phieuNhapId}");

        $showResponse
            ->assertOk()
            ->assertJsonPath('data.tong_tien', '500000.00');
    }

    public function test_staff_can_add_invoice_detail_and_payment(): void
    {
        $staff = $this->createNhanVien('staff_sale', 'nhan_vien');
        $khachHang = KhachHang::factory()->create();
        $thuoc = Thuoc::factory()->create([
            'gia_ban' => 50000,
        ]);
        $loThuoc = LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'so_luong_nhap' => 50,
            'so_luong_con' => 50,
            'gia_nhap' => 30000,
        ]);

        Sanctum::actingAs($staff, ['staff']);

        $hoaDonResponse = $this->postJson('/api/hoa-dons', [
            'id_khach_hang' => $khachHang->id_khach_hang,
            'tong_tien' => 0,
            'giam_gia' => 0,
        ]);

        $hoaDonResponse->assertCreated();
        $hoaDonId = $hoaDonResponse->json('data.id_hoa_don');

        $chiTietResponse = $this->postJson("/api/hoa-dons/{$hoaDonId}/chi-tiets", [
            'id_lo' => $loThuoc->id_lo,
            'so_luong' => 2,
        ]);

        $chiTietResponse
            ->assertCreated()
            ->assertJsonPath('data.thanh_tien', '100000.00');

        $paymentResponse = $this->postJson('/api/thanh-toans', [
            'id_hoa_don' => $hoaDonId,
            'phuong_thuc' => 'tien_mat',
            'so_tien' => 100000,
        ]);

        $paymentResponse->assertCreated();
        $this->assertDatabaseHas('thanh_toan', [
            'id_hoa_don' => $hoaDonId,
        ]);
    }

    public function test_public_can_request_and_verify_email_token(): void
    {
        $khachHang = KhachHang::factory()->create([
            'email_verified' => false,
            'email_verified_at' => null,
        ]);

        $requestResponse = $this->postJson('/api/email-verifications/request', [
            'email' => $khachHang->email,
        ]);

        $requestResponse->assertCreated();
        $token = $requestResponse->json('data.token');

        $verifyResponse = $this->getJson("/api/email-verifications/verify/{$token}");

        $verifyResponse->assertOk();
        $this->assertDatabaseHas('khach_hangs', [
            'id_khach_hang' => $khachHang->id_khach_hang,
            'email_verified' => 1,
        ]);
    }

    public function test_public_can_request_and_reset_password(): void
    {
        $khachHang = KhachHang::factory()->create([
            'mat_khau' => 'oldpassword',
        ]);

        $requestResponse = $this->postJson('/api/password-resets/request', [
            'email' => $khachHang->email,
        ]);

        $requestResponse->assertCreated();
        $token = $requestResponse->json('data.token');

        $resetResponse = $this->postJson('/api/password-resets/reset', [
            'email' => $khachHang->email,
            'token' => $token,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $resetResponse->assertOk();
        $khachHang->refresh();
        $this->assertTrue(Hash::check('newpassword', $khachHang->mat_khau));
    }

    public function test_public_can_register_and_browse_catalog(): void
    {
        $thuoc = Thuoc::factory()->create([
            'ten_thuoc' => 'Vitamin C 1000',
            'gia_ban' => 120000,
        ]);

        LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'so_luong_nhap' => 30,
            'so_luong_con' => 18,
            'gia_nhap' => 80000,
        ]);

        $staff = $this->createNhanVien('staff_pricing', 'nhan_vien');

        KhuyenMai::factory()->create([
            'ma_thuoc' => $thuoc->ma_thuoc,
            'ten_khuyen_mai' => 'Deal vitamin',
            'nhan_hien_thi' => 'Giam 20%',
            'loai_ap_dung' => 'phan_tram',
            'gia_tri' => 20,
            'id_nhan_vien' => $staff->id_nhan_vien,
        ]);

        $registerResponse = $this->postJson('/api/register', [
            'ten_khach_hang' => 'Khach Hang Test',
            'so_dien_thoai' => '0987654321',
            'email' => 'customer.login@example.com',
            'dia_chi' => '456 Duong DEF, Quan 3',
            'mat_khau' => 'Password@1',
            'mat_khau_confirmation' => 'Password@1',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonPath('type', 'customer')
            ->assertJsonStructure(['token']);

        $catalogResponse = $this->getJson('/api/catalog/thuocs');

        $catalogResponse
            ->assertOk()
            ->assertJsonPath('data.0.ma_thuoc', $thuoc->ma_thuoc)
            ->assertJsonPath('data.0.so_luong_ton', 18)
            ->assertJsonPath('data.0.gia_niem_yet', 120000)
            ->assertJsonPath('data.0.gia_ban', 96000)
            ->assertJsonPath('data.0.co_khuyen_mai', true);
    }

    public function test_staff_can_update_price_and_manage_promotions(): void
    {
        $staff = $this->createNhanVien('staff_price_admin', 'nhan_vien');
        $thuoc = Thuoc::factory()->create([
            'gia_ban' => 100000,
        ]);

        Sanctum::actingAs($staff, ['staff']);

        $priceResponse = $this->putJson("/api/thuocs/{$thuoc->ma_thuoc}/price", [
            'gia_ban' => 145000,
        ]);

        $priceResponse
            ->assertOk()
            ->assertJsonPath('data.gia_ban', 145000);

        $promoResponse = $this->postJson('/api/khuyen-mais', [
            'ma_thuoc' => $thuoc->ma_thuoc,
            'ten_khuyen_mai' => 'Flash sale',
            'mo_ta' => 'Giam gia theo dot',
            'loai_ap_dung' => 'phan_tram',
            'gia_tri' => 10,
            'nhan_hien_thi' => 'Giam 10%',
            'ngay_bat_dau' => now()->subHour()->toDateTimeString(),
            'ngay_ket_thuc' => now()->addDays(2)->toDateTimeString(),
            'trang_thai' => 'active',
        ]);

        $promoResponse
            ->assertCreated()
            ->assertJsonPath('data.ma_thuoc', $thuoc->ma_thuoc);

        $catalogResponse = $this->getJson('/api/catalog/thuocs?q=' . $thuoc->ma_thuoc);

        $catalogResponse
            ->assertOk()
            ->assertJsonPath('data.0.gia_niem_yet', 145000)
            ->assertJsonPath('data.0.gia_ban', 130500)
            ->assertJsonPath('data.0.khuyen_mai.nhan_hien_thi', 'Giam 10%');

        $couponResponse = $this->postJson('/api/ma-giam-gias', [
            'ma_giam_gia' => 'FLASH-10',
            'ten_ma' => 'Flash sale don hang',
            'mo_ta' => 'Giam gia tren tong don',
            'loai_ap_dung' => 'phan_tram',
            'gia_tri' => 10,
            'gia_tri_don_toi_thieu' => 300000,
            'gioi_han_moi_khach' => 1,
            'ngay_bat_dau' => now()->subHour()->toDateTimeString(),
            'ngay_ket_thuc' => now()->addDays(2)->toDateTimeString(),
            'trang_thai' => 'active',
        ]);

        $couponResponse
            ->assertCreated()
            ->assertJsonPath('data.ma_giam_gia', 'FLASH-10');

        $khachHang = KhachHang::factory()->create();
        Sanctum::actingAs($khachHang);

        $validateCodeResponse = $this->postJson('/api/ma-giam-gias/validate-code', [
            'ma_giam_gia' => 'flash-10',
            'tong_tam_tinh' => 500000,
        ]);

        $validateCodeResponse
            ->assertOk()
            ->assertJsonPath('data.ma_giam_gia', 'FLASH-10')
            ->assertJsonPath('data.gia_tri_don_toi_thieu', 300000)
            ->assertJsonPath('data.gioi_han_moi_khach', 1)
            ->assertJsonPath('data.giam_gia_don_hang', 50000)
            ->assertJsonPath('data.tong_sau_giam', 450000);

        $redeemResponse = $this->postJson('/api/ma-giam-gias/redeem-code', [
            'ma_giam_gia' => 'FLASH-10',
            'tong_tam_tinh' => 500000,
        ]);

        $redeemResponse
            ->assertOk()
            ->assertJsonPath('data.so_lan_da_dung', 1);

        $this->assertDatabaseHas('ma_giam_gia_luot_dungs', [
            'ma_giam_gia_id' => $couponResponse->json('data.id'),
            'id_khach_hang' => $khachHang->id_khach_hang,
            'so_lan_su_dung' => 1,
        ]);

        $limitResponse = $this->postJson('/api/ma-giam-gias/validate-code', [
            'ma_giam_gia' => 'FLASH-10',
            'tong_tam_tinh' => 500000,
        ]);

        $limitResponse
            ->assertStatus(422)
            ->assertJsonPath('data.so_lan_da_dung', 1);
    }

    public function test_promotion_code_checks_minimum_order_value(): void
    {
        $staff = $this->createNhanVien('staff_min_promo', 'nhan_vien');
        $thuoc = Thuoc::factory()->create();
        $khachHang = KhachHang::factory()->create();

        Sanctum::actingAs($staff, ['staff']);

        $this->postJson('/api/ma-giam-gias', [
            'ma_giam_gia' => 'MIN-500',
            'ten_ma' => 'Ma toi thieu 500k',
            'mo_ta' => 'Giam gia theo don',
            'loai_ap_dung' => 'so_tien',
            'gia_tri' => 50000,
            'gia_tri_don_toi_thieu' => 500000,
            'gioi_han_moi_khach' => 2,
            'ngay_bat_dau' => now()->subHour()->toDateTimeString(),
            'ngay_ket_thuc' => now()->addDays(7)->toDateTimeString(),
            'trang_thai' => 'active',
        ])->assertCreated();

        Sanctum::actingAs($khachHang);

        $response = $this->postJson('/api/ma-giam-gias/validate-code', [
            'ma_giam_gia' => 'MIN-500',
            'tong_tam_tinh' => 200000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('data.gia_tri_don_toi_thieu', 500000);
    }

    private function createNhanVien(string $username, string $roleName, bool $withProfile = true): NhanVien
    {
        $role = VaiTro::firstOrCreate(['ten_vai_tro' => $roleName], ['mo_ta' => $roleName]);
        $bangCap = BangCap::factory()->create();
        $nhanVien = NhanVien::factory()->create([
            'ten_dang_nhap' => $username,
            'mat_khau' => 'password',
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $bangCap->id_bang_cap,
            'trang_thai' => 'active',
        ]);

        if ($withProfile) {
            ThongTinNhanVien::factory()->create([
                'id_nhan_vien' => $nhanVien->id_nhan_vien,
            ]);
        }

        return $nhanVien->load('thongTinNhanVien');
    }
}
