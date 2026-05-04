<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDatabaseRows;
use Illuminate\Database\Seeder;

class LoThuocSeeder extends Seeder
{
    use SeedsDatabaseRows;

    public function run(): void
    {
        $this->seedTableFromJson('lo_thuocs', 'lo_thuocs.json', 'id_lo');
    }
}
