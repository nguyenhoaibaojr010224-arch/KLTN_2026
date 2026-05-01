<?php

namespace Tests\Feature;

use App\Models\HoTroHoiThoai;
use App\Models\HoTroTinNhan;
use App\Models\KhachHang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportGuestConversationCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_disconnect_deletes_guest_support_conversation_and_messages(): void
    {
        $conversation = HoTroHoiThoai::create([
            'guest_session_id' => 'guest-cleanup-session',
            'guest_display_name' => 'Khach Vang Lai',
            'trang_thai' => 'dang_trao_doi',
            'thoi_gian_tin_nhan_cuoi' => now(),
        ]);

        HoTroTinNhan::create([
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
            'nguoi_gui_loai' => 'customer',
            'noi_dung' => 'Toi can ho tro',
            'da_doc' => false,
            'thoi_gian' => now(),
        ]);

        HoTroTinNhan::create([
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
            'nguoi_gui_loai' => 'staff',
            'noi_dung' => 'Nhan vien dang ho tro',
            'da_doc' => false,
            'thoi_gian' => now(),
        ]);

        $response = $this->postJson('/api/support/conversation/guest/disconnect', [
            'guest_session_id' => 'guest-cleanup-session',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('ho_tro_hoi_thoais', [
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
        ]);

        $this->assertDatabaseMissing('ho_tro_tin_nhans', [
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
        ]);
    }

    public function test_guest_disconnect_keeps_authenticated_customer_support_conversation(): void
    {
        $customer = KhachHang::factory()->create();
        $conversation = HoTroHoiThoai::create([
            'id_khach_hang' => $customer->id_khach_hang,
            'trang_thai' => 'dang_trao_doi',
            'thoi_gian_tin_nhan_cuoi' => now(),
        ]);

        Sanctum::actingAs($customer, ['customer']);

        $response = $this->postJson('/api/support/conversation/guest/disconnect', [
            'guest_session_id' => 'guest-stale-session',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.deleted', false);

        $this->assertDatabaseHas('ho_tro_hoi_thoais', [
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
            'id_khach_hang' => $customer->id_khach_hang,
        ]);
    }
}
