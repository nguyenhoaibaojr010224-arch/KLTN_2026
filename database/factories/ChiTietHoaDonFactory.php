<?php

namespace Database\Factories;

use App\Models\ChiTietHoaDon;
use App\Models\HoaDon;
use App\Models\LoThuoc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChiTietHoaDon>
 */
class ChiTietHoaDonFactory extends Factory
{
    public function definition(): array
    {
        $soLuong = $this->faker->numberBetween(1, 5);
        $giaBan = $this->faker->numberBetween(30, 500) * 1000;

        return [
            'id_hoa_don' => HoaDon::query()->inRandomOrder()->value('id_hoa_don') ?? HoaDon::factory(),
            'id_lo' => LoThuoc::query()->inRandomOrder()->value('id_lo') ?? LoThuoc::factory(),
            'so_luong' => $soLuong,
            'gia_ban' => $giaBan,
            'thanh_tien' => $soLuong * $giaBan,
        ];
    }
}
