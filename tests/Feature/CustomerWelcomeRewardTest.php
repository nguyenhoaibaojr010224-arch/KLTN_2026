<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\KhachHang;
use App\Models\LoThuoc;
use App\Models\MaGiamGia;
use App\Models\NhanVien;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerWelcomeRewardTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_auto_first_order_coupon(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'ten_khach_hang' => 'Khach Hang Moi',
            'so_dien_thoai' => '0987000001',
            'email' => 'welcome.reward@example.com',
            'dia_chi' => '123 Nguyen Trai',
            'mat_khau' => 'Password@1',
            'mat_khau_confirmation' => 'Password@1',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('first_order_coupon.gia_tri', 10)
            ->assertJsonPath('first_order_coupon.loai_ap_dung', 'phan_tram');

        $khachHang = KhachHang::where('email', 'welcome.reward@example.com')->firstOrFail();

        $this->assertDatabaseHas('ma_giam_gias', [
            'id_khach_hang' => $khachHang->id_khach_hang,
            'loai_ma' => 'first_order',
            'tu_dong_ap_dung' => 1,
            'loai_ap_dung' => 'phan_tram',
            'gia_tri' => 10,
            'gioi_han_moi_khach' => 1,
            'trang_thai' => 'active',
        ]);
    }

    public function test_first_order_coupon_is_available_once_and_checkout_earns_points(): void
    {
        Mail::fake();
        $this->createNhanVien();
        $khachHang = KhachHang::factory()->create(['diem_tich_luy' => 0]);
        $thuoc = $this->createThuocWithStock(100000);
        $coupon = $this->createFirstOrderCoupon($khachHang);

        Sanctum::actingAs($khachHang, ['customer']);

        $availableResponse = $this->getJson('/api/ma-giam-gias/customer-available?tong_tam_tinh=100000');
        $availableResponse
            ->assertOk()
            ->assertJsonPath('data.0.ma_giam_gia', $coupon->ma_giam_gia)
            ->assertJsonPath('data.0.tu_dong_ap_dung', true)
            ->assertJsonPath('data.0.giam_gia_don_hang', 10000);

        $checkoutResponse = $this->postJson('/api/checkout/orders', [
            'items' => [
                [
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'so_luong' => 1,
                    'don_vi' => 'hop',
                ],
            ],
            'phuong_thuc_thanh_toan' => 'cod',
            'ma_giam_gia' => $coupon->ma_giam_gia,
        ]);

        $checkoutResponse
            ->assertCreated()
            ->assertJsonPath('data.giam_gia_ma', 10000)
            ->assertJsonPath('data.giam_gia_diem', 0)
            ->assertJsonPath('data.diem_da_cong', 100)
            ->assertJsonPath('data.diem_hien_tai', 100);

        $this->assertDatabaseHas('ma_giam_gia_luot_dungs', [
            'ma_giam_gia_id' => $coupon->id,
            'id_khach_hang' => $khachHang->id_khach_hang,
            'so_lan_su_dung' => 1,
        ]);

        $this->getJson('/api/ma-giam-gias/customer-available?tong_tam_tinh=100000')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_reward_points_can_be_redeemed_in_full_blocks_and_next_order_still_earns_points(): void
    {
        Mail::fake();
        $this->createNhanVien();
        $khachHang = KhachHang::factory()->create(['diem_tich_luy' => 2500]);
        $thuoc = $this->createThuocWithStock(50000, 10);

        Sanctum::actingAs($khachHang, ['customer']);

        $usePointsResponse = $this->postJson('/api/checkout/orders', [
            'items' => [
                [
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'so_luong' => 1,
                    'don_vi' => 'hop',
                ],
            ],
            'phuong_thuc_thanh_toan' => 'cod',
            'su_dung_diem' => true,
        ]);

        $usePointsResponse
            ->assertCreated()
            ->assertJsonPath('data.giam_gia_diem', 20000)
            ->assertJsonPath('data.diem_da_su_dung', 2000)
            ->assertJsonPath('data.diem_da_cong', 50)
            ->assertJsonPath('data.diem_hien_tai', 550);

        $keepPointsResponse = $this->postJson('/api/checkout/orders', [
            'items' => [
                [
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'so_luong' => 1,
                    'don_vi' => 'hop',
                ],
            ],
            'phuong_thuc_thanh_toan' => 'cod',
            'su_dung_diem' => false,
        ]);

        $keepPointsResponse
            ->assertCreated()
            ->assertJsonPath('data.giam_gia_diem', 0)
            ->assertJsonPath('data.diem_da_su_dung', 0)
            ->assertJsonPath('data.diem_da_cong', 50)
            ->assertJsonPath('data.diem_hien_tai', 600);
    }

    private function createNhanVien(): NhanVien
    {
        $role = VaiTro::firstOrCreate(['ten_vai_tro' => 'nhan_vien'], ['mo_ta' => 'nhan_vien']);
        $bangCap = BangCap::factory()->create();

        return NhanVien::factory()->create([
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $bangCap->id_bang_cap,
            'trang_thai' => 'active',
        ]);
    }

    private function createThuocWithStock(int $price, int $stock = 20): Thuoc
    {
        $thuoc = Thuoc::factory()->create([
            'don_vi_tinh' => 'hop',
            'don_vi_co_so' => 'hop',
            'he_so_quy_doi' => 1,
            'gia_ban' => $price,
            'trang_thai' => 'con ban',
        ]);

        LoThuoc::factory()->create([
            'id_thuoc' => $thuoc->ma_thuoc,
            'don_vi_nhap' => 'hop',
            'don_vi_co_so' => 'hop',
            'so_luong_nhap' => $stock,
            'so_luong_nhap_goc' => $stock,
            'so_luong_con' => $stock,
            'he_so_quy_doi_nhap' => 1,
            'gia_nhap' => max(1000, (int) floor($price / 2)),
            'gia_nhap_quy_doi' => max(1000, (int) floor($price / 2)),
        ]);

        return $thuoc;
    }

    private function createFirstOrderCoupon(KhachHang $khachHang): MaGiamGia
    {
        return MaGiamGia::create([
            'ma_giam_gia' => 'WELCOME10-' . $khachHang->id_khach_hang,
            'ten_ma' => 'Uu dai don dau tien',
            'mo_ta' => 'Giam 10% cho don hang dau tien.',
            'loai_ap_dung' => 'phan_tram',
            'gia_tri' => 10,
            'gia_tri_don_toi_thieu' => 0,
            'gioi_han_moi_khach' => 1,
            'ngay_bat_dau' => now()->subMinute(),
            'ngay_ket_thuc' => null,
            'trang_thai' => 'active',
            'id_khach_hang' => $khachHang->id_khach_hang,
            'loai_ma' => 'first_order',
            'tu_dong_ap_dung' => true,
        ]);
    }
}
