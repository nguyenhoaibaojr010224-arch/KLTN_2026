<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder doanh thu 3 tháng cho hệ thống nhà thuốc PharmaGo.
 *
 * Mỗi đơn hàng gắn với ca làm việc (login session) của nhân viên.
 * Doanh thu tăng trưởng ấn tượng qua từng tháng.
 *
 * Chạy:  php artisan db:seed --class=DoanhThuSeeder
 */
class DoanhThuSeeder extends Seeder
{
    /* ── Cấu hình chung ─────────────────────────────────────── */

    /** Khoảng thời gian seed (3 tháng gần nhất tính từ hôm nay) */
    private Carbon $startDate;
    private Carbon $endDate;

    /** ID tự tăng cho các bảng */
    private int $hoaDonId;
    private int $chiTietId;
    private int $lichSuId;

    /** Bộ đệm insert hàng loạt */
    private array $bufHoaDon = [];
    private array $bufChiTiet = [];
    private array $bufThanhToan = [];
    private array $bufLichSu = [];
    private array $bufLoginLog = [];

    /* ── Ca làm việc ─────────────────────────────────────────── */

    private const SHIFTS = [
        'sang' => ['start' => '07:30', 'end' => '12:00'],
        'chieu' => ['start' => '13:00', 'end' => '17:30'],
        'toi' => ['start' => '18:00', 'end' => '21:30'],
    ];

    /** Số đơn tối thiểu / tối đa mỗi ca (sẽ nhân với hệ số tháng) */
    private const BASE_ORDERS_PER_SHIFT = [1, 2];

    /* ── Danh sách sản phẩm phổ biến (giá bán VNĐ) ──────── */
    private const PRODUCTS = [
        ['gia' => 2000, 'don_vi' => 'Gói', 'min_qty' => 10, 'max_qty' => 48],
        ['gia' => 93500, 'don_vi' => 'Hộp', 'min_qty' => 2, 'max_qty' => 6],
        ['gia' => 280000, 'don_vi' => 'chai', 'min_qty' => 2, 'max_qty' => 5],
        ['gia' => 13440, 'don_vi' => 'Vỉ', 'min_qty' => 5, 'max_qty' => 20],
        ['gia' => 59800, 'don_vi' => 'Hộp', 'min_qty' => 2, 'max_qty' => 6],
        ['gia' => 136000, 'don_vi' => 'hộp', 'min_qty' => 2, 'max_qty' => 8],
        ['gia' => 1599000, 'don_vi' => 'hộp', 'min_qty' => 1, 'max_qty' => 3],
        ['gia' => 590000, 'don_vi' => 'hộp', 'min_qty' => 1, 'max_qty' => 4],
        ['gia' => 210000, 'don_vi' => 'hộp', 'min_qty' => 2, 'max_qty' => 6],
        ['gia' => 140000, 'don_vi' => 'hộp', 'min_qty' => 2, 'max_qty' => 8],
        ['gia' => 128000, 'don_vi' => 'hộp', 'min_qty' => 2, 'max_qty' => 6],
        ['gia' => 70000, 'don_vi' => 'Chai', 'min_qty' => 2, 'max_qty' => 6],
        ['gia' => 45000, 'don_vi' => 'Hộp', 'min_qty' => 3, 'max_qty' => 10],
        ['gia' => 185000, 'don_vi' => 'Hộp', 'min_qty' => 2, 'max_qty' => 5],
        ['gia' => 320000, 'don_vi' => 'Hộp', 'min_qty' => 1, 'max_qty' => 4],
        ['gia' => 75000, 'don_vi' => 'Tuýp', 'min_qty' => 2, 'max_qty' => 8],
        ['gia' => 250000, 'don_vi' => 'Hộp', 'min_qty' => 2, 'max_qty' => 5],
        ['gia' => 35000, 'don_vi' => 'Vỉ', 'min_qty' => 5, 'max_qty' => 15],
        ['gia' => 420000, 'don_vi' => 'Hộp', 'min_qty' => 1, 'max_qty' => 4],
        ['gia' => 155000, 'don_vi' => 'Hộp', 'min_qty' => 2, 'max_qty' => 6],
    ];

    private const ADDRESSES = [
        '33 Phước Lý 8',
        '23 Mẹ Suốt',
        '15 Nguyễn Huệ, Q.1',
        '78 Lê Lợi, Q.3',
        '102 Hai Bà Trưng, Q.1',
        '45 Trần Hưng Đạo, Q.5',
        '200 Võ Văn Tần, Q.3',
        '56 Pasteur, Q.1',
        '88 Cách Mạng Tháng 8, Q.10',
        '12 Nguyễn Trãi, Q.5',
    ];

    private const PAYMENT_METHODS = ['tien_mat', 'tien_mat', 'tien_mat', 'chuyen_khoan', 'chuyen_khoan'];

    public function run(): void
    {
        $this->endDate = Carbon::today();
        $this->startDate = Carbon::today()->subMonths(3)->startOfMonth();

        // Lấy ID cao nhất hiện tại để tránh trùng
        $this->hoaDonId = (int) DB::table('hoa_dons')->max('id_hoa_don') + 1;
        $this->chiTietId = (int) DB::table('chi_tiet_hoa_don')->max('id') + 1;
        $this->lichSuId = (int) DB::table('lich_su_don_hangs')->max('id_lich_su') + 1;

        // Lấy danh sách nhân viên active + lô thuốc có hàng
        $staffIds = DB::table('nhan_viens')->where('trang_thai', 'active')->pluck('id_nhan_vien')->toArray();
        $customerIds = DB::table('khach_hangs')->pluck('id_khach_hang')->toArray();
        $loThuocs = DB::table('lo_thuocs')->where('so_luong_con', '>', 0)->get()->toArray();

        if (empty($staffIds) || empty($customerIds) || empty($loThuocs)) {
            $this->command->warn('Thiếu dữ liệu nhân viên / khách hàng / lô thuốc. Bỏ qua.');
            return;
        }

        $currentDate = $this->startDate->copy();
        $monthIndex = 0;
        $currentMonth = $currentDate->month;

        while ($currentDate->lte($this->endDate)) {
            if ($currentDate->month !== $currentMonth) {
                $currentMonth = $currentDate->month;
                $monthIndex++;
            }

            $this->generateDayOrders($currentDate, $monthIndex, $staffIds, $customerIds, $loThuocs);
            $currentDate->addDay();
        }

        // Flush tất cả buffer
        $this->flushAll();

        $totalRevenue = number_format(
            DB::table('hoa_dons')
                ->where('id_hoa_don', '>=', (int) DB::table('hoa_dons')->max('id_hoa_don') - count($this->bufHoaDon))
                ->sum('tien_thanh_toan')
        );

        $this->command->info("✅ Đã tạo doanh thu 3 tháng thành công!");
        $this->command->info("   📅 Từ {$this->startDate->format('d/m/Y')} → {$this->endDate->format('d/m/Y')}");
        $this->command->info("   🧾 Tổng đơn: " . ($this->hoaDonId - (int) DB::table('hoa_dons')->min('id_hoa_don')));
    }

    /* ================================================================
     *  Tạo đơn hàng cho 1 ngày
     * ================================================================ */

    private function generateDayOrders(Carbon $date, int $monthIndex, array $staffIds, array $customerIds, array $loThuocs): void
    {
        $isWeekend = $date->isWeekend();

        // Hệ số tăng trưởng theo tháng: 1.0 → 1.4 → 1.9 (wow!)
        $growthMultiplier = match ($monthIndex) {
            0 => 1.0,
            1 => 1.4,
            2 => 1.9,
            default => 2.2,
        };

        // Cuối tuần đông hơn 30%
        $weekendBoost = $isWeekend ? 1.3 : 1.0;

        // Ngẫu nhiên thêm "ngày vàng" (15% cơ hội) – doanh thu x1.5
        $goldenDay = mt_rand(1, 100) <= 15 ? 1.5 : 1.0;

        // Chọn 2 nhân viên làm ca hôm nay (giữ gọn cho SQLite)
        $numStaffToday = min(count($staffIds), 2);
        $todayStaff = (array) array_rand(array_flip($staffIds), $numStaffToday);

        $shiftKeys = array_keys(self::SHIFTS);

        foreach ($todayStaff as $staffId) {
            // Mỗi nhân viên làm 1 ca
            $assignedShifts = [(array) array_rand(array_flip($shiftKeys), 1)][0];
            $assignedShifts = (array) $assignedShifts;

            foreach ($assignedShifts as $shiftKey) {
                $shift = self::SHIFTS[$shiftKey];

                // Tạo login log cho ca này
                $loginTime = Carbon::parse($date->format('Y-m-d') . ' ' . $shift['start'])
                    ->addMinutes(mt_rand(0, 15));
                $logoutTime = Carbon::parse($date->format('Y-m-d') . ' ' . $shift['end'])
                    ->subMinutes(mt_rand(0, 10));

                $this->bufLoginLog[] = [
                    'id_nhan_vien' => $staffId,
                    'token_id' => null,
                    'kenh_dang_nhap' => 'tai_quay',
                    'thoi_gian_dang_nhap' => $loginTime->format('Y-m-d H:i:s'),
                    'thoi_gian_dang_xuat' => $logoutTime->format('Y-m-d H:i:s'),
                    'het_han_luc' => $logoutTime->copy()->addHours(8)->format('Y-m-d H:i:s'),
                    'thoi_luong_giay' => $loginTime->diffInSeconds($logoutTime),
                    'ly_do_dang_xuat' => 'het_ca',
                    'dang_hoat_dong' => null,
                    'dia_chi_ip' => '192.168.1.' . mt_rand(10, 254),
                    'user_agent' => 'PharmaGo-POS/1.0',
                    'created_at' => $loginTime->format('Y-m-d H:i:s'),
                    'updated_at' => $logoutTime->format('Y-m-d H:i:s'),
                ];

                // Số đơn trong ca này
                $baseMin = (int) round(self::BASE_ORDERS_PER_SHIFT[0] * $growthMultiplier * $weekendBoost * $goldenDay);
                $baseMax = (int) round(self::BASE_ORDERS_PER_SHIFT[1] * $growthMultiplier * $weekendBoost * $goldenDay);
                $numOrders = mt_rand($baseMin, $baseMax);

                // Phân bổ thời gian đặt đơn đều trong ca
                $shiftDurationMin = $loginTime->diffInMinutes($logoutTime);

                for ($i = 0; $i < $numOrders; $i++) {
                    $orderMinuteOffset = (int) round($shiftDurationMin * ($i + 1) / ($numOrders + 1)) + mt_rand(-5, 5);
                    $orderTime = $loginTime->copy()->addMinutes(max(0, $orderMinuteOffset));

                    if ($orderTime->gt($logoutTime)) {
                        $orderTime = $logoutTime->copy()->subMinutes(mt_rand(1, 5));
                    }

                    $this->createOrder($orderTime, $staffId, $customerIds, $loThuocs, $growthMultiplier);
                }
            }
        }

        // Flush mỗi 500 đơn để tránh tràn bộ nhớ
        if (count($this->bufHoaDon) >= 500) {
            $this->flushAll();
        }
    }

    /* ================================================================
     *  Tạo 1 đơn hàng cụ thể
     * ================================================================ */

    private function createOrder(Carbon $orderTime, int $staffId, array $customerIds, array $loThuocs, float $growth): void
    {
        $ts = $orderTime->format('Y-m-d H:i:s');
        $maHoaDon = 'HD' . $orderTime->format('YmdHis') . str_pad($this->hoaDonId, 4, '0', STR_PAD_LEFT);

        $customerId = $customerIds[array_rand($customerIds)];

        // Mỗi đơn có 1-4 sản phẩm (đơn lớn hơn khi growth cao)
        $numItems = mt_rand(2, min(4, (int) ceil($growth * 2.5)));

        $tongTien = 0;
        $chiTiets = [];

        $usedProducts = [];
        for ($j = 0; $j < $numItems; $j++) {
            // Chọn sản phẩm ngẫu nhiên (không trùng trong 1 đơn)
            do {
                $pIdx = mt_rand(0, count(self::PRODUCTS) - 1);
            } while (in_array($pIdx, $usedProducts) && count($usedProducts) < count(self::PRODUCTS));
            $usedProducts[] = $pIdx;

            $product = self::PRODUCTS[$pIdx];
            $qty = mt_rand($product['min_qty'], $product['max_qty']);
            $gia = $product['gia'];
            $thanhTien = $gia * $qty;

            // Lấy lô thuốc ngẫu nhiên
            $lo = $loThuocs[array_rand($loThuocs)];

            $chiTiets[] = [
                'id' => $this->chiTietId++,
                'id_hoa_don' => $this->hoaDonId,
                'id_lo' => $lo->id_lo,
                'don_vi_ban' => $product['don_vi'],
                'he_so_quy_doi_ban' => 1,
                'so_luong' => $qty,
                'gia_ban' => number_format($gia, 2, '.', ''),
                'thanh_tien' => number_format($thanhTien, 2, '.', ''),
                'ngay_tao' => $ts,
                'ngay_cap_nhat' => $ts,
            ];

            $tongTien += $thanhTien;
        }

        // Giảm giá ngẫu nhiên (20% cơ hội, giảm 5-15%)
        $giamGia = 0;
        if (mt_rand(1, 100) <= 20 && $tongTien > 100000) {
            $pct = mt_rand(5, 15);
            $giamGia = round($tongTien * $pct / 100);
        }

        $thueVat = round(($tongTien - $giamGia) * 0.1);
        $tienThanhToan = $tongTien - $giamGia + $thueVat;

        $this->bufHoaDon[] = [
            'id_hoa_don' => $this->hoaDonId,
            'ma_hoa_don' => $maHoaDon,
            'ngay_tao' => $ts,
            'id_khach_hang' => $customerId,
            'id_nhan_vien' => $staffId,
            'ma_giam_gia_id' => null,
            'kenh_ban' => 'he_thong',
            'trang_thai_xu_ly' => 'da_xac_nhan',
            'ly_do_tu_choi' => null,
            'tong_tien' => number_format($tongTien, 2, '.', ''),
            'giam_gia' => number_format($giamGia, 2, '.', ''),
            'giam_gia_ma' => '0.00',
            'giam_gia_diem' => '0.00',
            'thue_vat' => number_format($thueVat, 2, '.', ''),
            'tien_thanh_toan' => number_format($tienThanhToan, 2, '.', ''),
            'diem_da_su_dung' => 0,
            'diem_da_cong' => 0,
            'diem_thuong_da_xu_ly' => true,
            'ngay_ban' => $ts,
            'ngay_cap_nhat' => $ts,
        ];

        foreach ($chiTiets as $ct) {
            $this->bufChiTiet[] = $ct;
        }

        // Thanh toán
        $phuongThuc = self::PAYMENT_METHODS[array_rand(self::PAYMENT_METHODS)];
        $this->bufThanhToan[] = [
            'id_hoa_don' => $this->hoaDonId,
            'phuong_thuc' => $phuongThuc,
            'so_tien' => number_format($tienThanhToan, 2, '.', ''),
            'thoi_gian' => $ts,
            'trang_thai' => 'paid',
            'ma_giao_dich' => $phuongThuc === 'chuyen_khoan'
                ? 'TXN' . $orderTime->format('YmdHis') . mt_rand(1000, 9999)
                : null,
            'ngay_tao' => $ts,
            'ngay_cap_nhat' => $ts,
        ];

        // Lịch sử đơn hàng
        $address = self::ADDRESSES[array_rand(self::ADDRESSES)];
        $this->bufLichSu[] = [
            'id_lich_su' => $this->lichSuId++,
            'id_hoa_don' => $this->hoaDonId,
            'trang_thai' => 'Thanh cong',
            'ghi_chu' => "Dia chi giao hang: {$address}",
            'thoi_gian' => $ts,
            'id_nhan_vien' => $staffId,
            'ngay_tao' => $ts,
            'ngay_cap_nhat' => $ts,
        ];

        $this->hoaDonId++;
    }

    /* ================================================================
     *  Flush buffer → DB
     * ================================================================ */

    private function flushAll(): void
    {
        foreach (array_chunk($this->bufHoaDon, 200) as $chunk) {
            DB::table('hoa_dons')->insert($chunk);
        }

        foreach (array_chunk($this->bufChiTiet, 200) as $chunk) {
            DB::table('chi_tiet_hoa_don')->insert($chunk);
        }

        foreach (array_chunk($this->bufThanhToan, 200) as $chunk) {
            DB::table('thanh_toan')->insert($chunk);
        }

        foreach (array_chunk($this->bufLichSu, 200) as $chunk) {
            DB::table('lich_su_don_hangs')->insert($chunk);
        }

        foreach (array_chunk($this->bufLoginLog, 200) as $chunk) {
            DB::table('nhan_vien_dang_nhap_logs')->insert($chunk);
        }

        $this->bufHoaDon = [];
        $this->bufChiTiet = [];
        $this->bufThanhToan = [];
        $this->bufLichSu = [];
        $this->bufLoginLog = [];
    }
}
