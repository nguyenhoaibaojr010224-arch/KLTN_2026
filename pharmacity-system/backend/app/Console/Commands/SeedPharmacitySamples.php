<?php

namespace App\Console\Commands;

use App\Models\ChiTietHoaDon;
use App\Models\ChiTietPhieuNhap;
use App\Models\EmailVerification;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\LichSuDonHang;
use App\Models\LoThuoc;
use App\Models\NhanVien;
use App\Models\PasswordReset;
use App\Models\PhieuNhap;
use App\Models\ThanhToan;
use App\Models\ThongTinNhanVien;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedPharmacitySamples extends Command
{
    protected $signature = 'pharmacity:seed-samples {count=10 : Supported values: 5, 10, 20, 40} {--fresh : Xoa du lieu sample hien tai truoc khi tao lai}';

    protected $description = 'Generate coherent sample data for the remaining Pharmacity modules.';

    public function handle(): int
    {
        $count = (int) $this->argument('count');

        if (! in_array($count, [5, 10, 20, 40], true)) {
            $this->error('Count khong hop le. Chi chap nhan 5, 10, 20 hoac 40.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->clearSampleTables();
        }

        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);

        DB::transaction(function () use ($count): void {
            $this->seedThongTinNhanVien($count);
            $this->seedPhieuNhapAndDetails($count);
            $this->seedHoaDonAndDetails($count);
            $this->seedThanhToan($count);
            $this->seedLichSuDonHang($count);
            $this->seedEmailVerifications($count);
            $this->seedPasswordResets($count);
        });

        $this->info("Da tao sample data cho cac module con thieu voi muc {$count} dong.");

        return self::SUCCESS;
    }

    private function clearSampleTables(): void
    {
        ThanhToan::query()->delete();
        LichSuDonHang::query()->delete();
        ChiTietHoaDon::query()->delete();
        HoaDon::query()->delete();
        ChiTietPhieuNhap::query()->delete();
        PhieuNhap::query()->delete();
        ThongTinNhanVien::query()->delete();
        EmailVerification::query()->delete();
        PasswordReset::query()->delete();
    }

    private function seedThongTinNhanVien(int $count): void
    {
        $nhanViens = NhanVien::query()
            ->whereNotIn('id_nhan_vien', ThongTinNhanVien::query()->pluck('id_nhan_vien'))
            ->limit($count)
            ->get();

        foreach ($nhanViens as $nhanVien) {
            ThongTinNhanVien::factory()->create([
                'id_nhan_vien' => $nhanVien->id_nhan_vien,
            ]);
        }
    }

    private function seedPhieuNhapAndDetails(int $count): void
    {
        $loThuocs = LoThuoc::query()->inRandomOrder()->limit($count)->get();

        foreach ($loThuocs as $index => $loThuoc) {
            $phieuNhap = PhieuNhap::factory()->create();
            $soLuong = 10 + $index;
            $giaNhap = (float) $loThuoc->gia_nhap;

            ChiTietPhieuNhap::create([
                'id_phieu_nhap' => $phieuNhap->id_phieu_nhap,
                'id_lo' => $loThuoc->id_lo,
                'so_luong' => $soLuong,
                'gia_nhap' => $giaNhap,
            ]);

            $loThuoc->so_luong_nhap += $soLuong;
            $loThuoc->so_luong_con += $soLuong;
            $loThuoc->save();

            $phieuNhap->update([
                'tong_tien' => $soLuong * $giaNhap,
            ]);
        }
    }

    private function seedHoaDonAndDetails(int $count): void
    {
        $khachHangs = KhachHang::query()->inRandomOrder()->limit($count)->get()->values();
        $nhanViens = NhanVien::query()->where('trang_thai', 'active')->inRandomOrder()->limit($count)->get()->values();
        $loThuocs = LoThuoc::query()->where('so_luong_con', '>', 0)->inRandomOrder()->limit($count)->get()->values();

        $limit = min($khachHangs->count(), $nhanViens->count(), $loThuocs->count(), $count);

        for ($i = 0; $i < $limit; $i++) {
            $loThuoc = $loThuocs[$i];
            $soLuong = min(3, max(1, (int) $loThuoc->so_luong_con));
            $giaBan = (float) ($loThuoc->thuoc?->gia_ban ?? 100000);
            $tongTien = $soLuong * $giaBan;
            $giamGia = $i % 2 === 0 ? 0 : min(50000, $tongTien * 0.1);

            $hoaDon = HoaDon::create([
                'ma_hoa_don' => 'HDAUTO' . now()->format('His') . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'id_khach_hang' => $khachHangs[$i]->id_khach_hang,
                'id_nhan_vien' => $nhanViens[$i]->id_nhan_vien,
                'tong_tien' => $tongTien,
                'giam_gia' => $giamGia,
                'tien_thanh_toan' => $tongTien - $giamGia,
                'ngay_ban' => now()->subDays($i),
            ]);

            ChiTietHoaDon::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'id_lo' => $loThuoc->id_lo,
                'so_luong' => $soLuong,
                'gia_ban' => $giaBan,
                'thanh_tien' => $tongTien,
            ]);

            $loThuoc->so_luong_con -= $soLuong;
            $loThuoc->save();
        }
    }

    private function seedThanhToan(int $count): void
    {
        $hoaDons = HoaDon::query()
            ->whereNotIn('id_hoa_don', ThanhToan::query()->pluck('id_hoa_don'))
            ->limit($count)
            ->get();

        foreach ($hoaDons as $index => $hoaDon) {
            ThanhToan::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'phuong_thuc' => ['tien_mat', 'chuyen_khoan', 'the'][$index % 3],
                'so_tien' => $hoaDon->tien_thanh_toan,
                'thoi_gian' => now()->subMinutes($index * 10),
                'ma_giao_dich' => $index % 3 === 0 ? null : 'GD' . strtoupper(Str::random(8)),
            ]);
        }
    }

    private function seedLichSuDonHang(int $count): void
    {
        $hoaDons = HoaDon::query()->limit($count)->get();
        $nhanVienIds = NhanVien::query()->pluck('id_nhan_vien')->all();
        $trangThaiList = ['moi_tao', 'da_thanh_toan', 'dang_xu_ly', 'hoan_tat', 'huy'];

        foreach ($hoaDons as $index => $hoaDon) {
            LichSuDonHang::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'trang_thai' => $trangThaiList[$index % count($trangThaiList)],
                'ghi_chu' => 'Sample lich su don hang ' . ($index + 1),
                'thoi_gian' => now()->subHours($index),
                'id_nhan_vien' => $nhanVienIds[$index % count($nhanVienIds)],
            ]);
        }
    }

    private function seedEmailVerifications(int $count): void
    {
        $emails = KhachHang::query()->inRandomOrder()->limit($count)->pluck('email');

        foreach ($emails as $index => $email) {
            EmailVerification::create([
                'email' => $email,
                'token' => Str::random(64),
                'expired_at' => now()->addHours(24 + $index),
                'verified' => $index % 2 === 0,
            ]);
        }
    }

    private function seedPasswordResets(int $count): void
    {
        $emails = KhachHang::query()->inRandomOrder()->limit($count)->pluck('email');

        foreach ($emails as $index => $email) {
            PasswordReset::create([
                'email' => $email,
                'token' => Hash::make($email . $index . now()->timestamp),
                'expired_at' => now()->addMinutes(30 + $index),
                'used' => false,
            ]);
        }
    }
}
