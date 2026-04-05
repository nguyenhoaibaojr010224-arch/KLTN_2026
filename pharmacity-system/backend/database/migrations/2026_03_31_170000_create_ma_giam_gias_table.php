<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ma_giam_gias', function (Blueprint $table): void {
            $table->id();
            $table->string('ma_giam_gia', 30)->unique();
            $table->string('ten_ma', 150);
            $table->text('mo_ta')->nullable();
            $table->enum('loai_ap_dung', ['phan_tram', 'so_tien', 'gia_co_dinh']);
            $table->unsignedInteger('gia_tri');
            $table->unsignedInteger('gia_tri_don_toi_thieu')->default(0);
            $table->unsignedInteger('gioi_han_moi_khach')->nullable();
            $table->dateTime('ngay_bat_dau');
            $table->dateTime('ngay_ket_thuc')->nullable();
            $table->enum('trang_thai', ['draft', 'active', 'inactive'])->default('draft');
            $table->unsignedBigInteger('id_nhan_vien')->nullable();
            $table->timestamps();

            $table->foreign('id_nhan_vien')->references('id_nhan_vien')->on('nhan_viens')->nullOnDelete();
            $table->index(['ma_giam_gia', 'trang_thai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ma_giam_gias');
    }
};
