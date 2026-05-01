<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            $table->text('mo_ta')->nullable()->after('gia_ban');
        });
    }

    public function down(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            $table->dropColumn('mo_ta');
        });
    }
};
