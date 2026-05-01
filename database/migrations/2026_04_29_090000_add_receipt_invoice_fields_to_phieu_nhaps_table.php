<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phieu_nhaps', function (Blueprint $table): void {
            $table->string('ma_phieu_nhap', 30)->nullable()->unique()->after('id_phieu_nhap');
            $table->string('so_hoa_don_giay', 100)->nullable()->after('id_nhan_vien');
            $table->date('ngay_hoa_don')->nullable()->after('so_hoa_don_giay');
            $table->string('chung_tu_url')->nullable()->after('ngay_hoa_don');
            $table->text('ghi_chu')->nullable()->after('chung_tu_url');
        });

        Schema::table('chi_tiet_phieu_nhaps', function (Blueprint $table): void {
            $table->string('don_vi_nhap', 50)->nullable()->after('id_lo');
            $table->string('don_vi_co_so', 50)->nullable()->after('don_vi_nhap');
            $table->unsignedInteger('so_luong_nhap_goc')->nullable()->after('don_vi_co_so');
            $table->unsignedInteger('he_so_quy_doi_nhap')->default(1)->after('so_luong_nhap_goc');
            $table->decimal('gia_nhap_quy_doi', 14, 2)->nullable()->after('gia_nhap');
            $table->decimal('thanh_tien', 14, 2)->nullable()->after('gia_nhap_quy_doi');
        });
    }

    public function down(): void
    {
        Schema::table('chi_tiet_phieu_nhaps', function (Blueprint $table): void {
            $table->dropColumn([
                'don_vi_nhap',
                'don_vi_co_so',
                'so_luong_nhap_goc',
                'he_so_quy_doi_nhap',
                'gia_nhap_quy_doi',
                'thanh_tien',
            ]);
        });

        Schema::table('phieu_nhaps', function (Blueprint $table): void {
            $table->dropUnique(['ma_phieu_nhap']);
            $table->dropColumn([
                'ma_phieu_nhap',
                'so_hoa_don_giay',
                'ngay_hoa_don',
                'chung_tu_url',
                'ghi_chu',
            ]);
        });
    }
};
