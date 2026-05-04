<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoa_dons', function (Blueprint $table): void {
            $table->boolean('diem_thuong_da_xu_ly')->default(false)->after('diem_da_cong');
        });

        DB::table('hoa_dons')
            ->where(function ($query): void {
                $query->where('diem_da_su_dung', '>', 0)
                    ->orWhere('diem_da_cong', '>', 0);
            })
            ->whereIn('trang_thai_xu_ly', ['cho_xac_nhan', 'da_xac_nhan', 'hoan_thanh'])
            ->update(['diem_thuong_da_xu_ly' => true]);
    }

    public function down(): void
    {
        Schema::table('hoa_dons', function (Blueprint $table): void {
            $table->dropColumn('diem_thuong_da_xu_ly');
        });
    }
};
