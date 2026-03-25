<?php

namespace Database\Seeders;

use App\Models\LoaiThuoc;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoaiThuocSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['ten_loai' => 'Hạ sốt, giảm đau', 'mo_ta' => 'Nhóm thuốc hỗ trợ hạ sốt và giảm đau thông thường.'],
            ['ten_loai' => 'Ho, cảm, sổ mũi, dị ứng', 'mo_ta' => 'Nhóm thuốc hỗ trợ các triệu chứng ho, cảm cúm, sổ mũi và dị ứng.'],
            ['ten_loai' => 'Tiêu hóa, dạ dày', 'mo_ta' => 'Nhóm thuốc hỗ trợ rối loạn tiêu hóa và vấn đề dạ dày.'],
            ['ten_loai' => 'Táo bón', 'mo_ta' => 'Nhóm thuốc hỗ trợ nhuận tràng và cải thiện táo bón.'],
            ['ten_loai' => 'Tẩy giun', 'mo_ta' => 'Nhóm thuốc hỗ trợ tẩy giun và ký sinh trùng đường ruột.'],
            ['ten_loai' => 'Say tàu xe, chóng mặt', 'mo_ta' => 'Nhóm thuốc hỗ trợ chống say tàu xe và giảm chóng mặt.'],
            ['ten_loai' => 'Đau họng, viêm họng, súc miệng', 'mo_ta' => 'Nhóm thuốc hỗ trợ đau họng, viêm họng và chăm sóc khoang miệng.'],
            ['ten_loai' => 'Nhỏ mắt, khô mắt, dị ứng mắt', 'mo_ta' => 'Nhóm thuốc hỗ trợ chăm sóc mắt, khô mắt và dị ứng mắt.'],
            ['ten_loai' => 'Nhỏ mũi, xịt mũi', 'mo_ta' => 'Nhóm thuốc hỗ trợ làm sạch và thông thoáng mũi.'],
            ['ten_loai' => 'Sát khuẩn, da liễu', 'mo_ta' => 'Nhóm thuốc sát khuẩn và chăm sóc các vấn đề ngoài da.'],
            ['ten_loai' => 'Nấm da, nấm kẽ, nấm âm đạo', 'mo_ta' => 'Nhóm thuốc hỗ trợ điều trị các tình trạng nấm thường gặp.'],
            ['ten_loai' => 'Giảm đau, xoa bóp ngoài da', 'mo_ta' => 'Nhóm thuốc thoa ngoài da hỗ trợ giảm đau và thư giãn cơ khớp.'],
            ['ten_loai' => 'Thuốc bổ, vitamin, khoáng chất', 'mo_ta' => 'Nhóm thuốc bổ sung vitamin, khoáng chất và nâng cao thể trạng.'],
            ['ten_loai' => 'Bù nước, điện giải', 'mo_ta' => 'Nhóm thuốc hỗ trợ bù nước, bù khoáng và cân bằng điện giải.'],
            ['ten_loai' => 'Tuần hoàn tĩnh mạch, trĩ', 'mo_ta' => 'Nhóm thuốc hỗ trợ tuần hoàn tĩnh mạch và các vấn đề về trĩ.'],
        ];

        $existing = LoaiThuoc::query()->orderBy('id')->get()->values();

        foreach ($items as $index => $item) {
            $current = $existing->get($index);

            if ($current) {
                $current->update($item);
                continue;
            }

            LoaiThuoc::query()->create($item);
        }
    }
}
