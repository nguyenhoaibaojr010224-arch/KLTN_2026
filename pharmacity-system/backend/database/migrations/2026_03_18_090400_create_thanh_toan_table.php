<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thanh_toan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_hoa_don')->primary();
            $table->string('phuong_thuc', 50);
            $table->decimal('so_tien', 14, 2);
            $table->dateTime('thoi_gian');
            $table->string('ma_giao_dich', 100)->nullable()->unique();
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent();

            $table->foreign('id_hoa_don')
                ->references('id_hoa_don')
                ->on('hoa_dons')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thanh_toan');
    }
};
