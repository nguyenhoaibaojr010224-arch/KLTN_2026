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
        $driver = Schema::getConnection()->getDriverName();

        Schema::create('thuocs', function (Blueprint $table) use ($driver) {
            $table->string('ma_thuoc', 10)->primary();
            $table->string('ten_thuoc', 100);
            $table->string('ham_luong')->nullable();
            $table->string('don_vi_tinh');
            $table->unsignedInteger('gia_ban');
            $table->enum('trang_thai', ['còn bán', 'ngừng bán'])->default('còn bán');

            if ($driver !== 'sqlite') {
                $table->foreignId('id_loai_thuoc')->constrained('loai_thuocs')->onDelete('cascade');
            }

            $table->foreignId('id_nha_san_xuat')->constrained('nha_san_xuats')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thuocs');
    }
};
