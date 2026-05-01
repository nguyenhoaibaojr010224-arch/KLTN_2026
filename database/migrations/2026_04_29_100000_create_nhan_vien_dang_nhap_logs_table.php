<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nhan_vien_dang_nhap_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_nhan_vien');
            $table->timestamp('thoi_gian_dang_nhap')->useCurrent();
            $table->string('dia_chi_ip', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('id_nhan_vien')
                ->references('id_nhan_vien')
                ->on('nhan_viens')
                ->cascadeOnDelete();

            $table->index(['id_nhan_vien', 'thoi_gian_dang_nhap'], 'nv_login_logs_employee_time_index');
            $table->index('thoi_gian_dang_nhap', 'nv_login_logs_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhan_vien_dang_nhap_logs');
    }
};
