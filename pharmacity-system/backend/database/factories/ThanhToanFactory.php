<?php

namespace Database\Factories;

use App\Models\HoaDon;
use App\Models\ThanhToan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThanhToan>
 */
class ThanhToanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_hoa_don' => HoaDon::query()->inRandomOrder()->value('id_hoa_don') ?? HoaDon::factory(),
            'phuong_thuc' => $this->faker->randomElement(['tien_mat', 'chuyen_khoan', 'the']),
            'so_tien' => $this->faker->numberBetween(50, 5000) * 1000,
            'thoi_gian' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'ma_giao_dich' => strtoupper($this->faker->optional(0.7)->bothify('GD-#####??')) ?: null,
        ];
    }
}
