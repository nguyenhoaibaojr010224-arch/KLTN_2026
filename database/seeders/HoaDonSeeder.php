<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsDatabaseRows;
use Illuminate\Database\Seeder;

class HoaDonSeeder extends Seeder
{
    use SeedsDatabaseRows;

    public function run(): void
    {
        $this->seedTableFromJson('hoa_dons', 'hoa_dons.json', 'id_hoa_don');
        $this->seedTableFromJson('chi_tiet_hoa_don', 'chi_tiet_hoa_don.json', 'id');
        $this->seedTableFromJson('thanh_toan', 'thanh_toan.json', 'id_hoa_don');
        $this->seedTableFromJson('lich_su_don_hangs', 'lich_su_don_hangs.json', 'id_lich_su');
    }
}
