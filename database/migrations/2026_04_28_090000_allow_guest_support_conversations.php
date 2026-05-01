<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ho_tro_hoi_thoais', function (Blueprint $table): void {
            $table->string('guest_session_id', 80)->nullable()->unique()->after('id_khach_hang');
            $table->string('guest_display_name', 100)->nullable()->after('guest_session_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE ho_tro_hoi_thoais MODIFY id_khach_hang BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        Schema::table('ho_tro_hoi_thoais', function (Blueprint $table): void {
            $table->dropUnique('ho_tro_hoi_thoais_guest_session_id_unique');
            $table->dropColumn(['guest_session_id', 'guest_display_name']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE ho_tro_hoi_thoais MODIFY id_khach_hang BIGINT UNSIGNED NOT NULL');
        }
    }
};
