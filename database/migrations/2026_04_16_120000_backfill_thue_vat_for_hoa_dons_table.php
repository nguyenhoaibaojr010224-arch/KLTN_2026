<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('hoa_dons')
            ->select(['id_hoa_don', 'tong_tien', 'giam_gia'])
            ->orderBy('id_hoa_don')
            ->chunk(100, function ($hoaDons): void {
                foreach ($hoaDons as $hoaDon) {
                    $tongTien = (float) ($hoaDon->tong_tien ?? 0);
                    $giamGia = (float) ($hoaDon->giam_gia ?? 0);
                    $tamTinh = max($tongTien - $giamGia, 0);
                    $thueVat = round($tamTinh * 0.1, 2);

                    DB::table('hoa_dons')
                        ->where('id_hoa_don', $hoaDon->id_hoa_don)
                        ->update([
                            'thue_vat' => $thueVat,
                            'tien_thanh_toan' => round($tamTinh + $thueVat, 2),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('hoa_dons')
            ->select(['id_hoa_don', 'tong_tien', 'giam_gia'])
            ->orderBy('id_hoa_don')
            ->chunk(100, function ($hoaDons): void {
                foreach ($hoaDons as $hoaDon) {
                    $tongTien = (float) ($hoaDon->tong_tien ?? 0);
                    $giamGia = (float) ($hoaDon->giam_gia ?? 0);

                    DB::table('hoa_dons')
                        ->where('id_hoa_don', $hoaDon->id_hoa_don)
                        ->update([
                            'thue_vat' => 0,
                            'tien_thanh_toan' => round(max($tongTien - $giamGia, 0), 2),
                        ]);
                }
            });
    }
};
