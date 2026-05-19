<?php

namespace App\Http\Controllers;

use App\Models\HoaDon;
use App\Models\NhanVien;
use App\Models\NhanVienDangNhapLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardAnalyticsController extends Controller
{
    public function staffPerformance(Request $request): JsonResponse
    {
        $selectedDate = $this->resolveDate($request->query('date'));
        $monthStart = $this->resolveMonth($request->query('month'), $selectedDate);
        $monthEnd = $monthStart->copy()->endOfMonth();

        $dailyLogins = $this->loginSummaryByEmployee($selectedDate->copy()->startOfDay(), $selectedDate->copy()->endOfDay());
        $dailySales = $this->salesByEmployee(
            $selectedDate->copy()->startOfDay(),
            $selectedDate->copy()->endOfDay(),
            $dailyLogins->keys()
        );
        $dailyRows = $this->mergeEmployeeRows($dailySales, $dailyLogins, includeLoginDays: false, onlyLoggedInEmployees: true);
        $weeklyRevenue = $this->weeklyRevenue($selectedDate);

        $monthlyLogins = $this->loginSummaryByEmployee($monthStart, $monthEnd, includeLoginDays: true);
        $monthlySales = $this->salesByEmployee($monthStart, $monthEnd, $monthlyLogins->keys());
        $monthlyRows = $this->mergeEmployeeRows($monthlySales, $monthlyLogins, includeLoginDays: true, onlyLoggedInEmployees: true);
        $monthlyLeader = $monthlyRows->first(fn (array $item): bool => (float) $item['doanh_thu'] > 0);

        return response()->json([
            'message' => 'Thống kê hiệu suất nhân viên thành công.',
            'data' => [
                'bo_loc' => [
                    'ngay' => $selectedDate->toDateString(),
                    'thang' => $monthStart->format('Y-m'),
                ],
                'hom_nay' => [
                    'tong_doanh_thu' => round((float) $dailyRows->sum('doanh_thu'), 2),
                    'tong_hoa_don' => (int) $dailyRows->sum('so_hoa_don'),
                    'so_nhan_vien_dang_nhap' => (int) $dailyRows
                        ->filter(fn (array $item): bool => (int) $item['so_lan_dang_nhap'] > 0)
                        ->count(),
                    'nhan_viens' => $dailyRows->values(),
                ],
                'tuan_nay' => $weeklyRevenue,
                'thang_nay' => [
                    'tong_doanh_thu' => round((float) $monthlyRows->sum('doanh_thu'), 2),
                    'tong_hoa_don' => (int) $monthlyRows->sum('so_hoa_don'),
                    'nhan_vien_dan_dau' => $monthlyLeader,
                    'nhan_viens' => $monthlyRows->values(),
                ],
            ],
        ]);
    }

    private function resolveDate(mixed $date): Carbon
    {
        try {
            return $date ? Carbon::parse((string) $date)->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    private function resolveMonth(mixed $month, Carbon $fallbackDate): Carbon
    {
        try {
            return $month
                ? Carbon::createFromFormat('Y-m', (string) $month)->startOfMonth()
                : $fallbackDate->copy()->startOfMonth();
        } catch (\Throwable) {
            return $fallbackDate->copy()->startOfMonth();
        }
    }

    private function salesByEmployee(Carbon $from, Carbon $to, $employeeIds = null)
    {
        return HoaDon::query()
            ->whereHas('chiTiets')
            ->whereHas('thanhToan', fn ($query) => $query->where('trang_thai', 'paid'))
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [$from, $to])
            ->when(
                $employeeIds !== null,
                fn ($query) => $query->whereIn('id_nhan_vien', collect($employeeIds)->filter()->values())
            )
            ->selectRaw('id_nhan_vien, count(*) as so_hoa_don, coalesce(sum(tien_thanh_toan), 0) as doanh_thu')
            ->groupBy('id_nhan_vien')
            ->get()
            ->keyBy('id_nhan_vien');
    }

    private function weeklyRevenue(Carbon $selectedDate): array
    {
        $weekStart = $selectedDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $selectedDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $loggedInEmployeeIds = $this->loginSummaryByEmployee($weekStart, $weekEnd)->keys();

        $rows = HoaDon::query()
            ->whereHas('chiTiets')
            ->whereHas('thanhToan', fn ($query) => $query->where('trang_thai', 'paid'))
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [$weekStart, $weekEnd])
            ->whereIn('id_nhan_vien', $loggedInEmployeeIds)
            ->selectRaw('date(ngay_ban) as ngay, count(*) as so_hoa_don, coalesce(sum(tien_thanh_toan), 0) as doanh_thu')
            ->groupByRaw('date(ngay_ban)')
            ->get()
            ->keyBy('ngay');

        $days = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $rows): array {
            $day = $weekStart->copy()->addDays($offset);
            $key = $day->toDateString();
            $row = $rows->get($key);

            return [
                'ngay' => $key,
                'thu' => $this->weekdayLabel($day),
                'so_hoa_don' => (int) ($row->so_hoa_don ?? 0),
                'doanh_thu' => round((float) ($row->doanh_thu ?? 0), 2),
            ];
        });

        return [
            'tu_ngay' => $weekStart->toDateString(),
            'den_ngay' => $weekEnd->toDateString(),
            'tong_doanh_thu' => round((float) $days->sum('doanh_thu'), 2),
            'tong_hoa_don' => (int) $days->sum('so_hoa_don'),
            'doanh_thu_theo_ngay' => $days->values(),
        ];
    }

    private function weekdayLabel(Carbon $day): string
    {
        return match ($day->dayOfWeek) {
            Carbon::MONDAY => 'Thứ 2',
            Carbon::TUESDAY => 'Thứ 3',
            Carbon::WEDNESDAY => 'Thứ 4',
            Carbon::THURSDAY => 'Thứ 5',
            Carbon::FRIDAY => 'Thứ 6',
            Carbon::SATURDAY => 'Thứ 7',
            default => 'Chủ nhật',
        };
    }

    private function loginSummaryByEmployee(Carbon $from, Carbon $to, bool $includeLoginDays = false)
    {
        $select = implode(', ', [
            'id_nhan_vien',
            'count(*) as so_lan_dang_nhap',
            'max(thoi_gian_dang_nhap) as lan_dang_nhap_cuoi',
            "max(case when kenh_dang_nhap = 'he_thong' then thoi_gian_dang_nhap end) as dang_nhap_he_thong",
            "max(case when kenh_dang_nhap = 'tai_quay' then thoi_gian_dang_nhap end) as dang_nhap_tai_quay",
            "max(case when kenh_dang_nhap = 'he_thong' then thoi_gian_dang_xuat end) as dang_xuat_he_thong",
            "max(case when kenh_dang_nhap = 'tai_quay' then thoi_gian_dang_xuat end) as dang_xuat_tai_quay",
        ]);

        if ($includeLoginDays) {
            $select .= ', count(distinct date(thoi_gian_dang_nhap)) as so_ngay_dang_nhap';
        }

        return NhanVienDangNhapLog::query()
            ->whereBetween('thoi_gian_dang_nhap', [$from, $to])
            ->selectRaw($select)
            ->groupBy('id_nhan_vien')
            ->get()
            ->keyBy('id_nhan_vien');
    }

    private function mergeEmployeeRows($sales, $logins, bool $includeLoginDays, bool $onlyLoggedInEmployees = false)
    {
        $employeeIds = $onlyLoggedInEmployees
            ? $logins->keys()->unique()->values()
            : $sales->keys()
                ->merge($logins->keys())
                ->unique()
                ->values();

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        $employees = NhanVien::query()
            ->with('vaiTro:id_vai_tro,ten_vai_tro')
            ->whereIn('id_nhan_vien', $employeeIds)
            ->get()
            ->keyBy('id_nhan_vien');

        return $employeeIds
            ->map(function ($employeeId) use ($employees, $sales, $logins, $includeLoginDays): ?array {
                $employee = $employees->get($employeeId);

                if (! $employee) {
                    return null;
                }

                $sale = $sales->get($employeeId);
                $login = $logins->get($employeeId);

                $row = [
                    'id_nhan_vien' => $employee->id_nhan_vien,
                    'ho_ten' => $employee->ho_ten,
                    'ten_dang_nhap' => $employee->ten_dang_nhap,
                    'vai_tro' => $employee->vaiTro?->ten_vai_tro,
                    'so_lan_dang_nhap' => (int) ($login->so_lan_dang_nhap ?? 0),
                    'lan_dang_nhap_cuoi' => $login?->lan_dang_nhap_cuoi,
                    'dang_nhap_he_thong' => $login?->dang_nhap_he_thong,
                    'dang_nhap_tai_quay' => $login?->dang_nhap_tai_quay,
                    'dang_xuat_he_thong' => $login?->dang_xuat_he_thong,
                    'dang_xuat_tai_quay' => $login?->dang_xuat_tai_quay,
                    'so_hoa_don' => (int) ($sale->so_hoa_don ?? 0),
                    'doanh_thu' => round((float) ($sale->doanh_thu ?? 0), 2),
                ];

                if ($includeLoginDays) {
                    $row['so_ngay_dang_nhap'] = (int) ($login->so_ngay_dang_nhap ?? 0);
                }

                return $row;
            })
            ->filter()
            ->sortByDesc('doanh_thu')
            ->values();
    }

    /* ================================================================
     *  Tổng quan doanh thu – cung cấp dữ liệu cho nhiều biểu đồ
     * ================================================================ */

    public function revenueOverview(Request $request): JsonResponse
    {
        $months = min((int) ($request->query('months') ?: 3), 12);
        $from   = now()->subMonths($months)->startOfMonth();
        $to     = now()->endOfDay();

        // 1. Doanh thu theo tháng (line/bar chart)
        $monthlyRevenue = \DB::table('hoa_dons')
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [$from, $to])
            ->selectRaw("DATE_FORMAT(ngay_ban, '%Y-%m') as thang, COUNT(*) as so_don, COALESCE(SUM(tien_thanh_toan),0) as doanh_thu, COALESCE(SUM(giam_gia),0) as tong_giam_gia")
            ->groupByRaw("DATE_FORMAT(ngay_ban, '%Y-%m')")
            ->orderBy('thang')
            ->get();

        // 2. Doanh thu theo ngày trong tháng hiện tại (area chart)
        $currentMonthStart = now()->startOfMonth();
        $dailyRevenue = \DB::table('hoa_dons')
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [$currentMonthStart, $to])
            ->selectRaw("DATE_FORMAT(ngay_ban, '%Y-%m-%d') as ngay, COUNT(*) as so_don, COALESCE(SUM(tien_thanh_toan),0) as doanh_thu")
            ->groupByRaw("DATE_FORMAT(ngay_ban, '%Y-%m-%d')")
            ->orderBy('ngay')
            ->get();

        // 3. Phân bổ phương thức thanh toán (pie/donut chart)
        $paymentMethods = \DB::table('thanh_toan')
            ->join('hoa_dons', 'thanh_toan.id_hoa_don', '=', 'hoa_dons.id_hoa_don')
            ->whereIn('hoa_dons.trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->where('thanh_toan.trang_thai', 'paid')
            ->whereBetween('hoa_dons.ngay_ban', [$from, $to])
            ->selectRaw("thanh_toan.phuong_thuc, COUNT(*) as so_giao_dich, COALESCE(SUM(thanh_toan.so_tien),0) as tong_tien")
            ->groupBy('thanh_toan.phuong_thuc')
            ->get()
            ->map(fn ($r) => [
                'phuong_thuc' => $r->phuong_thuc === 'tien_mat' ? 'Tiền mặt' : ($r->phuong_thuc === 'chuyen_khoan' ? 'Chuyển khoản' : $r->phuong_thuc),
                'so_giao_dich' => (int) $r->so_giao_dich,
                'tong_tien' => round((float) $r->tong_tien, 2),
            ]);

        // 4. Doanh thu theo kênh bán (pie chart)
        $salesChannels = \DB::table('hoa_dons')
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [$from, $to])
            ->selectRaw("kenh_ban, COUNT(*) as so_don, COALESCE(SUM(tien_thanh_toan),0) as doanh_thu")
            ->groupBy('kenh_ban')
            ->get()
            ->map(fn ($r) => [
                'kenh_ban' => $r->kenh_ban === 'he_thong' ? 'Hệ thống' : ($r->kenh_ban === 'tai_quay' ? 'Tại quầy' : ($r->kenh_ban === 'online' ? 'Online' : $r->kenh_ban)),
                'so_don' => (int) $r->so_don,
                'doanh_thu' => round((float) $r->doanh_thu, 2),
            ]);

        // 5. Top sản phẩm bán chạy (horizontal bar chart)
        $topProducts = \DB::table('chi_tiet_hoa_don')
            ->join('hoa_dons', 'chi_tiet_hoa_don.id_hoa_don', '=', 'hoa_dons.id_hoa_don')
            ->join('lo_thuocs', 'chi_tiet_hoa_don.id_lo', '=', 'lo_thuocs.id_lo')
            ->join('thuocs', 'lo_thuocs.id_thuoc', '=', 'thuocs.ma_thuoc')
            ->whereIn('hoa_dons.trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('hoa_dons.ngay_ban', [$from, $to])
            ->selectRaw("thuocs.ten_thuoc, SUM(chi_tiet_hoa_don.so_luong) as tong_so_luong, COALESCE(SUM(chi_tiet_hoa_don.thanh_tien),0) as tong_doanh_thu")
            ->groupBy('thuocs.ten_thuoc')
            ->orderByDesc('tong_doanh_thu')
            ->limit(8)
            ->get();

        // 6. Tổng quan chung
        $totalRevenue = (float) \DB::table('hoa_dons')
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [$from, $to])
            ->sum('tien_thanh_toan');

        $totalOrders = (int) \DB::table('hoa_dons')
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [$from, $to])
            ->count();

        $todayRevenue = (float) \DB::table('hoa_dons')
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [now()->startOfDay(), now()->endOfDay()])
            ->sum('tien_thanh_toan');

        $todayOrders = (int) \DB::table('hoa_dons')
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [now()->startOfDay(), now()->endOfDay()])
            ->count();

        // Tháng trước để tính tăng trưởng
        $lastMonthRevenue = (float) \DB::table('hoa_dons')
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('tien_thanh_toan');

        $thisMonthRevenue = (float) \DB::table('hoa_dons')
            ->whereIn('trang_thai_xu_ly', ['da_xac_nhan', 'hoan_thanh'])
            ->whereBetween('ngay_ban', [now()->startOfMonth(), now()->endOfDay()])
            ->sum('tien_thanh_toan');

        $growthPercent = $lastMonthRevenue > 0
            ? round(($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue * 100, 1)
            : 0;

        return response()->json([
            'message' => 'Tổng quan doanh thu thành công.',
            'data' => [
                'tong_quan' => [
                    'tong_doanh_thu' => round($totalRevenue, 2),
                    'tong_don_hang' => $totalOrders,
                    'doanh_thu_hom_nay' => round($todayRevenue, 2),
                    'don_hang_hom_nay' => $todayOrders,
                    'doanh_thu_thang_nay' => round($thisMonthRevenue, 2),
                    'tang_truong_phan_tram' => $growthPercent,
                ],
                'doanh_thu_theo_thang' => $monthlyRevenue,
                'doanh_thu_theo_ngay' => $dailyRevenue,
                'phuong_thuc_thanh_toan' => $paymentMethods,
                'kenh_ban' => $salesChannels,
                'san_pham_ban_chay' => $topProducts,
            ],
        ]);
    }
}
