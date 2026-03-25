<?php

namespace Database\Factories;

use App\Models\Thuoc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Thuoc>
 */
class ThuocFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ma_thuoc' => strtoupper($this->faker->unique()->lexify('TH???')) . $this->faker->unique()->numerify('#####'),
            'ten_thuoc' => $this->faker->words(4, true),
            'ham_luong' => $this->faker->randomElement(['500mg', '200mg', '10ml', '5g', '100mg']),
            'don_vi_tinh' => $this->faker->randomElement(['viên', 'hộp', 'chai', 'vỉ', 'tuýp']),
            'gia_ban' => $this->faker->numberBetween(10, 500) * 1000,
            'trang_thai' => $this->faker->randomElement(['còn bán', 'ngừng bán']),
            'id_loai_thuoc' => \App\Models\LoaiThuoc::factory(),
            'id_nha_san_xuat' => \App\Models\NhaSanXuat::factory(),
        ];
    }
}
