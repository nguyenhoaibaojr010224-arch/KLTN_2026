<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thong_tin_nhan_viens', function (Blueprint $table) {
            if (! Schema::hasColumn('thong_tin_nhan_viens', 'gioi_tinh')) {
                $table->string('gioi_tinh', 20)->nullable()->after('ngay_sinh');
            }
        });
    }

    public function down(): void
    {
        Schema::table('thong_tin_nhan_viens', function (Blueprint $table) {
            if (Schema::hasColumn('thong_tin_nhan_viens', 'gioi_tinh')) {
                $table->dropColumn('gioi_tinh');
            }
        });
    }
};
