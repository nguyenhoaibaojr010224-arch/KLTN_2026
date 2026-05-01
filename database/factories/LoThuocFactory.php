<?php

namespace Database\Factories;

use App\Models\LoThuoc;
use App\Models\Thuoc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoThuoc>
 */
class LoThuocFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $thuoc = Thuoc::query()->inRandomOrder()->first() ?? Thuoc::factory()->create();
        $ngaySanXuat = $this->faker->dateTimeBetween('-2 years', 'now');
        $hanSuDung = (clone $ngaySanXuat)->modify('+' . $this->faker->numberBetween(1, 3) . ' years');
        $soLuongNhapGoc = $this->faker->numberBetween(10, 200);
        $heSoQuyDoi = max(1, (int) ($thuoc->he_so_quy_doi ?? 1));
        $soLuongNhapCoSo = $soLuongNhapGoc * $heSoQuyDoi;
        $giaNhap = $this->faker->numberBetween(5, 400) * 1000;

        return [
            'id_thuoc' => $thuoc->ma_thuoc,
            'so_lo' => strtoupper($this->faker->unique()->lexify('LO-????')) . $this->faker->unique()->numerify('#####'),
            'ngay_san_xuat' => $ngaySanXuat->format('Y-m-d'),
            'han_su_dung' => $hanSuDung->format('Y-m-d'),
            'don_vi_nhap' => $thuoc->don_vi_tinh,
            'don_vi_co_so' => $thuoc->don_vi_co_so ?: $thuoc->don_vi_tinh,
            'so_luong_nhap' => $soLuongNhapCoSo,
            'so_luong_nhap_goc' => $soLuongNhapGoc,
            'so_luong_con' => $this->faker->numberBetween(0, $soLuongNhapCoSo),
            'he_so_quy_doi_nhap' => $heSoQuyDoi,
            'gia_nhap' => $giaNhap,
            'gia_nhap_quy_doi' => round($giaNhap / $heSoQuyDoi, 2),
        ];
    }
}
