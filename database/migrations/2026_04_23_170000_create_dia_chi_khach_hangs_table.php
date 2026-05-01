<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dia_chi_khach_hangs', function (Blueprint $table) {
            $table->id('id_dia_chi');
            $table->foreignId('id_khach_hang')
                ->constrained('khach_hangs', 'id_khach_hang')
                ->cascadeOnDelete();
            $table->string('ho_ten', 100);
            $table->string('so_dien_thoai', 20);
            $table->string('tinh_thanh', 100)->nullable();
            $table->string('quan_huyen', 100)->nullable();
            $table->string('phuong_xa', 100)->nullable();
            $table->string('so_nha', 255)->nullable();
            $table->string('loai_dia_chi', 50)->default('Nhà riêng');
            $table->boolean('mac_dinh')->default(false);
            $table->timestamps();
        });

        $customers = DB::table('khach_hangs')
            ->select(['id_khach_hang', 'ten_khach_hang', 'so_dien_thoai', 'dia_chi'])
            ->whereNotNull('dia_chi')
            ->get();

        $timestamp = now();

        foreach ($customers as $customer) {
            $rawAddress = trim((string) ($customer->dia_chi ?? ''));

            if ($rawAddress === '') {
                continue;
            }

            [$soNha, $phuongXa, $quanHuyen, $tinhThanh] = $this->splitAddress($rawAddress);

            DB::table('dia_chi_khach_hangs')->insert([
                'id_khach_hang' => $customer->id_khach_hang,
                'ho_ten' => $customer->ten_khach_hang ?: 'Khách hàng',
                'so_dien_thoai' => $customer->so_dien_thoai ?: '',
                'tinh_thanh' => $tinhThanh,
                'quan_huyen' => $quanHuyen,
                'phuong_xa' => $phuongXa,
                'so_nha' => $soNha,
                'loai_dia_chi' => 'Nhà riêng',
                'mac_dinh' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dia_chi_khach_hangs');
    }

    private function splitAddress(string $rawAddress): array
    {
        $segments = array_values(array_filter(array_map(
            static fn (?string $value): string => trim((string) $value),
            explode(',', $rawAddress)
        )));

        if (count($segments) >= 4) {
            return [
                implode(', ', array_slice($segments, 0, -3)),
                $segments[count($segments) - 3] ?? null,
                $segments[count($segments) - 2] ?? null,
                $segments[count($segments) - 1] ?? null,
            ];
        }

        if (count($segments) === 3) {
            return [
                $segments[0] ?? null,
                null,
                $segments[1] ?? null,
                $segments[2] ?? null,
            ];
        }

        if (count($segments) === 2) {
            return [
                $segments[0] ?? null,
                null,
                null,
                $segments[1] ?? null,
            ];
        }

        return [$rawAddress, null, null, null];
    }
};
