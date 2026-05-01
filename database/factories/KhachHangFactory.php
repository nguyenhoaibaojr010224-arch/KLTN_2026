<?php

namespace Database\Factories;

use App\Models\KhachHang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KhachHang>
 */
class KhachHangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ten_khach_hang' => $this->faker->name(),
            'so_dien_thoai' => $this->faker->unique()->numerify('09########'),
            'email' => $this->faker->unique()->safeEmail(),
            'dia_chi' => $this->faker->address(),
            'diem_tich_luy' => $this->faker->numberBetween(0, 1000),
            'mat_khau' => \Illuminate\Support\Facades\Hash::make('password'),
            'email_verified' => true,
            'email_verified_at' => now(),
        ];
    }
}
