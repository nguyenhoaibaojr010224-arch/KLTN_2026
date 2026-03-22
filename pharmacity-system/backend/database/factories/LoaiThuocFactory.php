<?php

namespace Database\Factories;

use App\Models\LoaiThuoc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoaiThuoc>
 */
class LoaiThuocFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ten_loai' => $this->faker->words(3, true),
            'mo_ta' => $this->faker->sentence(),
        ];
    }
}
