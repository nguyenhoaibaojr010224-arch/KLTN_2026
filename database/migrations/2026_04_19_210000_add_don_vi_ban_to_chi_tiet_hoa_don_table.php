<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chi_tiet_hoa_don', function (Blueprint $table) {
            $table->string('don_vi_ban', 50)->nullable()->after('id_lo');
        });
    }

    public function down(): void
    {
        Schema::table('chi_tiet_hoa_don', function (Blueprint $table) {
            $table->dropColumn('don_vi_ban');
        });
    }
};

