<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chi_tiet_phieu_nhaps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_phieu_nhap');
            $table->unsignedBigInteger('id_lo');
            $table->unsignedInteger('so_luong');
            $table->decimal('gia_nhap', 14, 2);
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent();

            $table->foreign('id_phieu_nhap')
                ->references('id_phieu_nhap')
                ->on('phieu_nhaps')
                ->cascadeOnDelete();

            $table->foreign('id_lo')
                ->references('id_lo')
                ->on('lo_thuocs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_phieu_nhaps');
    }
};
