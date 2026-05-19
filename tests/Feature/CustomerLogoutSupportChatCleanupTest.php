<?php

namespace Tests\Feature;

use App\Models\HoTroHoiThoai;
use App\Models\HoTroTinNhan;
use App\Models\KhachHang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerLogoutSupportChatCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_logout_deletes_support_conversation_and_messages(): void
    {
        $customer = KhachHang::factory()->create();
        Sanctum::actingAs($customer, ['customer']);
        $conversation = HoTroHoiThoai::create([
            'id_khach_hang' => $customer->id_khach_hang,
            'trang_thai' => 'dang_trao_doi',
            'thoi_gian_tin_nhan_cuoi' => now(),
        ]);
        $message = HoTroTinNhan::create([
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
            'nguoi_gui_loai' => 'customer',
            'id_khach_hang' => $customer->id_khach_hang,
            'noi_dung' => 'Toi can ho tro.',
            'da_doc' => false,
            'thoi_gian' => now(),
        ]);

        $response = $this->postJson('/api/logout');

        $response->assertOk();
        $this->assertDatabaseMissing('ho_tro_hoi_thoais', [
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
        ]);
        $this->assertDatabaseMissing('ho_tro_tin_nhans', [
            'id_tin_nhan' => $message->id_tin_nhan,
        ]);
    }
}
