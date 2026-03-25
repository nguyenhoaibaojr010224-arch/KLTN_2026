<?php

namespace Database\Factories;

use App\Models\LoThuoc;
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
        $ngaySanXuat = $this->faker->dateTimeBetween('-2 years', 'now');
        $hanSuDung = (clone $ngaySanXuat)->modify('+' . $this->faker->numberBetween(1, 3) . ' years');
        $soLuongNhap = $this->faker->numberBetween(100, 1000);

        return [
            'id_thuoc' => \App\Models\Thuoc::inRandomOrder()->first()?->ma_thuoc ?? \App\Models\Thuoc::factory()->create()->ma_thuoc,
            'so_lo' => strtoupper($this->faker->unique()->lexify('LO-????')) . $this->faker->unique()->numerify('#####'),
            'ngay_san_xuat' => $ngaySanXuat->format('Y-m-d'),
            'han_su_dung' => $hanSuDung->format('Y-m-d'),
            'so_luong_nhap' => $soLuongNhap,
            'so_luong_con' => $this->faker->numberBetween(0, $soLuongNhap),
            'gia_nhap' => $this->faker->numberBetween(5, 400) * 1000,
        ];
    }
}
