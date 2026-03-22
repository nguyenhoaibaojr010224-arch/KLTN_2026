<?php

namespace Database\Factories;

use App\Models\NhanVien;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NhanVien>
 */
class NhanVienFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ten_dang_nhap' => $this->faker->unique()->userName(),
            'mat_khau' => \Illuminate\Support\Facades\Hash::make('password'),
            'ho_ten' => $this->faker->name(),
            'id_vai_tro' => \App\Models\VaiTro::inRandomOrder()->first()->id_vai_tro ?? \App\Models\VaiTro::factory(),
            'id_bang_cap' => \App\Models\BangCap::inRandomOrder()->first()->id_bang_cap ?? \App\Models\BangCap::factory(),
            'trang_thai' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
