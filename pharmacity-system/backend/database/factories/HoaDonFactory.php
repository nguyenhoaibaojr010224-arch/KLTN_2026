<?php

namespace Database\Factories;

use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\NhanVien;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HoaDon>
 */
class HoaDonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tongTien = $this->faker->numberBetween(100000, 3000000);
        $giamGia = $this->faker->numberBetween(0, (int) floor($tongTien * 0.25));

        return [
            'ma_hoa_don' => $this->faker->unique()->numerify('HD########'),
            'id_khach_hang' => KhachHang::query()->inRandomOrder()->value('id_khach_hang') ?? KhachHang::factory(),
            'id_nhan_vien' => NhanVien::query()
                ->where('trang_thai', 'active')
                ->inRandomOrder()
                ->value('id_nhan_vien') ?? NhanVien::factory(),
            'tong_tien' => $tongTien,
            'giam_gia' => $giamGia,
            'tien_thanh_toan' => $tongTien - $giamGia,
            'ngay_ban' => $this->faker->dateTimeBetween('-60 days', 'now'),
        ];
    }
}
