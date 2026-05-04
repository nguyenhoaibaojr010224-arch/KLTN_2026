<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDatabaseRows;
use Illuminate\Database\Seeder;

class KhachHangSeeder extends Seeder
{
    use SeedsDatabaseRows;

    public function run(): void
    {
        $this->seedTableFromJson('khach_hangs', 'khach_hangs.json', 'id_khach_hang');
        $this->seedTableFromJson('dia_chi_khach_hangs', 'dia_chi_khach_hangs.json', 'id_dia_chi');
    }
}
