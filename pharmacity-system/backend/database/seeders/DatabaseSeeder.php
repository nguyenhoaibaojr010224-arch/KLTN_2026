<?php

namespace Database\Seeders;

use App\Models\BangCap;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\LoaiThuoc;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\NhaSanXuat;
use App\Models\Thuoc;
use App\Models\VaiTro;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $adminRole = VaiTro::firstOrCreate(
            ['ten_vai_tro' => 'admin'],
            ['mo_ta' => 'Quan tri vien he thong']
        );

        $staffRole = VaiTro::firstOrCreate(
            ['ten_vai_tro' => 'nhan_vien'],
            ['mo_ta' => 'Nhan vien nha thuoc']
        );

        if (BangCap::count() === 0) {
            BangCap::factory(5)->create();
        }

        NhanVien::firstOrCreate(['ten_dang_nhap' => 'admin'], [
            'mat_khau' => Hash::make('password'),
            'ho_ten' => 'Admin User',
            'id_vai_tro' => $adminRole->id_vai_tro,
            'id_bang_cap' => BangCap::query()->inRandomOrder()->value('id_bang_cap'),
            'trang_thai' => 'active',
        ]);

        NhanVien::firstOrCreate(['ten_dang_nhap' => 'staff'], [
            'mat_khau' => Hash::make('password'),
            'ho_ten' => 'Staff User',
            'id_vai_tro' => $staffRole->id_vai_tro,
            'id_bang_cap' => BangCap::query()->inRandomOrder()->value('id_bang_cap'),
            'trang_thai' => 'active',
        ]);

        $missingNhanVien = max(0, 40 - NhanVien::count());
        if ($missingNhanVien > 0) {
            NhanVien::factory($missingNhanVien)->create();
        }

        if (LoaiThuoc::count() === 0) {
            LoaiThuoc::factory(10)->create();
        }

        if (NhaSanXuat::count() === 0) {
            NhaSanXuat::factory(10)->create();
        }

        $missingThuoc = max(0, 40 - Thuoc::count());
        if ($missingThuoc > 0) {
            Thuoc::factory($missingThuoc)->create([
                'id_loai_thuoc' => fn() => LoaiThuoc::query()->inRandomOrder()->first()->id,
                'id_nha_san_xuat' => fn() => NhaSanXuat::query()->inRandomOrder()->first()->id,
            ]);
        }

        $missingLoThuoc = max(0, 40 - LoThuoc::count());
        if ($missingLoThuoc > 0) {
            LoThuoc::factory($missingLoThuoc)->create([
                'id_thuoc' => fn() => Thuoc::query()->inRandomOrder()->first()->ma_thuoc,
            ]);
        }

        $missingKhachHang = max(0, 40 - KhachHang::count());
        if ($missingKhachHang > 0) {
            KhachHang::factory($missingKhachHang)->create();
        }

        if (HoaDon::count() === 0) {
            HoaDon::factory(10)->create();
        }
    }
}
