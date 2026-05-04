<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDatabaseRows;
use Illuminate\Database\Seeder;

class NhanVienSeeder extends Seeder
{
    use SeedsDatabaseRows;

    public function run(): void
    {
        $this->seedTableFromJson('nhan_viens', 'nhan_viens.json', 'id_nhan_vien');
        $this->seedTableFromJson('thong_tin_nhan_viens', 'thong_tin_nhan_viens.json', 'id_nhan_vien');
    }
}
