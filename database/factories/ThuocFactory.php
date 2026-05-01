<?php

namespace Database\Factories;

use App\Models\NhaSanXuat;
use App\Models\Thuoc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Thuoc>
 */
class ThuocFactory extends Factory
{
    private const DANH_MUC_NHAN_OPTIONS = [
        ['slug' => 'khong-ke-don-giam-dau-ha-sot', 'nhan' => 'Hạ sốt, giảm đau'],
        ['slug' => 'khong-ke-don-khang-di-ung', 'nhan' => 'Kháng dị ứng'],
        ['slug' => 'khong-ke-don-cam-lanh', 'nhan' => 'Ho, cảm, sổ mũi, dị ứng'],
        ['slug' => 'khong-ke-don-tieu-hoa', 'nhan' => 'Tiêu hóa'],
        ['slug' => 'ke-don-khang-sinh', 'nhan' => 'Kháng sinh'],
        ['slug' => 'ke-don-khang-viem', 'nhan' => 'Kháng viêm'],
        ['slug' => 'vitamin-thuc-pham-chuc-nang', 'nhan' => 'Vitamin, khoáng chất'],
        ['slug' => 'ho-hap', 'nhan' => 'Hô hấp'],
        ['slug' => 'cham-soc-da', 'nhan' => 'Chăm sóc da'],
        ['slug' => 'khac', 'nhan' => 'Thuốc khác'],
    ];

    private const DON_VI_OPTIONS = [
        ['don_vi_tinh' => 'viên', 'don_vi_co_so' => 'viên', 'he_so_quy_doi' => 1, 'quy_cach_don_vi' => null],
        ['don_vi_tinh' => 'hộp', 'don_vi_co_so' => 'hộp', 'he_so_quy_doi' => 1, 'quy_cach_don_vi' => null],
        ['don_vi_tinh' => 'chai', 'don_vi_co_so' => 'chai', 'he_so_quy_doi' => 1, 'quy_cach_don_vi' => null],
        ['don_vi_tinh' => 'gói', 'don_vi_co_so' => 'gói', 'he_so_quy_doi' => 1, 'quy_cach_don_vi' => null],
        ['don_vi_tinh' => 'tuýp', 'don_vi_co_so' => 'tuýp', 'he_so_quy_doi' => 1, 'quy_cach_don_vi' => null],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $danhMuc = $this->faker->randomElement(self::DANH_MUC_NHAN_OPTIONS);
        $donVi = $this->faker->randomElement(self::DON_VI_OPTIONS);
        $tenThuoc = ucwords($this->faker->words($this->faker->numberBetween(2, 4), true));

        return [
            'ma_thuoc' => strtoupper($this->faker->unique()->lexify('TH???')) . $this->faker->unique()->numerify('#####'),
            'ten_thuoc' => $tenThuoc,
            'ham_luong' => $this->faker->randomElement(['500mg', '200mg', '10ml', '5g', '100mg']),
            'don_vi_tinh' => $donVi['don_vi_tinh'],
            'don_vi_co_so' => $donVi['don_vi_co_so'],
            'he_so_quy_doi' => $donVi['he_so_quy_doi'],
            'quy_cach_don_vi' => $donVi['quy_cach_don_vi'],
            'gia_ban' => $this->faker->numberBetween(10, 500) * 1000,
            'mo_ta' => "{$tenThuoc} là sản phẩm thuộc nhóm {$danhMuc['nhan']}, phù hợp cho nhu cầu chăm sóc sức khỏe thông thường.",
            'lieu_luong' => implode("\n", [
                'Người lớn: dùng theo hướng dẫn của dược sĩ.',
                'Trẻ em: cần có tư vấn chuyên môn trước khi dùng.',
                'Không vượt quá liều khuyến nghị trong ngày.',
            ]),
            'nhan' => $danhMuc['nhan'],
            'trang_thai' => $this->faker->randomElement(['còn bán', 'ngừng bán']),
            'id_nha_san_xuat' => NhaSanXuat::query()->inRandomOrder()->value('id') ?? NhaSanXuat::factory(),
            'danh_muc_thuoc_slug' => $danhMuc['slug'],
        ];
    }
}
