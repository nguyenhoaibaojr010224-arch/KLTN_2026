<?php

namespace Database\Factories;

use App\Models\VaiTro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VaiTro>
 */
class VaiTroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ten_vai_tro' => $this->faker->unique()->randomElement(['admin', 'nhân viên', 'quản lý', 'kế toán']),
            'mo_ta' => $this->faker->sentence(),
        ];
    }
}
