<?php

namespace Database\Factories;

use App\Models\NhaSanXuat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NhaSanXuat>
 */
class NhaSanXuatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ten_nha_san_xuat' => $this->faker->company(),
            'quoc_gia' => $this->faker->country(),
        ];
    }
}
