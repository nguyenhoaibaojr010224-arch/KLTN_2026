<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chi_tiet_hoa_don', function (Blueprint $table): void {
            $table->unsignedInteger('he_so_quy_doi_ban')->default(1)->after('don_vi_ban');
        });

        DB::table('chi_tiet_hoa_don')->update([
            'he_so_quy_doi_ban' => 1,
        ]);
    }

    public function down(): void
    {
        Schema::table('chi_tiet_hoa_don', function (Blueprint $table): void {
            $table->dropColumn('he_so_quy_doi_ban');
        });
    }
};
