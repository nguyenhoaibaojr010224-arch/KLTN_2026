<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lo_thuocs', function (Blueprint $table): void {
            $table->string('don_vi_nhap', 50)->nullable()->after('han_su_dung');
            $table->string('don_vi_co_so', 50)->nullable()->after('don_vi_nhap');
            $table->unsignedInteger('so_luong_nhap_goc')->nullable()->after('so_luong_nhap');
            $table->unsignedInteger('he_so_quy_doi_nhap')->default(1)->after('so_luong_nhap_goc');
            $table->decimal('gia_nhap_quy_doi', 12, 2)->nullable()->after('gia_nhap');
        });

        DB::table('lo_thuocs')
            ->join('thuocs', 'thuocs.ma_thuoc', '=', 'lo_thuocs.id_thuoc')
            ->select([
                'lo_thuocs.id_lo',
                'lo_thuocs.so_luong_nhap',
                'lo_thuocs.gia_nhap',
                'thuocs.don_vi_tinh',
                'thuocs.don_vi_co_so',
            ])
            ->orderBy('lo_thuocs.id_lo')
            ->chunk(100, function ($lots): void {
                foreach ($lots as $lot) {
                    DB::table('lo_thuocs')
                        ->where('id_lo', $lot->id_lo)
                        ->update([
                            'don_vi_nhap' => trim((string) ($lot->don_vi_tinh ?? '')),
                            'don_vi_co_so' => trim((string) ($lot->don_vi_co_so ?? $lot->don_vi_tinh ?? '')),
                            'so_luong_nhap_goc' => (int) ($lot->so_luong_nhap ?? 0),
                            'he_so_quy_doi_nhap' => 1,
                            'gia_nhap_quy_doi' => (float) ($lot->gia_nhap ?? 0),
                        ]);
                }
            });

        Schema::table('lo_thuocs', function (Blueprint $table): void {
            $table->string('don_vi_nhap', 50)->nullable(false)->change();
            $table->string('don_vi_co_so', 50)->nullable(false)->change();
            $table->unsignedInteger('so_luong_nhap_goc')->nullable(false)->change();
            $table->decimal('gia_nhap_quy_doi', 12, 2)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('lo_thuocs', function (Blueprint $table): void {
            $table->dropColumn([
                'don_vi_nhap',
                'don_vi_co_so',
                'so_luong_nhap_goc',
                'he_so_quy_doi_nhap',
                'gia_nhap_quy_doi',
            ]);
        });
    }
};
