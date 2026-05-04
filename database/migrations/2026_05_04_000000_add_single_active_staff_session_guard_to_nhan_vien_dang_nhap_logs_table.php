<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nhan_vien_dang_nhap_logs', function (Blueprint $table) {
            $table->boolean('dang_hoat_dong')->nullable()->after('ly_do_dang_xuat');
            $table->unique('dang_hoat_dong', 'nv_login_logs_single_active_session_unique');
        });
    }

    public function down(): void
    {
        Schema::table('nhan_vien_dang_nhap_logs', function (Blueprint $table) {
            $table->dropUnique('nv_login_logs_single_active_session_unique');
            $table->dropColumn('dang_hoat_dong');
        });
    }
};
