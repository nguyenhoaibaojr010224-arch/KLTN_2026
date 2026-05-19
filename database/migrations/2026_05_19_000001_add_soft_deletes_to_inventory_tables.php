<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            if (! Schema::hasColumn('thuocs', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('lo_thuocs', function (Blueprint $table): void {
            if (! Schema::hasColumn('lo_thuocs', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lo_thuocs', function (Blueprint $table): void {
            if (Schema::hasColumn('lo_thuocs', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('thuocs', function (Blueprint $table): void {
            if (Schema::hasColumn('thuocs', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
