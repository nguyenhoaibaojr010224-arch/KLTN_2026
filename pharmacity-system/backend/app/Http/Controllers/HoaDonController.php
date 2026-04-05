<?php

namespace App\Http\Controllers;

use App\Http\Requests\HoaDonStatisticsRequest;
use App\Http\Requests\SearchHoaDonRequest;
use App\Http\Requests\StoreHoaDonRequest;
use App\Models\HoaDon;
use App\Models\LichSuDonHang;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HoaDonController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lay danh sach hoa don thanh cong.',
            'data' => $this->baseQuery()->get(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $hoaDon = $this->baseQuery()->find($id);

        if (! $hoaDon) {
            return response()->json([
                'message' => 'Khong tim thay hoa don.',
            ], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet hoa don thanh cong.',
            'data' => $hoaDon,
        ]);
    }

    public function store(StoreHoaDonRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $tongTien = (float) $validated['tong_tien'];
        $giamGia = (float) ($validated['giam_gia'] ?? 0);

        $hoaDon = HoaDon::create([
            'ma_hoa_don' => $validated['ma_hoa_don'] ?? $this->generateInvoiceCode(),
            'id_khach_hang' => $validated['id_khach_hang'],
            'id_nhan_vien' => $request->user()->id_nhan_vien,
            'tong_tien' => $tongTien,
            'giam_gia' => $giamGia,
            'tien_thanh_toan' => $tongTien - $giamGia,
            'ngay_ban' => $validated['ngay_ban'] ?? now(),
        ]);

        $hoaDon->load(['khachHang', 'nhanVien.vaiTro']);

        return response()->json([
            'message' => 'Tao hoa don thanh cong.',
            'data' => $hoaDon,
        ], 201);
    }

    public function search(SearchHoaDonRequest $request): JsonResponse
    {
        $keyword = $request->validated()['q'];

        $hoaDons = $this->baseQuery()
            ->where(function (Builder $query) use ($keyword): void {
                $query->where('ma_hoa_don', 'like', '%' . $keyword . '%')
                    ->orWhereHas('khachHang', function (Builder $khachHangQuery) use ($keyword): void {
                        $khachHangQuery->where('ten_khach_hang', 'like', '%' . $keyword . '%')
                            ->orWhere('so_dien_thoai', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('nhanVien', function (Builder $nhanVienQuery) use ($keyword): void {
                        $nhanVienQuery->where('ho_ten', 'like', '%' . $keyword . '%')
                            ->orWhere('ten_dang_nhap', 'like', '%' . $keyword . '%');
                    });
            })
            ->get();

        return response()->json([
            'message' => 'Tim kiem hoa don thanh cong.',
            'data' => $hoaDons,
        ]);
    }

    public function pendingNotifications(): JsonResponse
    {
        $pendingQuery = $this->baseQuery()
            ->whereHas('latestLichSuDonHang', function (Builder $query): void {
                $query->where('trang_thai', 'Chờ xác nhận');
            });

        $tongThongBao = (clone $pendingQuery)->count();
        $hoaDons = (clone $pendingQuery)
            ->limit(8)
            ->get()
            ->map(function (HoaDon $hoaDon): array {
                return [
                    'id_hoa_don' => $hoaDon->id_hoa_don,
                    'ma_hoa_don' => $hoaDon->ma_hoa_don,
                    'ngay_ban' => optional($hoaDon->ngay_ban)?->toIso8601String(),
                    'tong_tien' => (float) $hoaDon->tien_thanh_toan,
                    'khach_hang' => [
                        'ten_khach_hang' => $hoaDon->khachHang?->ten_khach_hang,
                        'so_dien_thoai' => $hoaDon->khachHang?->so_dien_thoai,
                    ],
                    'trang_thai' => $hoaDon->latestLichSuDonHang?->trang_thai ?: 'Chờ xác nhận',
                    'ghi_chu' => $hoaDon->latestLichSuDonHang?->ghi_chu,
                    'thoi_gian_cap_nhat' => optional($hoaDon->latestLichSuDonHang?->thoi_gian)?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Lay thong bao hoa don cho xac nhan thanh cong.',
            'data' => $hoaDons,
            'tong_thong_bao' => $tongThongBao,
        ]);
    }

    public function statistics(HoaDonStatisticsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = HoaDon::query()->whereHas('chiTiets');

        if (! empty($filters['from'])) {
            $query->whereDate('ngay_ban', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('ngay_ban', '<=', $filters['to']);
        }

        $tongHoaDon = (clone $query)->count();
        $tongTien = (float) (clone $query)->sum('tong_tien');
        $tongGiamGia = (float) (clone $query)->sum('giam_gia');
        $tongThanhToan = (float) (clone $query)->sum('tien_thanh_toan');

        $doanhThuTheoNgay = (clone $query)
            ->selectRaw('date(ngay_ban) as ngay, count(*) as so_hoa_don, sum(tien_thanh_toan) as doanh_thu')
            ->groupByRaw('date(ngay_ban)')
            ->orderBy('ngay')
            ->get();

        $doanhThuTheoNhanVien = (clone $query)
            ->join('nhan_viens', 'nhan_viens.id_nhan_vien', '=', 'hoa_dons.id_nhan_vien')
            ->selectRaw('hoa_dons.id_nhan_vien, nhan_viens.ho_ten, count(*) as so_hoa_don, sum(hoa_dons.tien_thanh_toan) as doanh_thu')
            ->groupBy('hoa_dons.id_nhan_vien', 'nhan_viens.ho_ten')
            ->orderByDesc('doanh_thu')
            ->get();

        return response()->json([
            'message' => 'Thong ke doanh thu thanh cong.',
            'data' => [
                'bo_loc' => [
                    'from' => $filters['from'] ?? null,
                    'to' => $filters['to'] ?? null,
                ],
                'tong_quan' => [
                    'tong_so_hoa_don' => $tongHoaDon,
                    'tong_tien' => round($tongTien, 2),
                    'tong_giam_gia' => round($tongGiamGia, 2),
                    'tong_tien_thanh_toan' => round($tongThanhToan, 2),
                    'gia_tri_trung_binh' => $tongHoaDon > 0 ? round($tongThanhToan / $tongHoaDon, 2) : 0,
                ],
                'doanh_thu_theo_ngay' => $doanhThuTheoNgay,
                'doanh_thu_theo_nhan_vien' => $doanhThuTheoNhanVien,
            ],
        ]);
    }

    public function confirm(Request $request, int $id): JsonResponse
    {
        $hoaDon = $this->baseQuery()->find($id);

        if (! $hoaDon) {
            return response()->json([
                'message' => 'Khong tim thay hoa don.',
            ], 404);
        }

        $trangThaiHienTai = trim((string) $hoaDon->latestLichSuDonHang?->trang_thai);

        if ($trangThaiHienTai === 'Thành công') {
            return response()->json([
                'message' => 'Hoa don nay da duoc xac nhan truoc do.',
            ], 422);
        }

        DB::transaction(function () use ($request, $hoaDon): void {
            LichSuDonHang::create([
                'id_hoa_don' => $hoaDon->id_hoa_don,
                'trang_thai' => 'Thành công',
                'ghi_chu' => 'Đơn hàng đã được nhân viên xác nhận.',
                'thoi_gian' => now(),
                'id_nhan_vien' => $request->user()->id_nhan_vien,
            ]);
        });

        $hoaDon = $this->baseQuery()->find($id);

        return response()->json([
            'message' => 'Xac nhan hoa don thanh cong.',
            'data' => $hoaDon,
        ]);
    }

    private function baseQuery(): Builder
    {
        return HoaDon::query()
            ->whereHas('chiTiets')
            ->with([
                'khachHang:id_khach_hang,ten_khach_hang,so_dien_thoai,email',
                'nhanVien:id_nhan_vien,ten_dang_nhap,ho_ten,id_vai_tro',
                'nhanVien.vaiTro:id_vai_tro,ten_vai_tro',
                'latestLichSuDonHang',
            ])
            ->orderByDesc('ngay_ban')
            ->orderByDesc('id_hoa_don');
    }

    private function generateInvoiceCode(): string
    {
        do {
            $code = 'HD' . now()->format('YmdHis') . random_int(10, 99);
        } while (HoaDon::query()->where('ma_hoa_don', $code)->exists());

        return $code;
    }
}
