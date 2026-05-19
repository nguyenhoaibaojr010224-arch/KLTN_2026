<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\ChiTietPhieuNhap;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\PhieuNhap;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpiredInventoryAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_lot_with_remaining_stock_is_reported_in_inventory_alerts(): void
    {
        Carbon::setTestNow('2026-05-18 09:00:00');
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['*']);
        $thuoc = Thuoc::factory()->create();
        $expiredLot = LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'han_su_dung' => '2026-04-18',
            'so_luong_con' => 12,
        ]);

        $response = $this->getJson('/api/lo-thuocs/alerts?days=30');

        $response
            ->assertOk()
            ->assertJsonPath('data.lo_sap_het_han.0.id_lo', $expiredLot->id_lo)
            ->assertJsonPath('data.lo_sap_het_han.0.da_het_han', true)
            ->assertJsonPath('data.lo_sap_het_han.0.so_ngay_qua_han', 30);

        Carbon::setTestNow();
    }

    public function test_admin_can_dispose_expired_lot_remaining_stock(): void
    {
        Carbon::setTestNow('2026-05-18 09:00:00');
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['*']);
        $thuoc = Thuoc::factory()->create();
        $expiredLot = LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'han_su_dung' => '2026-04-18',
            'so_luong_con' => 12,
        ]);

        $response = $this->postJson("/api/admin/lo-thuocs/{$expiredLot->id_lo}/dispose", [
            'ly_do' => 'Huy lo het han',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('so_luong_da_huy', 12)
            ->assertJsonPath('data.so_luong_con', 0);
        $this->assertDatabaseHas('lo_thuocs', [
            'id_lo' => $expiredLot->id_lo,
            'so_luong_con' => 0,
        ]);

        Carbon::setTestNow();
    }

    public function test_admin_can_delete_expired_lot_without_losing_receipt_history(): void
    {
        Carbon::setTestNow('2026-05-18 09:00:00');
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['*']);
        $thuoc = Thuoc::factory()->create();
        $expiredLot = LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'han_su_dung' => '2026-04-18',
            'so_luong_con' => 12,
        ]);
        $receipt = PhieuNhap::factory()->create(['id_nhan_vien' => $admin->id_nhan_vien]);
        ChiTietPhieuNhap::factory()->create([
            'id_phieu_nhap' => $receipt->id_phieu_nhap,
            'id_lo' => $expiredLot->id_lo,
        ]);

        $response = $this->deleteJson("/api/admin/lo-thuocs/{$expiredLot->id_lo}");

        $response
            ->assertOk()
            ->assertJsonPath('so_luong_da_huy', 12);
        $this->assertSoftDeleted('lo_thuocs', ['id_lo' => $expiredLot->id_lo]);
        $this->assertDatabaseHas('chi_tiet_phieu_nhaps', [
            'id_phieu_nhap' => $receipt->id_phieu_nhap,
            'id_lo' => $expiredLot->id_lo,
        ]);

        Carbon::setTestNow();
    }

    public function test_admin_can_delete_medicine_only_when_stock_is_zero(): void
    {
        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['*']);
        $thuocWithStock = Thuoc::factory()->create();
        LoThuoc::factory()->create([
            'id_thuoc' => $thuocWithStock->ma_thuoc,
            'so_luong_con' => 5,
        ]);

        $this->deleteJson("/api/admin/thuocs/{$thuocWithStock->ma_thuoc}")
            ->assertStatus(409)
            ->assertJsonPath('data.so_luong_con', 5);

        $thuocOutOfStock = Thuoc::factory()->create();
        $emptyLot = LoThuoc::factory()->create([
            'id_thuoc' => $thuocOutOfStock->ma_thuoc,
            'so_luong_con' => 0,
        ]);
        $receipt = PhieuNhap::factory()->create(['id_nhan_vien' => $admin->id_nhan_vien]);
        ChiTietPhieuNhap::factory()->create([
            'id_phieu_nhap' => $receipt->id_phieu_nhap,
            'id_lo' => $emptyLot->id_lo,
        ]);

        $this->deleteJson("/api/admin/thuocs/{$thuocOutOfStock->ma_thuoc}")
            ->assertOk();

        $this->assertSoftDeleted('thuocs', ['ma_thuoc' => $thuocOutOfStock->ma_thuoc]);
        $this->assertSoftDeleted('lo_thuocs', ['id_lo' => $emptyLot->id_lo]);
        $this->assertDatabaseHas('chi_tiet_phieu_nhaps', [
            'id_phieu_nhap' => $receipt->id_phieu_nhap,
            'id_lo' => $emptyLot->id_lo,
        ]);
    }

    private function createAdmin(): NhanVien
    {
        $role = VaiTro::factory()->create(['ten_vai_tro' => 'admin']);
        $degree = BangCap::factory()->create();

        return NhanVien::factory()->create([
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $degree->id_bang_cap,
            'trang_thai' => 'active',
        ]);
    }
}
