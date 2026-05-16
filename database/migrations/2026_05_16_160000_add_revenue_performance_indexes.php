<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index trên hoa_dons để tăng tốc thống kê doanh thu
        Schema::table('hoa_dons', function (Blueprint $table) {
            $table->index(['ngay_ban', 'id_nhan_vien'], 'hoa_dons_ngay_ban_nhan_vien_index');
            $table->index(['ngay_ban', 'trang_thai_xu_ly'], 'hoa_dons_ngay_ban_trang_thai_index');
        });

        // Index trên thanh_toan để tăng tốc filter trang_thai
        $this->safeIndex('thanh_toan', ['trang_thai'], 'thanh_toan_trang_thai_index');

        // Index trên chi_tiet_hoa_don
        $this->safeIndex('chi_tiet_hoa_don', ['id_hoa_don'], 'chi_tiet_hoa_don_id_hoa_don_index');
    }

    public function down(): void
    {
        Schema::table('hoa_dons', function (Blueprint $table) {
            $table->dropIndex('hoa_dons_ngay_ban_nhan_vien_index');
            $table->dropIndex('hoa_dons_ngay_ban_trang_thai_index');
        });

        $this->safeDrop('thanh_toan', 'thanh_toan_trang_thai_index');
        $this->safeDrop('chi_tiet_hoa_don', 'chi_tiet_hoa_don_id_hoa_don_index');
    }

    private function safeIndex(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                $t->index($columns, $name);
            });
        } catch (QueryException) {
            // Index may already exist
        }
    }

    private function safeDrop(string $table, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($name) {
                $t->dropIndex($name);
            });
        } catch (QueryException) {
            // Index may not exist
        }
    }
};
