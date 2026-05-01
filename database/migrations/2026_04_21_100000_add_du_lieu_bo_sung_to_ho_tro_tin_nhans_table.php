<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ho_tro_tin_nhans', function (Blueprint $table) {
            $table->json('du_lieu_bo_sung')->nullable()->after('noi_dung');
        });
    }

    public function down(): void
    {
        Schema::table('ho_tro_tin_nhans', function (Blueprint $table) {
            $table->dropColumn('du_lieu_bo_sung');
        });
    }
};
