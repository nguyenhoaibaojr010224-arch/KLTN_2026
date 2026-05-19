<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use App\Models\ThanhToan;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminOrderNoSystemStaffNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_order_notification_warns_admin_when_customer_orders_without_system_staff_logged_in(): void
    {
        Carbon::setTestNow('2026-05-19 10:00:00');
        $admin = $this->createEmployee('admin');
        $staff = $this->createEmployee('staff');
        Sanctum::actingAs($admin, ['*']);

        $invoice = $this->createPendingSystemInvoice($staff);

        $response = $this->getJson('/api/hoa-dons/pending-notifications');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id_hoa_don', $invoice->id_hoa_don)
            ->assertJsonPath('data.0.can_canh_bao_chua_co_nhan_vien_he_thong', true)
            ->assertJsonPath('data.0.loai_thong_bao', 'chua_co_nhan_vien_he_thong');
        $this->assertStringContainsString(
            'Chưa có nhân viên đăng nhập hệ thống',
            $response->json('data.0.noi_dung_thong_bao')
        );

        Carbon::setTestNow();
    }

    public function test_pending_order_notification_still_warns_admin_when_only_admin_is_logged_in(): void
    {
        Carbon::setTestNow('2026-05-19 10:00:00');
        $admin = $this->createEmployee('admin');
        $staff = $this->createEmployee('staff');
        Sanctum::actingAs($admin, ['*']);
        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $admin->id_nhan_vien,
            'kenh_dang_nhap' => 'he_thong',
            'thoi_gian_dang_nhap' => now()->subHour(),
            'thoi_gian_dang_xuat' => null,
            'het_han_luc' => now()->addHours(8),
            'dang_hoat_dong' => true,
        ]);
        $invoice = $this->createPendingSystemInvoice($staff);

        $response = $this->getJson('/api/hoa-dons/pending-notifications');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id_hoa_don', $invoice->id_hoa_don)
            ->assertJsonPath('data.0.can_canh_bao_chua_co_nhan_vien_he_thong', true);

        Carbon::setTestNow();
    }

    public function test_pending_order_notification_does_not_warn_when_staff_was_logged_in_at_order_time(): void
    {
        Carbon::setTestNow('2026-05-19 10:00:00');
        $admin = $this->createEmployee('admin');
        $staff = $this->createEmployee('staff');
        Sanctum::actingAs($admin, ['*']);
        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'kenh_dang_nhap' => 'he_thong',
            'thoi_gian_dang_nhap' => now()->subHour(),
            'thoi_gian_dang_xuat' => null,
            'het_han_luc' => now()->addHours(8),
            'dang_hoat_dong' => true,
        ]);
        $invoice = $this->createPendingSystemInvoice($staff);

        $response = $this->getJson('/api/hoa-dons/pending-notifications');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id_hoa_don', $invoice->id_hoa_don)
            ->assertJsonPath('data.0.can_canh_bao_chua_co_nhan_vien_he_thong', false)
            ->assertJsonPath('data.0.loai_thong_bao', 'don_hang_moi');

        Carbon::setTestNow();
    }

    public function test_pending_order_notification_warns_when_staff_session_is_not_marked_active(): void
    {
        Carbon::setTestNow('2026-05-19 10:00:00');
        $admin = $this->createEmployee('admin');
        $staff = $this->createEmployee('staff');
        Sanctum::actingAs($admin, ['*']);
        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'kenh_dang_nhap' => 'he_thong',
            'thoi_gian_dang_nhap' => now()->subHour(),
            'thoi_gian_dang_xuat' => null,
            'het_han_luc' => now()->addHours(8),
            'dang_hoat_dong' => null,
        ]);
        $invoice = $this->createPendingSystemInvoice($staff);

        $response = $this->getJson('/api/hoa-dons/pending-notifications');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id_hoa_don', $invoice->id_hoa_don)
            ->assertJsonPath('data.0.can_canh_bao_chua_co_nhan_vien_he_thong', true);

        Carbon::setTestNow();
    }

    private function createEmployee(string $roleName): NhanVien
    {
        $role = VaiTro::factory()->create(['ten_vai_tro' => $roleName]);
        $degree = BangCap::factory()->create();

        return NhanVien::factory()->create([
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $degree->id_bang_cap,
            'trang_thai' => 'active',
        ]);
    }

    private function createPendingSystemInvoice(NhanVien $staff): HoaDon
    {
        $customer = KhachHang::factory()->create(['ten_khach_hang' => 'Nguyen Bao']);
        $thuoc = Thuoc::factory()->create();
        $lot = LoThuoc::factory()->create(['id_thuoc' => $thuoc->ma_thuoc]);
        $invoice = HoaDon::factory()->create([
            'ma_hoa_don' => 'HDNOLOGIN' . fake()->unique()->numerify('###'),
            'id_khach_hang' => $customer->id_khach_hang,
            'id_nhan_vien' => $staff->id_nhan_vien,
            'kenh_ban' => 'he_thong',
            'trang_thai_xu_ly' => 'cho_thanh_toan',
            'ngay_ban' => now()->subMinutes(5),
        ]);
        ChiTietHoaDon::factory()->create([
            'id_hoa_don' => $invoice->id_hoa_don,
            'id_lo' => $lot->id_lo,
        ]);
        ThanhToan::factory()->create([
            'id_hoa_don' => $invoice->id_hoa_don,
            'phuong_thuc' => 'tien_mat',
            'trang_thai' => 'pending',
        ]);

        return $invoice;
    }
}
