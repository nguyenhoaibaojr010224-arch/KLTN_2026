<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\LoThuoc;
use App\Mail\OrderRejectedMail;
use App\Models\NhanVien;
use App\Models\ThanhToan;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashOrderRejectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reject_unpaid_cash_order_and_restore_inventory(): void
    {
        Mail::fake();

        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, ['*']);

        $thuoc = Thuoc::factory()->create();
        $lot = LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'so_luong_con' => 3,
        ]);
        $order = HoaDon::factory()->create([
            'id_nhan_vien' => $admin->id_nhan_vien,
            'kenh_ban' => 'he_thong',
            'trang_thai_xu_ly' => 'da_xac_nhan',
        ]);
        ChiTietHoaDon::factory()->create([
            'id_hoa_don' => $order->id_hoa_don,
            'id_lo' => $lot->id_lo,
            'so_luong' => 7,
        ]);
        ThanhToan::factory()->create([
            'id_hoa_don' => $order->id_hoa_don,
            'phuong_thuc' => 'tien_mat',
            'trang_thai' => 'pending',
        ]);

        $response = $this->postJson("/api/hoa-dons/{$order->id_hoa_don}/reject", [
            'ly_do_tu_choi' => 'Khach hoan hang va khong thanh toan',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.trang_thai_xu_ly', 'tu_choi');

        $this->assertDatabaseHas('lo_thuocs', [
            'id_lo' => $lot->id_lo,
            'so_luong_con' => 10,
        ]);
        $this->assertDatabaseHas('hoa_dons', [
            'id_hoa_don' => $order->id_hoa_don,
            'trang_thai_xu_ly' => 'tu_choi',
            'ly_do_tu_choi' => 'Khach hoan hang va khong thanh toan',
        ]);
        $this->assertDatabaseHas('thanh_toan', [
            'id_hoa_don' => $order->id_hoa_don,
            'trang_thai' => 'canceled',
        ]);
        Mail::assertSent(OrderRejectedMail::class, function (OrderRejectedMail $mail) use ($order): bool {
            return $mail->hoaDon->id_hoa_don === $order->id_hoa_don
                && $mail->reason === 'Khach hoan hang va khong thanh toan';
        });
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
