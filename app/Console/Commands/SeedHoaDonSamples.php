<?php

namespace App\Console\Commands;

use App\Models\HoaDon;
use Illuminate\Console\Command;

class SeedHoaDonSamples extends Command
{
    protected $signature = 'hoa-dons:seed {count=10 : Supported values: 5, 10, 20, 40} {--fresh : Xoa du lieu hoa_don hien tai truoc khi tao moi}';

    protected $description = 'Generate sample hoa_don data with supported sizes 5, 10, 20, or 40 rows.';

    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $supportedCounts = [5, 10, 20, 40];

        if (! in_array($count, $supportedCounts, true)) {
            $this->error('Count khong hop le. Chi chap nhan 5, 10, 20 hoac 40.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            HoaDon::query()->delete();
        }

        HoaDon::factory($count)->create();

        $this->info("Da tao {$count} hoa don mau thanh cong.");

        return self::SUCCESS;
    }
}
