<?php

namespace Database\Factories;

use App\Models\ChiTietPhieuNhap;
use App\Models\LoThuoc;
use App\Models\PhieuNhap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChiTietPhieuNhap>
 */
class ChiTietPhieuNhapFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_phieu_nhap' => PhieuNhap::query()->inRandomOrder()->value('id_phieu_nhap') ?? PhieuNhap::factory(),
            'id_lo' => LoThuoc::query()->inRandomOrder()->value('id_lo') ?? LoThuoc::factory(),
            'so_luong' => $this->faker->numberBetween(10, 100),
            'gia_nhap' => $this->faker->numberBetween(20, 400) * 1000,
        ];
    }
}
