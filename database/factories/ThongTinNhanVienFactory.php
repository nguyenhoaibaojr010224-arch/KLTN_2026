<?php

namespace Database\Factories;

use App\Models\NhanVien;
use App\Models\ThongTinNhanVien;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThongTinNhanVien>
 */
class ThongTinNhanVienFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_nhan_vien' => NhanVien::query()->inRandomOrder()->value('id_nhan_vien') ?? NhanVien::factory(),
            'so_dien_thoai' => $this->faker->unique()->numerify('09########'),
            'email' => $this->faker->unique()->safeEmail(),
            'dia_chi' => $this->faker->address(),
            'ngay_sinh' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'ngay_vao_lam' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
        ];
    }
}
