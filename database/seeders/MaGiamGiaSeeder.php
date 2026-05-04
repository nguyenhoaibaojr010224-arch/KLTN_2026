<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDatabaseRows;
use Illuminate\Database\Seeder;

class MaGiamGiaSeeder extends Seeder
{
    use SeedsDatabaseRows;

    public function run(): void
    {
        $this->seedTableFromJson('ma_giam_gias', 'ma_giam_gias.json', 'id');
        $this->seedTableFromJson('ma_giam_gia_luot_dungs', 'ma_giam_gia_luot_dungs.json', 'id');
    }
}
