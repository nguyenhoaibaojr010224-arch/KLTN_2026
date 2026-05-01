<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ho_tro_hoi_thoais', function (Blueprint $table) {
            $table->id('id_hoi_thoai');
            $table->unsignedBigInteger('id_khach_hang')->nullable()->unique();
            $table->unsignedBigInteger('id_nhan_vien_phu_trach')->nullable();
            $table->string('trang_thai', 30)->default('moi');
            $table->dateTime('thoi_gian_tin_nhan_cuoi')->nullable();
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent();

            $table->foreign('id_khach_hang')
                ->references('id_khach_hang')
                ->on('khach_hangs')
                ->cascadeOnDelete();

            $table->foreign('id_nhan_vien_phu_trach')
                ->references('id_nhan_vien')
                ->on('nhan_viens')
                ->nullOnDelete();

            $table->index(['trang_thai', 'thoi_gian_tin_nhan_cuoi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ho_tro_hoi_thoais');
    }
};
