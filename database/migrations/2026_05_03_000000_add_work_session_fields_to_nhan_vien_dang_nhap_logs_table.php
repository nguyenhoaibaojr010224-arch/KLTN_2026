<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nhan_vien_dang_nhap_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('token_id')->nullable()->after('id_nhan_vien');
            $table->timestamp('thoi_gian_dang_xuat')->nullable()->after('thoi_gian_dang_nhap');
            $table->timestamp('het_han_luc')->nullable()->after('thoi_gian_dang_xuat');
            $table->unsignedInteger('thoi_luong_giay')->nullable()->after('het_han_luc');
            $table->string('ly_do_dang_xuat', 30)->nullable()->after('thoi_luong_giay');

            $table->index('token_id', 'nv_login_logs_token_id_index');
            $table->index(['id_nhan_vien', 'thoi_gian_dang_xuat'], 'nv_login_logs_employee_logout_index');
        });
    }

    public function down(): void
    {
        Schema::table('nhan_vien_dang_nhap_logs', function (Blueprint $table) {
            $table->dropIndex('nv_login_logs_token_id_index');
            $table->dropIndex('nv_login_logs_employee_logout_index');
            $table->dropColumn([
                'token_id',
                'thoi_gian_dang_xuat',
                'het_han_luc',
                'thoi_luong_giay',
                'ly_do_dang_xuat',
            ]);
        });
    }
};
