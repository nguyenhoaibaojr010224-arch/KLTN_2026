<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $hasLegacyLoaiThuocColumn = Schema::hasColumn('thuocs', 'id_loai_thuoc');

        Schema::table('thuocs', function (Blueprint $table): void {
            $table->text('nhan')->nullable()->after('lieu_luong');
        });

        if (! $hasLegacyLoaiThuocColumn) {
            return;
        }

        if ($driver === 'sqlite') {
            DB::statement("
                UPDATE thuocs
                SET nhan = (
                    SELECT loai_thuocs.ten_loai
                    FROM loai_thuocs
                    WHERE loai_thuocs.id = thuocs.id_loai_thuoc
                )
                WHERE (nhan IS NULL OR TRIM(nhan) = '')
                  AND id_loai_thuoc IS NOT NULL
            ");

            return;
        }

        Schema::table('thuocs', function (Blueprint $table): void {
            $table->dropForeign(['id_loai_thuoc']);
        });

        DB::statement('ALTER TABLE thuocs MODIFY id_loai_thuoc BIGINT UNSIGNED NULL');

        Schema::table('thuocs', function (Blueprint $table): void {
            $table->foreign('id_loai_thuoc')->references('id')->on('loai_thuocs')->nullOnDelete();
        });

        DB::statement("
            UPDATE thuocs t
            LEFT JOIN loai_thuocs l ON l.id = t.id_loai_thuoc
            SET t.nhan = l.ten_loai
            WHERE (t.nhan IS NULL OR TRIM(t.nhan) = '')
              AND l.ten_loai IS NOT NULL
              AND TRIM(l.ten_loai) <> ''
        ");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $hasLegacyLoaiThuocColumn = Schema::hasColumn('thuocs', 'id_loai_thuoc');

        if ($driver !== 'sqlite' && $hasLegacyLoaiThuocColumn) {
            Schema::table('thuocs', function (Blueprint $table): void {
                $table->dropForeign(['id_loai_thuoc']);
            });

            DB::statement('ALTER TABLE thuocs MODIFY id_loai_thuoc BIGINT UNSIGNED NULL');

            Schema::table('thuocs', function (Blueprint $table): void {
                $table->foreign('id_loai_thuoc')->references('id')->on('loai_thuocs')->nullOnDelete();
            });
        }

        Schema::table('thuocs', function (Blueprint $table): void {
            $table->dropColumn('nhan');
        });
    }
};
