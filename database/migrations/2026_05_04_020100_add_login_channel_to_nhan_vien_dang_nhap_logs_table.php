<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nhan_vien_dang_nhap_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('nhan_vien_dang_nhap_logs', 'kenh_dang_nhap')) {
                $table->string('kenh_dang_nhap', 30)->default('he_thong')->after('token_id');
            }
        });

        $this->dropSingleActiveSessionIndex();

        Schema::table('nhan_vien_dang_nhap_logs', function (Blueprint $table) {
            $table->unique(['kenh_dang_nhap', 'dang_hoat_dong'], 'nv_login_logs_channel_active_unique');
            $table->index(['kenh_dang_nhap', 'thoi_gian_dang_nhap'], 'nv_login_logs_channel_time_index');
        });
    }

    public function down(): void
    {
        Schema::table('nhan_vien_dang_nhap_logs', function (Blueprint $table) {
            $table->dropUnique('nv_login_logs_channel_active_unique');
            $table->dropIndex('nv_login_logs_channel_time_index');
            $table->dropColumn('kenh_dang_nhap');
            $table->unique('dang_hoat_dong', 'nv_login_logs_single_active_session_unique');
        });
    }

    private function dropSingleActiveSessionIndex(): void
    {
        try {
            Schema::table('nhan_vien_dang_nhap_logs', function (Blueprint $table) {
                $table->dropUnique('nv_login_logs_single_active_session_unique');
            });
        } catch (QueryException) {
            // Fresh or manually repaired databases may not have the previous index.
        }
    }
};
