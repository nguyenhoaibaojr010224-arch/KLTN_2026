<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            VaiTroSeeder::class,
            BangCapSeeder::class,
            NhaSanXuatSeeder::class,
            NhanVienSeeder::class,
            KhachHangSeeder::class,
            ThuocSeeder::class,
            LoThuocSeeder::class,
            KhuyenMaiSeeder::class,
            MaGiamGiaSeeder::class,
            PhieuNhapSeeder::class,
            HoaDonSeeder::class,
            HoTroSeeder::class,
            DoanhThuSeeder::class,
        ]);
    }
}
