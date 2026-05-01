<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            $table->json('quy_cach_don_vi')->nullable()->after('don_vi_tinh');
        });
    }

    public function down(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            $table->dropColumn('quy_cach_don_vi');
        });
    }
};
