<?php

namespace Tests\Feature;

use App\Models\BangCap;
use App\Models\HoTroHoiThoai;
use App\Models\HoTroTinNhan;
use App\Models\NhanVien;
use App\Models\VaiTro;
use App\Services\SupportAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SupportChatHumanHandoffTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_does_not_reply_after_nhan_vien_has_replied_in_conversation(): void
    {
        Carbon::setTestNow('2026-05-19 10:00:00');
        $staff = $this->createStaff();
        $conversation = HoTroHoiThoai::create([
            'guest_session_id' => 'guest-human-handoff',
            'guest_display_name' => 'Khach vang lai',
            'id_nhan_vien_phu_trach' => $staff->id_nhan_vien,
            'trang_thai' => 'dang_trao_doi',
            'thoi_gian_tin_nhan_cuoi' => now()->subMinute(),
        ]);
        HoTroTinNhan::create([
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
            'nguoi_gui_loai' => 'staff',
            'id_nhan_vien' => $staff->id_nhan_vien,
            'noi_dung' => 'Nhan vien dang ho tro ban.',
            'da_doc' => false,
            'thoi_gian' => now()->subMinute(),
        ]);
        $this->mock(SupportAiService::class, function ($mock): void {
            $mock->shouldNotReceive('replyToCustomerQuestion');
            $mock->shouldNotReceive('buildSuggestedProductsPayload');
        });

        $response = $this->postJson('/api/support/conversation/messages', [
            'guest_session_id' => 'guest-human-handoff',
            'noi_dung' => 'Toi can hoi them ve don hang.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.id_hoi_thoai', $conversation->id_hoi_thoai);
        $this->assertDatabaseHas('ho_tro_tin_nhans', [
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
            'nguoi_gui_loai' => 'customer',
            'noi_dung' => 'Toi can hoi them ve don hang.',
        ]);
        $this->assertDatabaseMissing('ho_tro_tin_nhans', [
            'id_hoi_thoai' => $conversation->id_hoi_thoai,
            'nguoi_gui_loai' => 'ai',
        ]);

        Carbon::setTestNow();
    }

    public function test_ai_still_replies_before_nhan_vien_takes_over(): void
    {
        Carbon::setTestNow('2026-05-19 10:00:00');
        $this->mock(SupportAiService::class, function ($mock): void {
            $mock->shouldReceive('replyToCustomerQuestion')
                ->once()
                ->with('Con thuoc ha sot nao khong?')
                ->andReturn([
                    'reply' => 'Ban co the xem cac san pham ha sot phu hop.',
                    'needs_pharmacist' => false,
                    'intent' => 'tra_cuu_thuoc',
                    'purchase_action' => null,
                    'matched_products' => [],
                ]);
            $mock->shouldReceive('buildSuggestedProductsPayload')
                ->once()
                ->andReturn([]);
        });

        $response = $this->postJson('/api/support/conversation/messages', [
            'guest_session_id' => 'guest-ai-before-staff',
            'noi_dung' => 'Con thuoc ha sot nao khong?',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.messages.0.nguoi_gui_loai', 'customer')
            ->assertJsonPath('data.messages.1.nguoi_gui_loai', 'ai');
        $this->assertDatabaseHas('ho_tro_tin_nhans', [
            'nguoi_gui_loai' => 'ai',
            'noi_dung' => 'Ban co the xem cac san pham ha sot phu hop.',
        ]);

        Carbon::setTestNow();
    }

    private function createStaff(): NhanVien
    {
        $role = VaiTro::factory()->create(['ten_vai_tro' => 'nhan_vien']);
        $degree = BangCap::factory()->create();

        return NhanVien::factory()->create([
            'id_vai_tro' => $role->id_vai_tro,
            'id_bang_cap' => $degree->id_bang_cap,
            'trang_thai' => 'active',
        ]);
    }
}
