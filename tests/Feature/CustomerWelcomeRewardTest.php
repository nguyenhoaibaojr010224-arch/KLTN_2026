<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmedMail;
use App\Models\BangCap;
use App\Models\KhachHang;
use App\Models\LoThuoc;
use App\Models\MaGiamGia;
use App\Models\NhanVien;
use App\Models\Thuoc;
use App\Models\VaiTro;
use App\Services\PayosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_first_order_coupon_is_available_once_and_checkout_earns_points_after_staff_confirmation(): void
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
            ->assertJsonPath('data.diem_hien_tai', 0);

        $this->assertSame(0, (int) $khachHang->fresh()->diem_tich_luy);

        $this->assertDatabaseHas('ma_giam_gia_luot_dungs', [
            'ma_giam_gia_id' => $coupon->id,
            'id_khach_hang' => $khachHang->id_khach_hang,
            'so_lan_su_dung' => 1,
        ]);

        $admin = $this->createNhanVien('admin');
        Sanctum::actingAs($admin, ['*']);

        $this->postJson("/api/hoa-dons/{$checkoutResponse->json('data.id_hoa_don')}/confirm")
            ->assertOk()
            ->assertJsonPath('data.diem_thuong_da_xu_ly', true);

        $this->assertSame(100, (int) $khachHang->fresh()->diem_tich_luy);

        Sanctum::actingAs($khachHang, ['customer']);

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
            ->assertJsonPath('data.diem_hien_tai', 2500);

        $this->assertSame(2500, (int) $khachHang->fresh()->diem_tich_luy);

        $admin = $this->createNhanVien('admin');
        Sanctum::actingAs($admin, ['*']);

        $this->postJson("/api/hoa-dons/{$usePointsResponse->json('data.id_hoa_don')}/confirm")
            ->assertOk()
            ->assertJsonPath('data.diem_thuong_da_xu_ly', true);

        $this->assertSame(550, (int) $khachHang->fresh()->diem_tich_luy);

        Sanctum::actingAs($khachHang, ['customer']);

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
            ->assertJsonPath('data.diem_hien_tai', 550);

        Sanctum::actingAs($admin, ['*']);

        $this->postJson("/api/hoa-dons/{$keepPointsResponse->json('data.id_hoa_don')}/confirm")
            ->assertOk()
            ->assertJsonPath('data.diem_thuong_da_xu_ly', true);

        $this->assertSame(600, (int) $khachHang->fresh()->diem_tich_luy);
    }

    public function test_checkout_can_create_payos_payment_link_and_webhook_marks_it_paid(): void
    {
        Mail::fake();
        Http::fake([
            'https://api-merchant.payos.vn/v2/payment-requests' => Http::response([
                'code' => '00',
                'desc' => 'success',
                'data' => [
                    'paymentLinkId' => 'plink_test_001',
                    'checkoutUrl' => 'https://pay.payos.vn/web/plink_test_001',
                    'qrCode' => '000201010212',
                    'status' => 'PENDING',
                ],
            ]),
        ]);

        config([
            'services.payos.client_id' => 'client_test',
            'services.payos.api_key' => 'api_test',
            'services.payos.checksum_key' => 'checksum_test',
            'services.payos.base_url' => 'https://api-merchant.payos.vn',
            'services.payos.return_url' => 'http://localhost:5173/tai-khoan/lich-su-don-hang',
            'services.payos.cancel_url' => 'http://localhost:5173/thanh-toan',
        ]);

        $this->createNhanVien();
        $khachHang = KhachHang::factory()->create(['diem_tich_luy' => 0]);
        $thuoc = $this->createThuocWithStock(100000);

        Sanctum::actingAs($khachHang, ['customer']);

        $checkoutResponse = $this->postJson('/api/checkout/orders', [
            'items' => [
                [
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'so_luong' => 1,
                    'don_vi' => 'hop',
                ],
            ],
            'phuong_thuc_thanh_toan' => 'payos',
        ]);

        $checkoutResponse
            ->assertCreated()
            ->assertJsonPath('data.phuong_thuc_thanh_toan', 'payos')
            ->assertJsonPath('data.trang_thai_thanh_toan', 'pending')
            ->assertJsonPath('data.diem_da_cong', 100)
            ->assertJsonPath('data.diem_hien_tai', 0)
            ->assertJsonPath('data.payos.checkout_url', 'https://pay.payos.vn/web/plink_test_001');

        $this->assertSame(0, (int) $khachHang->fresh()->diem_tich_luy);

        Http::assertSent(fn ($request): bool => $request->hasHeader('x-client-id', 'client_test')
            && $request->hasHeader('x-api-key', 'api_test')
            && $request['amount'] === 110000
            && $request['orderCode'] === $checkoutResponse->json('data.id_hoa_don')
            && filled($request['signature']));

        $this->assertDatabaseHas('thanh_toan', [
            'id_hoa_don' => $checkoutResponse->json('data.id_hoa_don'),
            'phuong_thuc' => 'payos',
            'trang_thai' => 'pending',
            'payos_order_code' => $checkoutResponse->json('data.id_hoa_don'),
            'payos_payment_link_id' => 'plink_test_001',
        ]);

        $admin = $this->createNhanVien('admin');
        Sanctum::actingAs($admin, ['*']);

        $this->postJson("/api/hoa-dons/{$checkoutResponse->json('data.id_hoa_don')}/confirm")
            ->assertUnprocessable()
            ->assertJsonPath('errors.thanh_toan.0', 'Đơn PayOS chưa thanh toán thành công nên chưa thể xác nhận.');

        Sanctum::actingAs($khachHang, ['customer']);

        $payos = app(PayosService::class);
        $webhookData = [
            'orderCode' => $checkoutResponse->json('data.id_hoa_don'),
            'amount' => 110000,
            'description' => 'PHARMAGO',
            'reference' => 'PAYOS_REF_001',
            'paymentLinkId' => 'plink_test_001',
            'transactionDateTime' => now()->toIso8601String(),
        ];

        $this->postJson('/api/payos/webhook', [
            'code' => '00',
            'desc' => 'success',
            'success' => true,
            'data' => $webhookData,
            'signature' => $payos->signature($webhookData),
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('thanh_toan', [
            'id_hoa_don' => $checkoutResponse->json('data.id_hoa_don'),
            'trang_thai' => 'paid',
            'ma_giao_dich' => 'PAYOS_REF_001',
        ]);

        $this->assertSame(100, (int) $khachHang->fresh()->diem_tich_luy);
        $this->assertDatabaseHas('hoa_dons', [
            'id_hoa_don' => $checkoutResponse->json('data.id_hoa_don'),
            'diem_thuong_da_xu_ly' => 1,
        ]);

        Sanctum::actingAs($admin, ['*']);

        $this->postJson("/api/hoa-dons/{$checkoutResponse->json('data.id_hoa_don')}/confirm")
            ->assertOk()
            ->assertJsonPath('data.trang_thai_xu_ly', 'da_xac_nhan');

        Mail::assertSent(OrderConfirmedMail::class, function (OrderConfirmedMail $mail) use ($khachHang): bool {
            $rendered = $mail->render();

            return $mail->hasTo($khachHang->email)
                && ($mail->meta['payment_method_label'] ?? null) === 'QR'
                && str_contains($rendered, 'QR');
        });
    }

    public function test_customer_can_cancel_pending_payos_order_without_earning_points(): void
    {
        Mail::fake();
        Http::fake([
            'https://api-merchant.payos.vn/v2/payment-requests' => Http::response([
                'code' => '00',
                'desc' => 'success',
                'data' => [
                    'paymentLinkId' => 'plink_cancel_001',
                    'checkoutUrl' => 'https://pay.payos.vn/web/plink_cancel_001',
                    'qrCode' => '000201010212',
                    'status' => 'PENDING',
                ],
            ]),
        ]);

        config([
            'services.payos.client_id' => 'client_test',
            'services.payos.api_key' => 'api_test',
            'services.payos.checksum_key' => 'checksum_test',
            'services.payos.base_url' => 'https://api-merchant.payos.vn',
            'services.payos.return_url' => 'http://localhost:5173/tai-khoan/lich-su-don-hang',
            'services.payos.cancel_url' => 'http://localhost:5173/tai-khoan/lich-su-don-hang',
        ]);

        $this->createNhanVien();
        $khachHang = KhachHang::factory()->create(['diem_tich_luy' => 0]);
        $thuoc = $this->createThuocWithStock(100000);

        Sanctum::actingAs($khachHang, ['customer']);

        $checkoutResponse = $this->postJson('/api/checkout/orders', [
            'items' => [
                [
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'so_luong' => 1,
                    'don_vi' => 'hop',
                ],
            ],
            'phuong_thuc_thanh_toan' => 'payos',
        ]);

        $checkoutResponse->assertCreated();
        $orderCode = $checkoutResponse->json('data.id_hoa_don');

        $this->postJson('/api/checkout/orders/payos-cancel', [
            'order_code' => $orderCode,
            'payment_link_id' => 'plink_cancel_001',
            'status' => 'CANCELLED',
        ])
            ->assertOk()
            ->assertJsonPath('message', "Đơn hàng {$checkoutResponse->json('data.ma_hoa_don')} đã bị hủy.")
            ->assertJsonPath('data', null);

        $this->assertSame(0, (int) $khachHang->fresh()->diem_tich_luy);
        $this->assertDatabaseMissing('hoa_dons', [
            'id_hoa_don' => $orderCode,
        ]);
        $this->assertDatabaseMissing('thanh_toan', [
            'id_hoa_don' => $orderCode,
        ]);
        $this->assertDatabaseMissing('lich_su_don_hangs', [
            'id_hoa_don' => $orderCode,
        ]);
        $this->assertDatabaseHas('lo_thuocs', [
            'id_thuoc' => $thuoc->ma_thuoc,
            'so_luong_con' => 20,
        ]);
    }

    private function createNhanVien(string $roleName = 'nhan_vien'): NhanVien
    {
        $role = VaiTro::firstOrCreate(['ten_vai_tro' => $roleName], ['mo_ta' => $roleName]);
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
