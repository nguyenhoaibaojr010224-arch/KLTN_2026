<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\HoaDon;
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
