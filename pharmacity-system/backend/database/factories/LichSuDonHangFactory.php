<?php

namespace Database\Factories;

use App\Models\HoaDon;
use App\Models\LichSuDonHang;
use App\Models\NhanVien;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LichSuDonHang>
 */
class LichSuDonHangFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_hoa_don' => HoaDon::query()->inRandomOrder()->value('id_hoa_don') ?? HoaDon::factory(),
            'trang_thai' => $this->faker->randomElement(['moi_tao', 'da_thanh_toan', 'dang_xu_ly', 'hoan_tat', 'huy']),
            'ghi_chu' => $this->faker->optional()->sentence(),
            'thoi_gian' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'id_nhan_vien' => NhanVien::query()->inRandomOrder()->value('id_nhan_vien') ?? NhanVien::factory(),
        ];
    }
}
