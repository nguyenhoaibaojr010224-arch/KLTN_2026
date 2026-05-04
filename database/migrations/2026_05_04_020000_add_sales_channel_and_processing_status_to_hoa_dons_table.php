<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoa_dons', function (Blueprint $table) {
            $table->string('kenh_ban', 30)->default('he_thong')->after('ma_giam_gia_id');
            $table->string('trang_thai_xu_ly', 30)->default('da_xac_nhan')->after('kenh_ban');
            $table->text('ly_do_tu_choi')->nullable()->after('trang_thai_xu_ly');

            $table->index(['kenh_ban', 'trang_thai_xu_ly'], 'hoa_dons_channel_processing_index');
            $table->index(['trang_thai_xu_ly', 'ngay_ban'], 'hoa_dons_processing_sold_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('hoa_dons', function (Blueprint $table) {
            $table->dropIndex('hoa_dons_channel_processing_index');
            $table->dropIndex('hoa_dons_processing_sold_at_index');
            $table->dropColumn(['kenh_ban', 'trang_thai_xu_ly', 'ly_do_tu_choi']);
        });
    }
};
