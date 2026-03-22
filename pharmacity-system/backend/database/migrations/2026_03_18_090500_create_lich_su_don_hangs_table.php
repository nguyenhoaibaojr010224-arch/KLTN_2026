<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lich_su_don_hangs', function (Blueprint $table) {
            $table->id('id_lich_su');
            $table->unsignedBigInteger('id_hoa_don');
            $table->string('trang_thai', 50);
            $table->text('ghi_chu')->nullable();
            $table->dateTime('thoi_gian');
            $table->unsignedBigInteger('id_nhan_vien');
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent();

            $table->foreign('id_hoa_don')
                ->references('id_hoa_don')
                ->on('hoa_dons')
                ->cascadeOnDelete();

            $table->foreign('id_nhan_vien')
                ->references('id_nhan_vien')
                ->on('nhan_viens')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_su_don_hangs');
    }
};
