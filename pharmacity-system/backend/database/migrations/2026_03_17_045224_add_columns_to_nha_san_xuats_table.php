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
        Schema::table('nha_san_xuats', function (Blueprint $table) {
            $table->string('nuoc_san_xuat', 100)->nullable()->after('ten_nha_san_xuat');
            $table->string('dia_chi', 200)->nullable()->after('nuoc_san_xuat');
            $table->string('so_dien_thoai', 15)->nullable()->after('dia_chi');
        });
    }

    public function down(): void
    {
        Schema::table('nha_san_xuats', function (Blueprint $table) {
            $table->dropColumn(['nuoc_san_xuat', 'dia_chi', 'so_dien_thoai']);
        });
    }
};
