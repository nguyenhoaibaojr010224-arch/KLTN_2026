<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            $table->string('don_vi_co_so', 50)->nullable()->after('don_vi_tinh');
            $table->unsignedInteger('he_so_quy_doi')->default(1)->after('don_vi_co_so');
        });

        DB::table('thuocs')->select(['ma_thuoc', 'don_vi_tinh', 'quy_cach_don_vi'])->orderBy('ma_thuoc')->chunkById(
            100,
            function ($thuocs): void {
                foreach ($thuocs as $thuoc) {
                    $quyCach = json_decode((string) ($thuoc->quy_cach_don_vi ?? '[]'), true);
                    if (! is_array($quyCach)) {
                        $quyCach = [];
                    }

                    $normalizedQuyCach = collect($quyCach)
                        ->filter(fn ($item): bool => is_array($item))
                        ->map(function (array $item): array {
                            return [
                                'ten_don_vi' => trim((string) ($item['ten_don_vi'] ?? '')),
                                'gia_ban' => (int) ($item['gia_ban'] ?? 0),
                                'so_luong_quy_doi' => max(1, (int) ($item['so_luong_quy_doi'] ?? 1)),
                            ];
                        })
                        ->values()
                        ->all();

                    DB::table('thuocs')
                        ->where('ma_thuoc', $thuoc->ma_thuoc)
                        ->update([
                            'don_vi_co_so' => trim((string) ($thuoc->don_vi_tinh ?? '')),
                            'he_so_quy_doi' => 1,
                            'quy_cach_don_vi' => $normalizedQuyCach === [] ? null : json_encode($normalizedQuyCach, JSON_UNESCAPED_UNICODE),
                        ]);
                }
            },
            'ma_thuoc',
            'ma_thuoc'
        );

        Schema::table('thuocs', function (Blueprint $table): void {
            $table->string('don_vi_co_so', 50)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('thuocs', function (Blueprint $table): void {
            $table->dropColumn(['don_vi_co_so', 'he_so_quy_doi']);
        });
    }
};
