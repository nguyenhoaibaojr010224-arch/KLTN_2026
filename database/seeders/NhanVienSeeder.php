<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class NhanVienSeeder extends Seeder
{
    /**
     * Seed 5 nhân viên PharmaGo:
     * - Đông Khánh (admin)
     * - Hoàng Duy, Quốc Bảo, Hoài Bảo, Minh Nhựa (nhân viên)
     *
     * Mật khẩu mặc định: Admin@123
     */
    public function run(): void
    {
        $password = Hash::make('Admin@123');
        $now = now()->toDateTimeString();

        // ─── Bảng nhan_viens ───
        $nhanViens = [
            [
                'id_nhan_vien' => 1,
                'ten_dang_nhap' => 'dongkhanh',
                'mat_khau' => $password,
                'ho_ten' => 'Tô Đông Khánh',
                'id_vai_tro' => 1, // admin
                'id_bang_cap' => 1, // Chuyên Khoa
                'trang_thai' => 'active',
                'created_at' => '2025-01-15 08:00:00',
                'updated_at' => $now,
            ],
            [
                'id_nhan_vien' => 2,
                'ten_dang_nhap' => 'hoangduy',
                'mat_khau' => $password,
                'ho_ten' => 'Lê Vũ Hoàng Duy',
                'id_vai_tro' => 2, // nhan_vien
                'id_bang_cap' => 2, // Cử Nhân Dược
                'trang_thai' => 'active',
                'created_at' => '2025-01-15 08:00:00',
                'updated_at' => $now,
            ],
            [
                'id_nhan_vien' => 3,
                'ten_dang_nhap' => 'quocbao',
                'mat_khau' => $password,
                'ho_ten' => 'Nguyễn Quốc Bảo',
                'id_vai_tro' => 2,
                'id_bang_cap' => 3, // Cao Đẳng Dược
                'trang_thai' => 'active',
                'created_at' => '2025-01-15 08:00:00',
                'updated_at' => $now,
            ],
            [
                'id_nhan_vien' => 4,
                'ten_dang_nhap' => 'hoaibao',
                'mat_khau' => $password,
                'ho_ten' => 'Trần Hoài Bảo',
                'id_vai_tro' => 2,
                'id_bang_cap' => 2, // Cử Nhân Dược
                'trang_thai' => 'active',
                'created_at' => '2025-01-15 08:00:00',
                'updated_at' => $now,
            ],
            [
                'id_nhan_vien' => 5,
                'ten_dang_nhap' => 'minhnhua',
                'mat_khau' => $password,
                'ho_ten' => 'Phạm Minh Nhựa',
                'id_vai_tro' => 2,
                'id_bang_cap' => 4, // Trung Cấp Dược
                'trang_thai' => 'active',
                'created_at' => '2025-01-15 08:00:00',
                'updated_at' => $now,
            ],
        ];

        // ─── Bảng thong_tin_nhan_viens ───
        $thongTins = [
            [
                'id_nhan_vien' => 1,
                'so_dien_thoai' => '0901000001',
                'email' => 'dongkhanh@pharmago.com',
                'dia_chi' => 'TP. Hồ Chí Minh',
                'ngay_sinh' => '2000-03-15',
                'gioi_tinh' => 'Nam',
                'ngay_vao_lam' => '2025-01-15',
                'ngay_tao' => '2025-01-15 08:00:00',
                'ngay_cap_nhat' => $now,
            ],
            [
                'id_nhan_vien' => 2,
                'so_dien_thoai' => '0901000002',
                'email' => 'hoangduy@pharmago.com',
                'dia_chi' => 'TP. Hồ Chí Minh',
                'ngay_sinh' => '2001-07-22',
                'gioi_tinh' => 'Nam',
                'ngay_vao_lam' => '2025-01-15',
                'ngay_tao' => '2025-01-15 08:00:00',
                'ngay_cap_nhat' => $now,
            ],
            [
                'id_nhan_vien' => 3,
                'so_dien_thoai' => '0901000003',
                'email' => 'quocbao@pharmago.com',
                'dia_chi' => 'TP. Hồ Chí Minh',
                'ngay_sinh' => '2000-11-10',
                'gioi_tinh' => 'Nam',
                'ngay_vao_lam' => '2025-01-15',
                'ngay_tao' => '2025-01-15 08:00:00',
                'ngay_cap_nhat' => $now,
            ],
            [
                'id_nhan_vien' => 4,
                'so_dien_thoai' => '0901000004',
                'email' => 'hoaibao@pharmago.com',
                'dia_chi' => 'TP. Hồ Chí Minh',
                'ngay_sinh' => '2001-02-28',
                'gioi_tinh' => 'Nam',
                'ngay_vao_lam' => '2025-01-15',
                'ngay_tao' => '2025-01-15 08:00:00',
                'ngay_cap_nhat' => $now,
            ],
            [
                'id_nhan_vien' => 5,
                'so_dien_thoai' => '0901000005',
                'email' => 'minhnhua@pharmago.com',
                'dia_chi' => 'TP. Hồ Chí Minh',
                'ngay_sinh' => '1999-09-05',
                'gioi_tinh' => 'Nam',
                'ngay_vao_lam' => '2025-01-15',
                'ngay_tao' => '2025-01-15 08:00:00',
                'ngay_cap_nhat' => $now,
            ],
        ];

        // Upsert: cập nhật nếu đã tồn tại, tạo mới nếu chưa
        DB::table('nhan_viens')->upsert($nhanViens, ['id_nhan_vien'], [
            'ten_dang_nhap', 'mat_khau', 'ho_ten', 'id_vai_tro', 'id_bang_cap', 'trang_thai', 'updated_at',
        ]);

        DB::table('thong_tin_nhan_viens')->upsert($thongTins, ['id_nhan_vien'], [
            'so_dien_thoai', 'email', 'dia_chi', 'ngay_sinh', 'gioi_tinh', 'ngay_vao_lam', 'ngay_cap_nhat',
        ]);

        // Xóa nhân viên thừa (chỉ giữ ID 1-5)
        DB::table('thong_tin_nhan_viens')->whereNotIn('id_nhan_vien', [1, 2, 3, 4, 5])->delete();
        DB::table('nhan_viens')->whereNotIn('id_nhan_vien', [1, 2, 3, 4, 5])->delete();

        $this->command->info('✅ Đã seed 5 nhân viên PharmaGo (mật khẩu: Admin@123)');
    }
}
