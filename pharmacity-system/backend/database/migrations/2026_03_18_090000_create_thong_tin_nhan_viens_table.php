<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thong_tin_nhan_viens', function (Blueprint $table) {
            $table->unsignedBigInteger('id_nhan_vien')->primary();
            $table->string('so_dien_thoai', 10)->unique();
            $table->string('email')->unique();
            $table->string('dia_chi', 100);
            $table->date('ngay_sinh');
            $table->date('ngay_vao_lam');
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent();

            $table->foreign('id_nhan_vien')
                ->references('id_nhan_vien')
                ->on('nhan_viens')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thong_tin_nhan_viens');
    }
};
