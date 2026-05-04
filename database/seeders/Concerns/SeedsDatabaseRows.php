<?php

namespace Database\Seeders\Concerns;

use Database\Seeders\SeederData;
use Illuminate\Support\Facades\DB;

trait SeedsDatabaseRows
{
    protected function seedTableFromJson(string $table, string $fileName, array|string $uniqueBy): void
    {
        $rows = SeederData::rows($fileName);

        if (! is_array($rows) || $rows === []) {
            return;
        }

        $uniqueColumns = (array) $uniqueBy;

        foreach (array_chunk($rows, 200) as $chunk) {
            $columns = array_keys($chunk[0]);
            $updateColumns = array_values(array_diff($columns, $uniqueColumns));

            DB::table($table)->upsert($chunk, $uniqueColumns, $updateColumns);
        }
    }
}
