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
        $select = 'id_nhan_vien, count(*) as so_lan_dang_nhap, max(thoi_gian_dang_nhap) as lan_dang_nhap_cuoi';

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
}
