<?php

namespace Database\Factories;

use App\Models\KhuyenMai;
use App\Models\NhanVien;
use App\Models\Thuoc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KhuyenMai>
 */
class KhuyenMaiFactory extends Factory
{
    protected $model = KhuyenMai::class;

    public function definition(): array
    {
        return [
            'ma_thuoc' => Thuoc::factory(),
            'ten_khuyen_mai' => 'Khuyen mai ' . $this->faker->words(2, true),
            'mo_ta' => $this->faker->sentence(),
            'loai_ap_dung' => $this->faker->randomElement(['phan_tram', 'so_tien', 'gia_co_dinh']),
            'gia_tri' => $this->faker->numberBetween(5, 30),
            'nhan_hien_thi' => $this->faker->randomElement(['Mua 1 tang 1', 'Deal hot', 'Giam gia dac biet']),
            'ngay_bat_dau' => now()->subDay(),
            'ngay_ket_thuc' => now()->addDays(7),
            'trang_thai' => 'active',
            'id_nhan_vien' => NhanVien::query()->inRandomOrder()->value('id_nhan_vien'),
        ];
    }
}
