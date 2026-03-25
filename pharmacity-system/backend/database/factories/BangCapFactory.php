<?php

namespace Database\Factories;

use App\Models\BangCap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BangCap>
 */
class BangCapFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ten_bang_cap' => $this->faker->unique()->randomElement([
                'Dai hoc Duoc',
                'Cao dang Duoc',
                'Trung cap Duoc',
                'Cu nhan Kinh te',
                'Duoc si chuyen khoa',
                'Thac si Duoc',
            ]),
        ];
    }
}
