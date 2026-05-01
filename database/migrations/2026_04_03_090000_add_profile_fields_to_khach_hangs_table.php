<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('khach_hangs', function (Blueprint $table) {
            if (! Schema::hasColumn('khach_hangs', 'ngay_sinh')) {
                $table->date('ngay_sinh')->nullable()->after('dia_chi');
            }

            if (! Schema::hasColumn('khach_hangs', 'gioi_tinh')) {
                $table->string('gioi_tinh', 20)->nullable()->after('ngay_sinh');
            }
        });
    }

    public function down(): void
    {
        Schema::table('khach_hangs', function (Blueprint $table) {
            if (Schema::hasColumn('khach_hangs', 'gioi_tinh')) {
                $table->dropColumn('gioi_tinh');
            }

            if (Schema::hasColumn('khach_hangs', 'ngay_sinh')) {
                $table->dropColumn('ngay_sinh');
            }
        });
    }
};
