<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chi_tiet_hoa_don', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_hoa_don');
            $table->unsignedBigInteger('id_lo');
            $table->unsignedInteger('so_luong');
            $table->decimal('gia_ban', 14, 2);
            $table->decimal('thanh_tien', 14, 2)->default(0);
            $table->timestamp('ngay_tao')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent();

            $table->foreign('id_hoa_don')
                ->references('id_hoa_don')
                ->on('hoa_dons')
                ->cascadeOnDelete();

            $table->foreign('id_lo')
                ->references('id_lo')
                ->on('lo_thuocs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_hoa_don');
    }
};
