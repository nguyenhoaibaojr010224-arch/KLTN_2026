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
        Schema::create('khach_hangs', function (Blueprint $table) {
            $table->id('id_khach_hang');
            $table->string('ten_khach_hang', 100);
            $table->string('so_dien_thoai', 10)->unique();
            $table->string('email')->unique();
            $table->string('dia_chi', 100);
            $table->unsignedInteger('diem_tich_luy')->default(0);
            $table->string('mat_khau');
            $table->boolean('email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('khach_hangs');
    }
};
