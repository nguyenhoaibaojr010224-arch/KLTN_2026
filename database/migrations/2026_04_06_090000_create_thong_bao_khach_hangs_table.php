<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thong_bao_khach_hangs', function (Blueprint $table): void {
            $table->id('id_thong_bao');
            $table->string('nhom', 50);
            $table->string('tieu_de', 180);
            $table->text('noi_dung');
            $table->string('loai_gui', 30)->default('broadcast');
            $table->string('doi_tuong', 50)->default('tat_ca_khach_hang');
            $table->dateTime('ngay_bat_dau')->nullable();
            $table->dateTime('ngay_ket_thuc')->nullable();
            $table->enum('trang_thai', ['draft', 'active', 'inactive'])->default('active');
            $table->unsignedBigInteger('id_nhan_vien_tao')->nullable();
            $table->timestamps();

            $table->foreign('id_nhan_vien_tao')
                ->references('id_nhan_vien')
                ->on('nhan_viens')
                ->nullOnDelete();

            $table->index(['trang_thai', 'doi_tuong']);
            $table->index(['nhom', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thong_bao_khach_hangs');
    }
};
