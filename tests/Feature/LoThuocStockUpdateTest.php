<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoThuocStockUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_lot_metadata_without_changing_stock(): void
    {
        $admin = $this->createAdmin();
        $thuoc = Thuoc::factory()->create([
            'don_vi_tinh' => 'hop',
            'don_vi_co_so' => 'hop',
            'he_so_quy_doi' => 1,
        ]);
        $loThuoc = LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'so_lo' => 'LOT-EDIT-001',
            'ngay_san_xuat' => '2026-01-01',
            'han_su_dung' => '2028-01-01',
            'don_vi_nhap' => 'hop',
            'don_vi_co_so' => 'hop',
            'so_luong_nhap_goc' => 95,
            'so_luong_nhap' => 95,
            'so_luong_con' => 38,
            'he_so_quy_doi_nhap' => 1,
            'gia_nhap' => 65000,
            'gia_nhap_quy_doi' => 65000,
        ]);

        Sanctum::actingAs($admin, ['admin']);

        $this->putJson("/api/admin/lo-thuocs/{$loThuoc->id_lo}", [
            'so_lo' => 'LOT-EDIT-001B',
            'ngay_san_xuat' => '2026-02-01',
            'han_su_dung' => '2028-02-01',
            'don_vi_nhap' => 'hop',
            'gia_nhap' => 70000,
        ])->assertOk();

        $loThuoc->refresh();

        $this->assertSame('LOT-EDIT-001B', $loThuoc->so_lo);
        $this->assertSame(95, (int) $loThuoc->so_luong_nhap_goc);
        $this->assertSame(95, (int) $loThuoc->so_luong_nhap);
        $this->assertSame(38, (int) $loThuoc->so_luong_con);
        $this->assertSame(70000, (int) $loThuoc->gia_nhap);
    }

    public function test_admin_cannot_add_stock_by_updating_lot_directly(): void
    {
        $admin = $this->createAdmin();
        $thuoc = Thuoc::factory()->create([
            'don_vi_tinh' => 'hop',
            'don_vi_co_so' => 'hop',
            'he_so_quy_doi' => 1,
        ]);
        $loThuoc = LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'so_lo' => 'LOT-NO-DIRECT-STOCK',
            'don_vi_nhap' => 'hop',
            'don_vi_co_so' => 'hop',
            'so_luong_nhap_goc' => 95,
            'so_luong_nhap' => 95,
            'so_luong_con' => 38,
            'he_so_quy_doi_nhap' => 1,
        ]);

        Sanctum::actingAs($admin, ['admin']);

        $this->putJson("/api/admin/lo-thuocs/{$loThuoc->id_lo}", [
            'so_lo' => 'LOT-NO-DIRECT-STOCK',
            'ngay_san_xuat' => $loThuoc->ngay_san_xuat,
            'han_su_dung' => $loThuoc->han_su_dung,
            'don_vi_nhap' => 'hop',
            'gia_nhap' => $loThuoc->gia_nhap,
            'so_luong_nhap_them_goc' => 10,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('so_luong_nhap_them_goc');

        $loThuoc->refresh();

        $this->assertSame(95, (int) $loThuoc->so_luong_nhap_goc);
        $this->assertSame(95, (int) $loThuoc->so_luong_nhap);
        $this->assertSame(38, (int) $loThuoc->so_luong_con);
    }

    private function createAdmin(): NhanVien
    {
        $role = VaiTro::firstOrCreate(['ten_vai_tro' => 'admin'], ['mo_ta' => 'admin']);
        $bangCap = BangCap::factory()->create();

        return NhanVien::factory()->create([
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $bangCap->id_bang_cap,
            'trang_thai' => 'active',
        ])->load('vaiTro');
    }
}
