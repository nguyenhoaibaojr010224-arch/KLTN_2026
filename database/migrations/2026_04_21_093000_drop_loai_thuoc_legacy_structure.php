<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        $hasLegacyLoaiThuocColumn = Schema::hasColumn('thuocs', 'id_loai_thuoc');

        if ($hasLegacyLoaiThuocColumn) {
            Schema::table('thuocs', function (Blueprint $table) use ($driver): void {
                if ($driver !== 'sqlite') {
                    $table->dropForeign(['id_loai_thuoc']);
                }

                $table->dropColumn('id_loai_thuoc');
            });
        }

        Schema::dropIfExists('loai_thuocs');
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::create('loai_thuocs', function (Blueprint $table): void {
            $table->id();
            $table->string('ten_loai', 100)->unique();
            $table->text('mo_ta')->nullable();
            $table->timestamps();
        });

        Schema::table('thuocs', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_loai_thuoc')->nullable()->after('hinh_anh');
        });

        if ($driver !== 'sqlite') {
            Schema::table('thuocs', function (Blueprint $table): void {
                $table->foreign('id_loai_thuoc')->references('id')->on('loai_thuocs')->nullOnDelete();
            });
        }
    }
};
