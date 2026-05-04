<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDatabaseRows;
use Illuminate\Database\Seeder;

class PhieuNhapSeeder extends Seeder
{
    use SeedsDatabaseRows;

    public function run(): void
    {
        $this->seedTableFromJson('phieu_nhaps', 'phieu_nhaps.json', 'id_phieu_nhap');
        $this->seedTableFromJson('chi_tiet_phieu_nhaps', 'chi_tiet_phieu_nhaps.json', 'id');
    }
}
