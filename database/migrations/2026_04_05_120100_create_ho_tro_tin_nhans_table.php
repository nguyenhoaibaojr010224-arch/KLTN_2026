<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ho_tro_tin_nhans', function (Blueprint $table) {
            $table->id('id_tin_nhan');
            $table->unsignedBigInteger('id_hoi_thoai');
            $table->string('nguoi_gui_loai', 20);
            $table->unsignedBigInteger('id_khach_hang')->nullable();
            $table->unsignedBigInteger('id_nhan_vien')->nullable();
            $table->text('noi_dung');
            $table->boolean('da_doc')->default(false);
            $table->dateTime('thoi_gian');
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent();

            $table->foreign('id_hoi_thoai')
                ->references('id_hoi_thoai')
                ->on('ho_tro_hoi_thoais')
                ->cascadeOnDelete();

            $table->foreign('id_khach_hang')
                ->references('id_khach_hang')
                ->on('khach_hangs')
                ->nullOnDelete();

            $table->foreign('id_nhan_vien')
                ->references('id_nhan_vien')
                ->on('nhan_viens')
                ->nullOnDelete();

            $table->index(['id_hoi_thoai', 'thoi_gian']);
            $table->index(['nguoi_gui_loai', 'da_doc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ho_tro_tin_nhans');
    }
};
