<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDatabaseRows;
use Illuminate\Database\Seeder;

class HoTroSeeder extends Seeder
{
    use SeedsDatabaseRows;

    public function run(): void
    {
        $this->seedTableFromJson('ho_tro_hoi_thoais', 'ho_tro_hoi_thoais.json', 'id_hoi_thoai');
        $this->seedTableFromJson('ho_tro_tin_nhans', 'ho_tro_tin_nhans.json', 'id_tin_nhan');
    }
}
