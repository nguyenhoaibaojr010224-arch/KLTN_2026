<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\ChiTietHoaDon;
use App\Mail\OrderConfirmedMail;
use App\Models\EmailVerification;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\KhuyenMai;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use App\Models\NhaSanXuat;
use App\Models\ThongTinNhanVien;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    public function test_admin_can_create_employee_with_phone_number_for_login(): void
    {
        $admin = $this->createNhanVien('admin_create_employee_phone', 'admin');
        $role = VaiTro::firstOrCreate(['ten_vai_tro' => 'nhan_vien'], ['mo_ta' => 'nhan_vien']);
        $bangCap = BangCap::factory()->create();

        Sanctum::actingAs($admin, ['admin']);

        $response = $this->postJson('/api/admin/nhan-viens', [
            'so_dien_thoai' => '0901112223',
            'mat_khau' => 'password',
            'ho_ten' => 'Nhan Vien Moi',
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $bangCap->id_bang_cap,
            'trang_thai' => 'active',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('so_dien_thoai', '0901112223')
            ->assertJsonPath('ten_dang_nhap', '0901112223');

        $this->assertDatabaseHas('thong_tin_nhan_viens', [
            'id_nhan_vien' => $response->json('id_nhan_vien'),
            'so_dien_thoai' => '0901112223',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'so_dien_thoai' => '0901112223',
            'password' => 'password',
            'kenh_dang_nhap' => 'he_thong',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('type', 'staff')
            ->assertJsonPath('user.ho_ten', 'Nhan Vien Moi');
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

    public function test_staff_can_create_purchase_receipt_with_multiple_new_lots(): void
    {
        $staff = $this->createNhanVien('staff_purchase_receipt', 'nhan_vien');
        $supplier = NhaSanXuat::factory()->create([
            'ten_nha_san_xuat' => 'Nha cung cap DHG',
        ]);
        $hapacol = Thuoc::factory()->create([
            'ten_thuoc' => 'Hapacol 250',
            'don_vi_tinh' => 'Hop',
            'don_vi_co_so' => 'Vi',
            'he_so_quy_doi' => 10,
        ]);
        $siro = Thuoc::factory()->create([
            'ten_thuoc' => 'Siro ho Bao Thanh',
            'don_vi_tinh' => 'Chai',
            'don_vi_co_so' => 'ml',
            'he_so_quy_doi' => 100,
        ]);

        Sanctum::actingAs($staff, ['staff']);

        $response = $this->postJson('/api/phieu-nhaps', [
            'id_nha_san_xuat' => $supplier->id,
            'so_hoa_don_giay' => 'HDN-000128',
            'ngay_hoa_don' => now()->toDateString(),
            'ngay_nhap' => now()->toDateTimeString(),
            'chung_tu_url' => 'https://example.com/hoa-don/HDN-000128.pdf',
            'ghi_chu' => 'Nhap tu hoa don giay nha cung cap giao.',
            'chi_tiets' => [
                [
                    'id_thuoc' => $hapacol->ma_thuoc,
                    'so_lo' => 'LO-HAPA-001',
                    'ngay_san_xuat' => now()->subMonth()->toDateString(),
                    'han_su_dung' => now()->addYear()->toDateString(),
                    'don_vi_nhap' => 'Hop',
                    'so_luong_nhap_goc' => 3,
                    'gia_nhap' => 90000,
                ],
                [
                    'id_thuoc' => $siro->ma_thuoc,
                    'so_lo' => 'LO-SIRO-001',
                    'ngay_san_xuat' => now()->subWeeks(2)->toDateString(),
                    'han_su_dung' => now()->addYears(2)->toDateString(),
                    'don_vi_nhap' => 'Chai',
                    'so_luong_nhap_goc' => 12,
                    'gia_nhap' => 30000,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.so_hoa_don_giay', 'HDN-000128')
            ->assertJsonPath('data.tong_tien', '630000.00')
            ->assertJsonCount(2, 'data.chi_tiets');

        $this->assertNotEmpty($response->json('data.ma_phieu_nhap'));
        $this->assertDatabaseHas('lo_thuocs', [
            'id_thuoc' => $hapacol->ma_thuoc,
            'so_lo' => 'LO-HAPA-001',
            'don_vi_nhap' => 'Hop',
            'don_vi_co_so' => 'Vi',
            'so_luong_nhap_goc' => 3,
            'so_luong_nhap' => 30,
            'so_luong_con' => 30,
            'he_so_quy_doi_nhap' => 10,
            'gia_nhap' => 90000,
            'gia_nhap_quy_doi' => 9000,
        ]);
        $this->assertDatabaseHas('chi_tiet_phieu_nhaps', [
            'so_luong_nhap_goc' => 3,
            'so_luong' => 30,
            'gia_nhap' => 90000,
            'thanh_tien' => 270000,
        ]);
    }

    public function test_staff_can_view_inventory_alerts_for_expiring_lots_and_low_stock(): void
    {
        $staff = $this->createNhanVien('staff_inventory_alerts', 'nhan_vien');
        $expiringThuoc = Thuoc::factory()->create([
            'ten_thuoc' => 'Thuoc sap het han',
            'don_vi_tinh' => 'hop',
            'don_vi_co_so' => 'hop',
            'he_so_quy_doi' => 1,
        ]);
        $lowStockThuoc = Thuoc::factory()->create([
            'ten_thuoc' => 'Thuoc gan het ton',
            'don_vi_tinh' => 'vi',
            'don_vi_co_so' => 'vi',
            'he_so_quy_doi' => 1,
        ]);
        $normalThuoc = Thuoc::factory()->create([
            'ten_thuoc' => 'Thuoc ton on dinh',
            'don_vi_tinh' => 'chai',
            'don_vi_co_so' => 'chai',
            'he_so_quy_doi' => 1,
        ]);

        LoThuoc::factory()->create([
            'id_thuoc' => $expiringThuoc->ma_thuoc,
            'so_lo' => 'LO-EXP-001',
            'han_su_dung' => now()->addDays(10)->toDateString(),
            'don_vi_co_so' => 'hop',
            'so_luong_con' => 50,
        ]);
        LoThuoc::factory()->create([
            'id_thuoc' => $lowStockThuoc->ma_thuoc,
            'so_lo' => 'LO-LOW-001',
            'han_su_dung' => now()->addDays(120)->toDateString(),
            'don_vi_co_so' => 'vi',
            'so_luong_con' => 8,
        ]);
        LoThuoc::factory()->create([
            'id_thuoc' => $normalThuoc->ma_thuoc,
            'so_lo' => 'LO-OK-001',
            'han_su_dung' => now()->addDays(120)->toDateString(),
            'don_vi_co_so' => 'chai',
            'so_luong_con' => 80,
        ]);

        Sanctum::actingAs($staff, ['staff']);

        $response = $this->getJson('/api/lo-thuocs/alerts?days=30&low_stock=20');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.lo_sap_het_han')
            ->assertJsonCount(1, 'data.thuoc_gan_het_ton')
            ->assertJsonPath('data.lo_sap_het_han.0.so_lo', 'LO-EXP-001')
            ->assertJsonPath('data.thuoc_gan_het_ton.0.ma_thuoc', $lowStockThuoc->ma_thuoc)
            ->assertJsonPath('tong_thong_bao', 2);
    }

    public function test_staff_performance_dashboard_combines_login_logs_and_revenue(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 15, 10, 0, 0));

        try {
            $admin = $this->createNhanVien('admin_performance_chart', 'admin');
            $staffA = $this->createNhanVien('staff_performance_a', 'nhan_vien');
            $staffB = $this->createNhanVien('staff_performance_b', 'nhan_vien');
            $khachHang = KhachHang::factory()->create();
            $thuoc = Thuoc::factory()->create();
            $loThuoc = LoThuoc::factory()->create([
                'id_thuoc' => $thuoc->ma_thuoc,
            ]);

            NhanVienDangNhapLog::create([
                'id_nhan_vien' => $staffA->id_nhan_vien,
                'thoi_gian_dang_nhap' => now()->subMinutes(30),
                'dia_chi_ip' => '127.0.0.1',
            ]);
            NhanVienDangNhapLog::create([
                'id_nhan_vien' => $staffB->id_nhan_vien,
                'thoi_gian_dang_nhap' => now()->subMonth(),
                'dia_chi_ip' => '127.0.0.1',
            ]);

            $invoiceA = HoaDon::factory()->create([
                'id_khach_hang' => $khachHang->id_khach_hang,
                'id_nhan_vien' => $staffA->id_nhan_vien,
                'tong_tien' => 300000,
                'giam_gia' => 0,
                'thue_vat' => 0,
                'tien_thanh_toan' => 300000,
                'trang_thai_xu_ly' => 'da_xac_nhan',
                'ngay_ban' => now(),
            ]);
            ChiTietHoaDon::factory()->create([
                'id_hoa_don' => $invoiceA->id_hoa_don,
                'id_lo' => $loThuoc->id_lo,
                'so_luong' => 1,
                'gia_ban' => 300000,
                'thanh_tien' => 300000,
            ]);

            $invoiceBToday = HoaDon::factory()->create([
                'id_khach_hang' => $khachHang->id_khach_hang,
                'id_nhan_vien' => $staffB->id_nhan_vien,
                'tong_tien' => 100000,
                'giam_gia' => 0,
                'thue_vat' => 0,
                'tien_thanh_toan' => 100000,
                'trang_thai_xu_ly' => 'da_xac_nhan',
                'ngay_ban' => now(),
            ]);
            ChiTietHoaDon::factory()->create([
                'id_hoa_don' => $invoiceBToday->id_hoa_don,
                'id_lo' => $loThuoc->id_lo,
                'so_luong' => 1,
                'gia_ban' => 100000,
                'thanh_tien' => 100000,
            ]);

            $invoiceBMonth = HoaDon::factory()->create([
                'id_khach_hang' => $khachHang->id_khach_hang,
                'id_nhan_vien' => $staffB->id_nhan_vien,
                'tong_tien' => 900000,
                'giam_gia' => 0,
                'thue_vat' => 0,
                'tien_thanh_toan' => 900000,
                'trang_thai_xu_ly' => 'da_xac_nhan',
                'ngay_ban' => now()->startOfMonth()->addDays(2),
            ]);
            ChiTietHoaDon::factory()->create([
                'id_hoa_don' => $invoiceBMonth->id_hoa_don,
                'id_lo' => $loThuoc->id_lo,
                'so_luong' => 1,
                'gia_ban' => 900000,
                'thanh_tien' => 900000,
            ]);

            Sanctum::actingAs($admin, ['admin']);

            $response = $this->getJson('/api/dashboard/staff-performance?date=2026-04-15&month=2026-04');

            $response
                ->assertOk()
                ->assertJsonPath('data.bo_loc.ngay', '2026-04-15')
                ->assertJsonPath('data.hom_nay.tong_hoa_don', 1)
                ->assertJsonPath('data.hom_nay.tong_doanh_thu', 300000)
                ->assertJsonPath('data.hom_nay.so_nhan_vien_dang_nhap', 1)
                ->assertJsonPath('data.hom_nay.nhan_viens.0.id_nhan_vien', $staffA->id_nhan_vien)
                ->assertJsonPath('data.tuan_nay.tu_ngay', '2026-04-13')
                ->assertJsonPath('data.tuan_nay.den_ngay', '2026-04-19')
                ->assertJsonPath('data.tuan_nay.tong_hoa_don', 1)
                ->assertJsonPath('data.tuan_nay.tong_doanh_thu', 300000)
                ->assertJsonPath('data.tuan_nay.doanh_thu_theo_ngay.2.ngay', '2026-04-15')
                ->assertJsonPath('data.tuan_nay.doanh_thu_theo_ngay.2.thu', 'Thứ 4')
                ->assertJsonPath('data.tuan_nay.doanh_thu_theo_ngay.2.so_hoa_don', 1)
                ->assertJsonPath('data.tuan_nay.doanh_thu_theo_ngay.2.doanh_thu', 300000)
                ->assertJsonPath('data.thang_nay.nhan_vien_dan_dau.id_nhan_vien', $staffA->id_nhan_vien)
                ->assertJsonPath('data.thang_nay.nhan_vien_dan_dau.doanh_thu', 300000);
        } finally {
            Carbon::setTestNow();
        }
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

    public function test_customer_checkout_sends_order_invoice_mail(): void
    {
        Mail::fake();

        $staff = $this->createNhanVien('staff_checkout_mail', 'nhan_vien');
        $khachHang = KhachHang::factory()->create([
            'ten_khach_hang' => 'To Dong Khanh',
            'email' => 'khanh.checkout@example.com',
        ]);
        $thuoc = Thuoc::factory()->create([
            'ten_thuoc' => 'Bot sui bot Hapacol',
            'don_vi_tinh' => 'hop',
            'don_vi_co_so' => 'hop',
            'he_so_quy_doi' => 1,
            'gia_ban' => 470000,
        ]);

        LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'don_vi_nhap' => 'hop',
            'don_vi_co_so' => 'hop',
            'so_luong_nhap' => 20,
            'so_luong_nhap_goc' => 20,
            'so_luong_con' => 20,
            'he_so_quy_doi_nhap' => 1,
        ]);

        Sanctum::actingAs($khachHang);

        $response = $this->postJson('/api/checkout/orders', [
            'items' => [
                [
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'so_luong' => 2,
                    'don_vi' => 'hop',
                ],
            ],
            'phuong_thuc_thanh_toan' => 'cod',
            'dia_chi_giao_hang' => '33 Hoa Xuan, Da Nang',
            'ghi_chu' => 'Giao trong gio hanh chinh',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.trang_thai_xu_ly', 'cho_xac_nhan');

        Mail::assertNothingSent();

        Sanctum::actingAs($staff, ['staff']);
        NhanVienDangNhapLog::create([
            'id_nhan_vien' => $staff->id_nhan_vien,
            'kenh_dang_nhap' => 'he_thong',
            'thoi_gian_dang_nhap' => now(),
            'dang_hoat_dong' => true,
        ]);

        $confirmResponse = $this->postJson("/api/hoa-dons/{$response->json('data.id_hoa_don')}/confirm");
        $confirmResponse
            ->assertOk()
            ->assertJsonPath('data.trang_thai_xu_ly', 'da_xac_nhan');

        Mail::assertSent(OrderConfirmedMail::class, function (OrderConfirmedMail $mail) use ($khachHang): bool {
            $rendered = $mail->render();

            return $mail->hasTo($khachHang->email)
                && str_contains($rendered, 'Bot sui bot Hapacol');
        });
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
        $token = EmailVerification::query()
            ->where('email', $khachHang->email)
            ->latest()
            ->value('token');

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
            ->assertJsonStructure([
                'user',
                'first_order_coupon' => ['ma_giam_gia', 'gia_tri', 'loai_ap_dung'],
                'verification' => ['email', 'status'],
            ]);

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
