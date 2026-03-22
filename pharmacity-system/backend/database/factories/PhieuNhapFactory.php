<?php

namespace Database\Factories;

use App\Models\NhanVien;
use App\Models\NhaSanXuat;
use App\Models\PhieuNhap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhieuNhap>
 */
class PhieuNhapFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_nha_san_xuat' => NhaSanXuat::query()->inRandomOrder()->value('id') ?? NhaSanXuat::factory(),
            'id_nhan_vien' => NhanVien::query()->inRandomOrder()->value('id_nhan_vien') ?? NhanVien::factory(),
            'tong_tien' => 0,
            'ngay_nhap' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
