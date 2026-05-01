<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            $table->text('lieu_luong')->nullable()->after('mo_ta');
        });
    }

    public function down(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            $table->dropColumn('lieu_luong');
        });
    }
};
