<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use App\Models\ThongTinNhanVien;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CounterSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_counter_sale_requires_counter_login_and_completes_invoice_immediately(): void
    {
        $staff = $this->createStaff();
        $thuoc = Thuoc::factory()->create([
            'ten_thuoc' => 'Vitamin C tai quay',
            'don_vi_tinh' => 'hop',
            'don_vi_co_so' => 'hop',
            'he_so_quy_doi' => 1,
            'gia_ban' => 100000,
            'trang_thai' => 'con ban',
        ]);
        $loThuoc = LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'don_vi_nhap' => 'hop',
            'don_vi_co_so' => 'hop',
            'so_luong_nhap' => 10,
            'so_luong_nhap_goc' => 10,
            'so_luong_con' => 5,
            'he_so_quy_doi_nhap' => 1,
        ]);

        Sanctum::actingAs($staff, ['staff']);

        $this->postJson('/api/ban-tai-quay/hoa-don', [
            'phuong_thuc_thanh_toan' => 'tien_mat',
            'items' => [
                [
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'so_luong' => 2,
                    'don_vi' => 'hop',
                ],
            ],
        ])->assertForbidden();

        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'kenh_dang_nhap' => 'tai_quay',
            'thoi_gian_dang_nhap' => now(),
            'het_han_luc' => now()->addHours(8),
            'dang_hoat_dong' => true,
        ]);

        $response = $this->postJson('/api/ban-tai-quay/hoa-don', [
            'phuong_thuc_thanh_toan' => 'tien_mat',
            'items' => [
                [
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'so_luong' => 2,
                    'don_vi' => 'hop',
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.kenh_ban', 'tai_quay')
            ->assertJsonPath('data.trang_thai_xu_ly', 'hoan_thanh');

        $this->assertDatabaseHas('hoa_dons', [
            'id_hoa_don' => $response->json('data.id_hoa_don'),
            'id_nhan_vien' => $staff->id_nhan_vien,
            'kenh_ban' => 'tai_quay',
            'trang_thai_xu_ly' => 'hoan_thanh',
            'tong_tien' => 200000,
            'thue_vat' => 20000,
            'tien_thanh_toan' => 220000,
        ]);

        $this->assertSame(3, (int) $loThuoc->fresh()->so_luong_con);
        $this->assertSame(1, HoaDon::query()->where('kenh_ban', 'tai_quay')->count());
    }

    public function test_counter_sale_can_attach_customer_by_phone_and_award_points(): void
    {
        $staff = $this->createStaff();
        $customer = KhachHang::factory()->create([
            'ten_khach_hang' => 'Khach Tich Diem',
            'so_dien_thoai' => '0909999000',
            'email' => 'counter.customer@example.com',
            'mat_khau' => 'Password@123',
            'diem_tich_luy' => 1000,
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
        $thuoc = Thuoc::factory()->create([
            'ten_thuoc' => 'Vitamin tich diem',
            'don_vi_tinh' => 'hop',
            'don_vi_co_so' => 'hop',
            'he_so_quy_doi' => 1,
            'gia_ban' => 100000,
            'trang_thai' => 'con ban',
        ]);
        LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'don_vi_nhap' => 'hop',
            'don_vi_co_so' => 'hop',
            'so_luong_nhap' => 10,
            'so_luong_nhap_goc' => 10,
            'so_luong_con' => 10,
            'he_so_quy_doi_nhap' => 1,
        ]);

        Sanctum::actingAs($staff, ['staff']);
        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'kenh_dang_nhap' => 'tai_quay',
            'thoi_gian_dang_nhap' => now(),
            'het_han_luc' => now()->addHours(8),
            'dang_hoat_dong' => true,
        ]);

        $lookupResponse = $this->postJson('/api/ban-tai-quay/khach-hang/so-dien-thoai', [
            'so_dien_thoai' => '0909999000',
        ]);

        $lookupResponse
            ->assertOk()
            ->assertJsonPath('data.khach_hang.id_khach_hang', $customer->id_khach_hang)
            ->assertJsonStructure(['data' => ['customer_token']]);

        $response = $this->postJson('/api/ban-tai-quay/hoa-don', [
            'customer_token' => $lookupResponse->json('data.customer_token'),
            'su_dung_diem' => true,
            'phuong_thuc_thanh_toan' => 'tien_mat',
            'items' => [
                [
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'so_luong' => 2,
                    'don_vi' => 'hop',
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.id_khach_hang', $customer->id_khach_hang)
            ->assertJsonPath('data.diem_da_su_dung', 1000)
            ->assertJsonPath('data.diem_da_cong', 200)
            ->assertJsonPath('data.khach_hang_tich_diem.diem_tich_luy', 200);

        $this->assertDatabaseHas('hoa_dons', [
            'id_hoa_don' => $response->json('data.id_hoa_don'),
            'id_khach_hang' => $customer->id_khach_hang,
            'id_nhan_vien' => $staff->id_nhan_vien,
            'kenh_ban' => 'tai_quay',
            'trang_thai_xu_ly' => 'hoan_thanh',
            'tong_tien' => 200000,
            'giam_gia_diem' => 10000,
            'thue_vat' => 19000,
            'tien_thanh_toan' => 209000,
            'diem_da_su_dung' => 1000,
            'diem_da_cong' => 200,
        ]);

        $this->assertSame(200, (int) $customer->fresh()->diem_tich_luy);
    }

    private function createStaff(): NhanVien
    {
        $role = VaiTro::firstOrCreate(['ten_vai_tro' => 'nhan_vien'], ['mo_ta' => 'nhan_vien']);
        $bangCap = BangCap::factory()->create();
        $staff = NhanVien::factory()->create([
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $bangCap->id_bang_cap,
            'mat_khau' => 'password',
            'trang_thai' => 'active',
        ]);

        ThongTinNhanVien::factory()->create([
            'id_nhan_vien' => $staff->id_nhan_vien,
        ]);

        return $staff->load('vaiTro', 'thongTinNhanVien');
    }
}
