<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoa_dons', function (Blueprint $table): void {
            $table->foreignId('ma_giam_gia_id')->nullable()->after('id_nhan_vien')->constrained('ma_giam_gias')->nullOnDelete();
            $table->decimal('giam_gia_ma', 12, 2)->default(0)->after('giam_gia');
            $table->decimal('giam_gia_diem', 12, 2)->default(0)->after('giam_gia_ma');
            $table->unsignedInteger('diem_da_su_dung')->default(0)->after('tien_thanh_toan');
            $table->unsignedInteger('diem_da_cong')->default(0)->after('diem_da_su_dung');
        });
    }

    public function down(): void
    {
        Schema::table('hoa_dons', function (Blueprint $table): void {
            $table->dropForeign(['ma_giam_gia_id']);
            $table->dropColumn([
                'ma_giam_gia_id',
                'giam_gia_ma',
                'giam_gia_diem',
                'diem_da_su_dung',
                'diem_da_cong',
            ]);
        });
    }
};
