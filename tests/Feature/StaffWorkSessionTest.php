<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use App\Models\ThongTinNhanVien;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffWorkSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_staff_login_creates_eight_hour_work_session_but_admin_login_does_not(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-03 08:00:00'));

        $staff = $this->createNhanVien('nhan_vien', 'Staff User', '0900000001', 'staff@example.com');
        $admin = $this->createNhanVien('admin', 'Admin User', '0900000002', 'admin@example.com');

        $staffResponse = $this->postJson('/api/login', [
            'tai_khoan' => '0900000001',
            'password' => 'Password@123',
            'kenh_dang_nhap' => 'he_thong',
        ]);

        $staffResponse
            ->assertOk()
            ->assertJsonPath('type', 'staff')
            ->assertJsonStructure(['token', 'expires_at', 'work_session']);

        $staffLog = NhanVienDangNhapLog::query()
            ->where('id_nhan_vien', $staff->id_nhan_vien)
            ->first();

        $this->assertNotNull($staffLog);
        $this->assertSame('2026-05-03 08:00:00', $staffLog->thoi_gian_dang_nhap->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-03 16:00:00', $staffLog->het_han_luc->format('Y-m-d H:i:s'));
        $this->assertNull($staffLog->thoi_gian_dang_xuat);
        $this->assertNotNull($staffLog->token_id);

        $adminResponse = $this->postJson('/api/login', [
            'tai_khoan' => '0900000002',
            'password' => 'Password@123',
        ]);

        $adminResponse
            ->assertOk()
            ->assertJsonPath('type', 'admin')
            ->assertJsonPath('expires_at', null)
            ->assertJsonPath('work_session', null);

        $this->assertDatabaseMissing('nhan_vien_dang_nhap_logs', [
            'id_nhan_vien' => $admin->id_nhan_vien,
        ]);
    }

    public function test_staff_logout_closes_current_work_session(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-03 08:00:00'));

        $staff = $this->createNhanVien('nhan_vien', 'Staff User', '0900000003', 'logout.staff@example.com');
        $token = $this->postJson('/api/login', [
            'tai_khoan' => '0900000003',
            'password' => 'Password@123',
            'kenh_dang_nhap' => 'he_thong',
        ])->json('token');

        Carbon::setTestNow(Carbon::parse('2026-05-03 12:30:00'));

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertOk();

        $log = NhanVienDangNhapLog::query()
            ->where('id_nhan_vien', $staff->id_nhan_vien)
            ->first();

        $this->assertSame('2026-05-03 12:30:00', $log->thoi_gian_dang_xuat->format('Y-m-d H:i:s'));
        $this->assertSame(16200, $log->thoi_luong_giay);
        $this->assertSame('manual', $log->ly_do_dang_xuat);
    }

    public function test_another_staff_cannot_login_while_a_staff_session_is_active(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-03 08:00:00'));

        $firstStaff = $this->createNhanVien('nhan_vien', 'First Staff', '0900000011', 'first.staff@example.com');
        $secondStaff = $this->createNhanVien('nhan_vien', 'Second Staff', '0900000012', 'second.staff@example.com');

        $firstResponse = $this->postJson('/api/login', [
            'tai_khoan' => '0900000011',
            'password' => 'Password@123',
            'kenh_dang_nhap' => 'he_thong',
        ]);

        $firstResponse->assertOk();

        $secondResponse = $this->postJson('/api/login', [
            'tai_khoan' => '0900000012',
            'password' => 'Password@123',
            'kenh_dang_nhap' => 'he_thong',
        ]);

        $secondResponse
            ->assertStatus(409)
            ->assertJsonPath('message', 'Hiện tại hệ thống đang có người đăng nhập');

        $this->assertDatabaseHas('nhan_vien_dang_nhap_logs', [
            'id_nhan_vien' => $firstStaff->id_nhan_vien,
            'thoi_gian_dang_xuat' => null,
            'dang_hoat_dong' => true,
        ]);

        $this->assertDatabaseMissing('nhan_vien_dang_nhap_logs', [
            'id_nhan_vien' => $secondStaff->id_nhan_vien,
        ]);
    }

    public function test_expired_staff_session_is_closed_before_allowing_another_staff_login(): void
    {
        $firstStaff = $this->createNhanVien('nhan_vien', 'Expired Staff', '0900000013', 'expired.staff@example.com');
        $secondStaff = $this->createNhanVien('nhan_vien', 'Next Staff', '0900000014', 'next.staff@example.com');

        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $firstStaff->id_nhan_vien,
            'thoi_gian_dang_nhap' => Carbon::parse('2026-05-03 08:00:00'),
            'het_han_luc' => Carbon::parse('2026-05-03 16:00:00'),
            'dang_hoat_dong' => true,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-03 16:05:00'));

        $this->postJson('/api/login', [
            'tai_khoan' => '0900000014',
            'password' => 'Password@123',
            'kenh_dang_nhap' => 'he_thong',
        ])->assertOk();

        $expiredLog = NhanVienDangNhapLog::query()
            ->where('id_nhan_vien', $firstStaff->id_nhan_vien)
            ->first();

        $newLog = NhanVienDangNhapLog::query()
            ->where('id_nhan_vien', $secondStaff->id_nhan_vien)
            ->first();

        $this->assertSame('2026-05-03 16:00:00', $expiredLog->thoi_gian_dang_xuat->format('Y-m-d H:i:s'));
        $this->assertSame('expired', $expiredLog->ly_do_dang_xuat);
        $this->assertFalse((bool) $expiredLog->dang_hoat_dong);
        $this->assertNotNull($newLog);
        $this->assertTrue((bool) $newLog->dang_hoat_dong);
    }

    public function test_admin_can_view_staff_work_sessions_and_expired_session_is_capped_at_eight_hours(): void
    {
        $admin = $this->createNhanVien('admin', 'Admin User', '0900000004', 'admin.history@example.com');
        $staff = $this->createNhanVien('nhan_vien', 'Staff User', '0900000005', 'history.staff@example.com');

        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'thoi_gian_dang_nhap' => Carbon::parse('2026-05-01 08:00:00'),
            'het_han_luc' => Carbon::parse('2026-05-01 16:00:00'),
        ]);

        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'thoi_gian_dang_nhap' => Carbon::parse('2026-04-30 09:00:00'),
            'thoi_gian_dang_xuat' => Carbon::parse('2026-04-30 12:00:00'),
            'het_han_luc' => Carbon::parse('2026-04-30 17:00:00'),
            'thoi_luong_giay' => 10800,
            'ly_do_dang_xuat' => 'manual',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-02 09:00:00'));
        Sanctum::actingAs($admin, ['admin']);

        $this->getJson("/api/admin/nhan-viens/{$staff->id_nhan_vien}/work-sessions?date=2026-05-01")
            ->assertOk()
            ->assertJsonPath('tracking_enabled', true)
            ->assertJsonPath('filter.type', 'date')
            ->assertJsonPath('summary.tong_phien', 1)
            ->assertJsonPath('summary.tong_gio_lam', 8)
            ->assertJsonPath('sessions.0.trang_thai', 'tu_het_han')
            ->assertJsonPath('sessions.0.ly_do_dang_xuat', 'expired')
            ->assertJsonPath('sessions.0.so_gio_lam', 8);

        $expiredLog = NhanVienDangNhapLog::query()
            ->where('id_nhan_vien', $staff->id_nhan_vien)
            ->whereDate('thoi_gian_dang_nhap', '2026-05-01')
            ->first();

        $this->assertSame('2026-05-01 16:00:00', $expiredLog->thoi_gian_dang_xuat->format('Y-m-d H:i:s'));
        $this->assertSame(28800, $expiredLog->thoi_luong_giay);
        $this->assertSame('expired', $expiredLog->ly_do_dang_xuat);
    }

    public function test_admin_can_view_orders_and_products_sold_by_staff_on_work_session_date(): void
    {
        $admin = $this->createNhanVien('admin', 'Admin User', '0900000006', 'admin.sales@example.com');
        $staff = $this->createNhanVien('nhan_vien', 'Staff User', '0900000007', 'sales.staff@example.com');
        $otherStaff = $this->createNhanVien('nhan_vien', 'Other Staff', '0900000008', 'other.staff@example.com');
        $customer = KhachHang::factory()->create([
            'ten_khach_hang' => 'Nguyen Van A',
            'so_dien_thoai' => '0911111111',
        ]);
        $thuoc = Thuoc::factory()->create([
            'ma_thuoc' => 'THSALE001',
            'ten_thuoc' => 'Paracetamol 500',
            'ham_luong' => '500mg',
            'don_vi_tinh' => 'viên',
        ]);
        $loThuoc = LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'so_lo' => 'LOT-SALE-001',
        ]);

        $hoaDon = HoaDon::factory()->create([
            'ma_hoa_don' => 'HDSTAFF001',
            'id_khach_hang' => $customer->id_khach_hang,
            'id_nhan_vien' => $staff->id_nhan_vien,
            'tong_tien' => 100000,
            'giam_gia' => 0,
            'thue_vat' => 10000,
            'tien_thanh_toan' => 110000,
            'ngay_ban' => Carbon::parse('2026-05-03 10:30:00'),
        ]);

        ChiTietHoaDon::factory()->create([
            'id_hoa_don' => $hoaDon->id_hoa_don,
            'id_lo' => $loThuoc->id_lo,
            'don_vi_ban' => 'viên',
            'so_luong' => 2,
            'gia_ban' => 50000,
            'thanh_tien' => 100000,
        ]);

        HoaDon::factory()->create([
            'id_khach_hang' => $customer->id_khach_hang,
            'id_nhan_vien' => $staff->id_nhan_vien,
            'ngay_ban' => Carbon::parse('2026-05-04 10:30:00'),
        ]);

        HoaDon::factory()->create([
            'id_khach_hang' => $customer->id_khach_hang,
            'id_nhan_vien' => $otherStaff->id_nhan_vien,
            'ngay_ban' => Carbon::parse('2026-05-03 11:30:00'),
        ]);

        Sanctum::actingAs($admin, ['admin']);

        $this->getJson("/api/admin/nhan-viens/{$staff->id_nhan_vien}/sales-by-date?date=2026-05-03")
            ->assertOk()
            ->assertJsonPath('filter.date', '2026-05-03')
            ->assertJsonPath('summary.tong_don_hang', 1)
            ->assertJsonPath('summary.tong_san_pham', 2)
            ->assertJsonPath('summary.tong_doanh_thu', 110000)
            ->assertJsonPath('orders.0.ma_hoa_don', 'HDSTAFF001')
            ->assertJsonPath('orders.0.khach_hang.ten_khach_hang', 'Nguyen Van A')
            ->assertJsonPath('orders.0.items.0.ten_thuoc', 'Paracetamol 500')
            ->assertJsonPath('orders.0.items.0.so_lo', 'LOT-SALE-001')
            ->assertJsonPath('orders.0.items.0.so_luong', 2)
            ->assertJsonPath('orders.0.items.0.thanh_tien', 100000);
    }

    private function createNhanVien(string $roleName, string $name, string $phone, string $email): NhanVien
    {
        $role = VaiTro::firstOrCreate(['ten_vai_tro' => $roleName], ['mo_ta' => $roleName]);
        $bangCap = BangCap::factory()->create();
        $nhanVien = NhanVien::factory()->create([
            'ten_dang_nhap' => $phone,
            'mat_khau' => 'Password@123',
            'ho_ten' => $name,
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $bangCap->id_bang_cap,
            'trang_thai' => 'active',
        ]);

        ThongTinNhanVien::factory()->create([
            'id_nhan_vien' => $nhanVien->id_nhan_vien,
            'so_dien_thoai' => $phone,
            'email' => $email,
        ]);

        return $nhanVien->load('vaiTro', 'thongTinNhanVien');
    }
}
