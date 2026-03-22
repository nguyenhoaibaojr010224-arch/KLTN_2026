<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khuyen_mais', function (Blueprint $table) {
            $table->id();
            $table->string('ma_thuoc', 10);
            $table->string('ten_khuyen_mai', 150);
            $table->text('mo_ta')->nullable();
            $table->enum('loai_ap_dung', ['phan_tram', 'so_tien', 'gia_co_dinh']);
            $table->unsignedInteger('gia_tri');
            $table->string('nhan_hien_thi', 100)->nullable();
            $table->dateTime('ngay_bat_dau');
            $table->dateTime('ngay_ket_thuc')->nullable();
            $table->enum('trang_thai', ['draft', 'active', 'inactive'])->default('draft');
            $table->unsignedBigInteger('id_nhan_vien')->nullable();
            $table->timestamps();

            $table->foreign('ma_thuoc')->references('ma_thuoc')->on('thuocs')->cascadeOnDelete();
            $table->foreign('id_nhan_vien')->references('id_nhan_vien')->on('nhan_viens')->nullOnDelete();
            $table->index(['ma_thuoc', 'trang_thai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khuyen_mais');
    }
};
