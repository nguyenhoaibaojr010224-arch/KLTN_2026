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

class DashboardStaffPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_staff_performance_counts_revenue_only_for_employee_logged_in_that_day(): void
    {
        $staffUser = $this->createNhanVien('staff', 'nhan_vien', 'Staff User');
        $loggedInStaff = $this->createNhanVien('0905159357', 'nhan_vien', 'Le Vu Hoang Duy');
        $customer = KhachHang::factory()->create();
        $loThuoc = $this->createLot();
        $selectedDay = Carbon::parse('2026-04-30 09:00:00');

        $this->createInvoice($staffUser, $customer, $loThuoc, 231000, $selectedDay->copy()->setTime(10, 0));
        $this->createInvoice($loggedInStaff, $customer, $loThuoc, 231000, $selectedDay->copy()->setTime(11, 0));

        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $loggedInStaff->id_nhan_vien,
            'thoi_gian_dang_nhap' => $selectedDay->copy()->setTime(8, 30),
        ]);

        Sanctum::actingAs($loggedInStaff, ['staff']);

        $response = $this->getJson('/api/dashboard/staff-performance?date=2026-04-30&month=2026-04');

        $response
            ->assertOk()
            ->assertJsonPath('data.hom_nay.tong_doanh_thu', 231000)
            ->assertJsonPath('data.hom_nay.tong_hoa_don', 1)
            ->assertJsonPath('data.hom_nay.so_nhan_vien_dang_nhap', 1)
            ->assertJsonCount(1, 'data.hom_nay.nhan_viens')
            ->assertJsonPath('data.hom_nay.nhan_viens.0.id_nhan_vien', $loggedInStaff->id_nhan_vien)
            ->assertJsonPath('data.hom_nay.nhan_viens.0.ho_ten', 'Le Vu Hoang Duy')
            ->assertJsonPath('data.hom_nay.nhan_viens.0.so_hoa_don', 1)
            ->assertJsonPath('data.tuan_nay.tong_doanh_thu', 231000)
            ->assertJsonPath('data.tuan_nay.tong_hoa_don', 1)
            ->assertJsonPath('data.thang_nay.tong_doanh_thu', 231000)
            ->assertJsonPath('data.thang_nay.tong_hoa_don', 1);
    }

    private function createInvoice(
        NhanVien $nhanVien,
        KhachHang $khachHang,
        LoThuoc $loThuoc,
        int $amount,
        Carbon $soldAt
    ): HoaDon {
        $hoaDon = HoaDon::factory()->create([
            'id_nhan_vien' => $nhanVien->id_nhan_vien,
            'id_khach_hang' => $khachHang->id_khach_hang,
            'tong_tien' => $amount,
            'giam_gia' => 0,
            'thue_vat' => 0,
            'tien_thanh_toan' => $amount,
            'ngay_ban' => $soldAt,
        ]);

        ChiTietHoaDon::factory()->create([
            'id_hoa_don' => $hoaDon->id_hoa_don,
            'id_lo' => $loThuoc->id_lo,
            'so_luong' => 1,
            'gia_ban' => $amount,
            'thanh_tien' => $amount,
        ]);

        return $hoaDon;
    }

    private function createLot(): LoThuoc
    {
        $thuoc = Thuoc::factory()->create([
            'gia_ban' => 231000,
            'trang_thai' => 'con ban',
        ]);

        return LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'so_luong_nhap' => 10,
            'so_luong_nhap_goc' => 10,
            'so_luong_con' => 10,
        ]);
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
