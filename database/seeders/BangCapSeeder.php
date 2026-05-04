<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDatabaseRows;
use Illuminate\Database\Seeder;

class BangCapSeeder extends Seeder
{
    use SeedsDatabaseRows;

    public function run(): void
    {
        $this->seedTableFromJson('bang_caps', 'bang_caps.json', 'id_bang_cap');
    }
}
