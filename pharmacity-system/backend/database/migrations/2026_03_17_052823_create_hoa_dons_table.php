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
        Schema::create('hoa_dons', function (Blueprint $table) {
            $table->id('id_hoa_don');
            $table->string('ma_hoa_don', 30)->unique();
            $table->timestamp('ngay_tao')->useCurrent();
            $table->unsignedBigInteger('id_khach_hang');
            $table->unsignedBigInteger('id_nhan_vien');
            $table->decimal('tong_tien', 12, 2)->default(0);
            $table->decimal('giam_gia', 12, 2)->default(0);
            $table->decimal('tien_thanh_toan', 12, 2)->default(0);
            $table->timestamp('ngay_ban')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent();

            $table->foreign('id_khach_hang')
                ->references('id_khach_hang')
                ->on('khach_hangs')
                ->restrictOnDelete();

            $table->foreign('id_nhan_vien')
                ->references('id_nhan_vien')
                ->on('nhan_viens')
                ->restrictOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hoa_dons');
    }
};
