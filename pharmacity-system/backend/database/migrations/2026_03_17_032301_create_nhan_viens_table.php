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
        Schema::create('nhan_viens', function (Blueprint $table) {
            $table->id('id_nhan_vien');
            $table->string('ten_dang_nhap', 50)->unique();
            $table->string('mat_khau');
            $table->string('ho_ten', 100);
            $table->unsignedBigInteger('id_vai_tro');
            $table->unsignedBigInteger('id_bang_cap');
            $table->enum('trang_thai', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('id_vai_tro')->references('id_vai_tro')->on('vai_tros')->onDelete('cascade');
            $table->foreign('id_bang_cap')->references('id_bang_cap')->on('bang_caps')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhan_viens');
    }
};
