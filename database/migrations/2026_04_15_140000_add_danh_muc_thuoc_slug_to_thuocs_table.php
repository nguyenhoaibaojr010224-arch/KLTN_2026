<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            $table->string('danh_muc_thuoc_slug', 100)->nullable()->after('id_nha_san_xuat');
        });
    }

    public function down(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            $table->dropColumn('danh_muc_thuoc_slug');
        });
    }
};
