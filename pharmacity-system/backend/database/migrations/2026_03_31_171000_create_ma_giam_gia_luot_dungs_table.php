<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ma_giam_gia_luot_dungs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ma_giam_gia_id')->constrained('ma_giam_gias')->cascadeOnDelete();
            $table->unsignedBigInteger('id_khach_hang');
            $table->unsignedInteger('so_lan_su_dung')->default(0);
            $table->timestamp('lan_su_dung_cuoi')->nullable();
            $table->timestamps();

            $table->foreign('id_khach_hang')->references('id_khach_hang')->on('khach_hangs')->cascadeOnDelete();
            $table->unique(['ma_giam_gia_id', 'id_khach_hang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ma_giam_gia_luot_dungs');
    }
};
