<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lo_thuocs', function (Blueprint $table) {
            $table->id('id_lo');
            $table->string('id_thuoc', 10);
            $table->foreign('id_thuoc')->references('ma_thuoc')->on('thuocs')->onDelete('cascade');
            $table->string('so_lo')->unique();
            $table->date('ngay_san_xuat');
            $table->date('han_su_dung');
            $table->unsignedInteger('so_luong_nhap');
            $table->unsignedInteger('so_luong_con');
            $table->unsignedInteger('gia_nhap');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lo_thuocs');
    }
};
