<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('khuyen_mais', function (Blueprint $table): void {
            $table->string('ma_khuyen_mai', 30)->nullable()->unique()->after('ma_thuoc');
        });
    }

    public function down(): void
    {
        Schema::table('khuyen_mais', function (Blueprint $table): void {
            $table->dropUnique(['ma_khuyen_mai']);
            $table->dropColumn('ma_khuyen_mai');
        });
    }
};
