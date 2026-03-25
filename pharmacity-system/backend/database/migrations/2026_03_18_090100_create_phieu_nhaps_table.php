<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phieu_nhaps', function (Blueprint $table) {
            $table->id('id_phieu_nhap');
            $table->unsignedBigInteger('id_nha_san_xuat');
            $table->unsignedBigInteger('id_nhan_vien');
            $table->decimal('tong_tien', 14, 2)->default(0);
            $table->dateTime('ngay_nhap');
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent();

            $table->foreign('id_nha_san_xuat')
                ->references('id')
                ->on('nha_san_xuats')
                ->restrictOnDelete();

            $table->foreign('id_nhan_vien')
                ->references('id_nhan_vien')
                ->on('nhan_viens')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phieu_nhaps');
    }
};
