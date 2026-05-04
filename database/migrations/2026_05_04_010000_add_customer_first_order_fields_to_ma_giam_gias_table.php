<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ma_giam_gias', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_khach_hang')->nullable()->after('id_nhan_vien');
            $table->string('loai_ma', 30)->default('general')->after('id_khach_hang');
            $table->boolean('tu_dong_ap_dung')->default(false)->after('loai_ma');

            $table->foreign('id_khach_hang')->references('id_khach_hang')->on('khach_hangs')->cascadeOnDelete();
            $table->index(['id_khach_hang', 'loai_ma']);
            $table->index('tu_dong_ap_dung');
        });
    }

    public function down(): void
    {
        Schema::table('ma_giam_gias', function (Blueprint $table): void {
            $table->dropForeign(['id_khach_hang']);
            $table->dropIndex(['id_khach_hang', 'loai_ma']);
            $table->dropIndex(['tu_dong_ap_dung']);
            $table->dropColumn(['id_khach_hang', 'loai_ma', 'tu_dong_ap_dung']);
        });
    }
};
