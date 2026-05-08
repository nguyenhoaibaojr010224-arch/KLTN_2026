<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchKeywordRequest;
use App\Http\Requests\StoreMaGiamGiaRequest;
use App\Http\Requests\UpdateMaGiamGiaRequest;
use App\Models\HoaDon;
use App\Models\KhachHang;
use App\Models\MaGiamGia;
use App\Models\MaGiamGiaLuotDung;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MaGiamGiaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        MaGiamGia::deactivateExpired();

        $keyword = trim((string) $request->string('q'));

        $items = MaGiamGia::query()
            ->with('nhanVien')
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($innerQuery) use ($keyword): void {
                    $innerQuery
                        ->where('ma_giam_gia', 'like', '%' . $keyword . '%')
                        ->orWhere('ten_ma', 'like', '%' . $keyword . '%')
                        ->orWhere('mo_ta', 'like', '%' . $keyword . '%');
                });
            })
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Lay danh sach ma giam gia thanh cong.',
            'data' => $items->map(fn (MaGiamGia $item) => $this->transform($item)),
        ]);
    }

    public function store(StoreMaGiamGiaRequest $request): JsonResponse
    {
        MaGiamGia::deactivateExpired();

        $payload = $this->normalizeDatePayload($request->validated());

        $item = MaGiamGia::create([
            ...$payload,
            'ma_giam_gia' => $this->normalizeCode($payload['ma_giam_gia']),
            'gia_tri_don_toi_thieu' => (int) ($payload['gia_tri_don_toi_thieu'] ?? 0),
            'id_nhan_vien' => $request->user()?->id_nhan_vien,
        ]);

        MaGiamGia::deactivateExpired();
        $item->refresh();
        $item->load('nhanVien');

        return response()->json([
            'message' => 'Tao ma giam gia thanh cong.',
            'data' => $this->transform($item),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        MaGiamGia::deactivateExpired();

        $item = MaGiamGia::with('nhanVien')->find($id);

        if (! $item) {
            return response()->json(['message' => 'Khong tim thay ma giam gia.'], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet ma giam gia thanh cong.',
            'data' => $this->transform($item),
        ]);
    }

    public function update(UpdateMaGiamGiaRequest $request, int $id): JsonResponse
    {
        MaGiamGia::deactivateExpired();

        $item = MaGiamGia::find($id);

        if (! $item) {
            return response()->json(['message' => 'Khong tim thay ma giam gia.'], 404);
        }

        $payload = $this->normalizeDatePayload($request->validated());
        if (array_key_exists('ma_giam_gia', $payload)) {
            $payload['ma_giam_gia'] = $this->normalizeCode($payload['ma_giam_gia']);
        }
        if (array_key_exists('gia_tri_don_toi_thieu', $payload)) {
            $payload['gia_tri_don_toi_thieu'] = (int) ($payload['gia_tri_don_toi_thieu'] ?? 0);
        }
        $payload['id_nhan_vien'] = $request->user()?->id_nhan_vien;

        $item->update($payload);
        MaGiamGia::deactivateExpired();
        $item->refresh();
        $item->load('nhanVien');

        return response()->json([
            'message' => 'Cap nhat ma giam gia thanh cong.',
            'data' => $this->transform($item),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = MaGiamGia::find($id);

        if (! $item) {
            return response()->json(['message' => 'Khong tim thay ma giam gia.'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Xoa ma giam gia thanh cong.']);
    }

    public function search(SearchKeywordRequest $request): JsonResponse
    {
        return $this->index($request);
    }

    public function customerList(Request $request): JsonResponse
    {
        MaGiamGia::deactivateExpired();

        $keyword = trim((string) $request->string('q'));
        $khachHang = $request->user();

        $items = MaGiamGia::query()
            ->with('nhanVien')
            ->dangHoatDong()
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $innerQuery) use ($keyword): void {
                    $innerQuery
                        ->where('ma_giam_gia', 'like', '%' . $keyword . '%')
                        ->orWhere('ten_ma', 'like', '%' . $keyword . '%')
                        ->orWhere('mo_ta', 'like', '%' . $keyword . '%');
                });
            })
            ->latest()
            ->get()
            ->filter(fn (MaGiamGia $item): bool => $this->isCouponVisibleToCustomer($item, $khachHang))
            ->filter(fn (MaGiamGia $item): bool => ! $this->isCouponAlreadyUnavailable($item, $khachHang))
            ->map(function (MaGiamGia $item) use ($khachHang): array {
                $usageCount = $khachHang instanceof KhachHang
                    ? $this->usageCount($item->id, $khachHang->id_khach_hang)
                    : 0;
                $reason = $this->customerCouponBlockReason($item, 0, $khachHang, false);

                return [
                    ...$this->transform($item),
                    'so_lan_da_dung' => $usageCount,
                    'so_lan_con_lai' => $item->gioi_han_moi_khach !== null
                        ? max((int) $item->gioi_han_moi_khach - $usageCount, 0)
                        : null,
                    'co_the_su_dung' => $reason === null,
                    'ly_do_khong_su_dung' => $reason,
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Lấy danh sách mã giảm giá cho khách hàng thành công.',
            'data' => $items,
        ]);
    }

    public function customerAvailable(Request $request): JsonResponse
    {
        MaGiamGia::deactivateExpired();

        $validated = $request->validate([
            'tong_tam_tinh' => ['nullable', 'numeric', 'min:0'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $subtotal = max((int) round((float) ($validated['tong_tam_tinh'] ?? 0)), 0);
        $keyword = trim((string) ($validated['q'] ?? ''));
        $khachHang = $request->user();

        $items = MaGiamGia::query()
            ->with('nhanVien')
            ->dangHoatDong()
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $innerQuery) use ($keyword): void {
                    $innerQuery
                        ->where('ma_giam_gia', 'like', '%' . $keyword . '%')
                        ->orWhere('ten_ma', 'like', '%' . $keyword . '%')
                        ->orWhere('mo_ta', 'like', '%' . $keyword . '%');
                });
            })
            ->latest()
            ->get()
            ->filter(fn (MaGiamGia $item): bool => $this->isCouponVisibleToCustomer($item, $khachHang))
            ->filter(fn (MaGiamGia $item): bool => ! $this->isCouponAlreadyUnavailable($item, $khachHang))
            ->map(function (MaGiamGia $item) use ($khachHang, $subtotal): array {
                $usageCount = $khachHang instanceof KhachHang
                    ? $this->usageCount($item->id, $khachHang->id_khach_hang)
                    : 0;

                $reason = $this->customerCouponBlockReason($item, $subtotal, $khachHang);
                if ($subtotal < (int) $item->gia_tri_don_toi_thieu) {
                    $reason = 'Chưa đủ điều kiện để sử dụng mã này.';
                }

                $discount = $reason === null ? $item->tinhTienGiam($subtotal) : 0;

                return [
                    ...$this->transform($item),
                    'tong_tam_tinh' => $subtotal,
                    'co_the_ap_dung' => $reason === null,
                    'ly_do_khong_ap_dung' => $reason,
                    'giam_gia_don_hang' => $discount,
                    'tong_sau_giam' => max($subtotal - $discount, 0),
                    'so_lan_da_dung' => $usageCount,
                    'so_lan_con_lai' => $item->gioi_han_moi_khach !== null
                        ? max((int) $item->gioi_han_moi_khach - $usageCount, 0)
                        : null,
                ];
            })
            ->sortByDesc(fn (array $item) => ((int) $item['co_the_ap_dung'] * 10)
                + (int) ($item['tu_dong_ap_dung'] && $item['co_the_ap_dung']))
            ->values();

        return response()->json([
            'message' => 'Lay danh sach ma giam gia cho khach hang thanh cong.',
            'data' => $items,
        ]);
    }

    public function available(Request $request): JsonResponse
    {
        MaGiamGia::deactivateExpired();

        $validated = $request->validate([
            'tong_tam_tinh' => ['required', 'numeric', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
        ], [
            'tong_tam_tinh.required' => 'Thiếu tổng tiền để kiểm tra mã giảm giá.',
        ]);

        $subtotal = (int) round((float) $validated['tong_tam_tinh']);
        $keyword = trim((string) ($validated['q'] ?? ''));
        $khachHang = $request->user();

        $items = MaGiamGia::query()
            ->with('nhanVien')
            ->dangHoatDong()
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $innerQuery) use ($keyword): void {
                    $innerQuery
                        ->where('ma_giam_gia', 'like', '%' . $keyword . '%')
                        ->orWhere('ten_ma', 'like', '%' . $keyword . '%')
                        ->orWhere('mo_ta', 'like', '%' . $keyword . '%');
                });
            })
            ->latest()
            ->get()
            ->filter(fn (MaGiamGia $item): bool => $this->isCouponVisibleToCustomer($item, $khachHang))
            ->filter(fn (MaGiamGia $item): bool => ! $this->isCouponAlreadyUnavailable($item, $khachHang))
            ->map(function (MaGiamGia $item) use ($khachHang, $subtotal) {
                $usageCount = $khachHang instanceof KhachHang
                    ? $this->usageCount($item->id, $khachHang->id_khach_hang)
                    : 0;

                $reason = $this->customerCouponBlockReason($item, $subtotal, $khachHang);
                if ($subtotal < (int) $item->gia_tri_don_toi_thieu) {
                    $reason = 'Bạn không đủ điều kiện sử dụng mã này.';
                } elseif (
                    $khachHang instanceof KhachHang
                    && $item->gioi_han_moi_khach !== null
                    && $usageCount >= (int) $item->gioi_han_moi_khach
                ) {
                    $reason = 'Bạn đã dùng hết số lượt của mã này.';
                }

                $discount = $reason === null ? $item->tinhTienGiam($subtotal) : 0;

                return [
                    ...$this->transform($item),
                    'tong_tam_tinh' => $subtotal,
                    'co_the_ap_dung' => $reason === null,
                    'ly_do_khong_ap_dung' => $reason,
                    'giam_gia_don_hang' => $discount,
                    'tong_sau_giam' => max($subtotal - $discount, 0),
                    'so_lan_da_dung' => $usageCount,
                    'so_lan_con_lai' => $item->gioi_han_moi_khach !== null
                        ? max((int) $item->gioi_han_moi_khach - $usageCount, 0)
                        : null,
                ];
            })
            ->sortByDesc(fn (array $item) => ((int) $item['co_the_ap_dung'] * 10)
                + (int) ($item['tu_dong_ap_dung'] && $item['co_the_ap_dung']))
            ->values();

        return response()->json([
            'message' => 'Lấy danh sách mã giảm giá khả dụng thành công.',
            'data' => $items,
        ]);
    }

    public function validateCode(Request $request): JsonResponse
    {
        MaGiamGia::deactivateExpired();

        $validated = $request->validate([
            'ma_giam_gia' => ['required', 'string', 'min:4', 'max:30'],
            'tong_tam_tinh' => ['required', 'numeric', 'min:1'],
        ], [
            'ma_giam_gia.required' => 'Vui long nhap ma giam gia.',
            'tong_tam_tinh.required' => 'Thieu tong tien de kiem tra ma giam gia.',
        ]);

        $code = $this->normalizeCode($validated['ma_giam_gia']);
        $subtotal = (int) round((float) $validated['tong_tam_tinh']);

        $item = MaGiamGia::query()
            ->with('nhanVien')
            ->dangHoatDong()
            ->whereRaw('UPPER(ma_giam_gia) = ?', [$code])
            ->first();

        if (! $item) {
            return response()->json([
                'message' => 'Ma giam gia khong hop le hoac da het hieu luc.',
            ], 404);
        }

        $khachHang = $request->user();

        if (! $this->isCouponVisibleToCustomer($item, $khachHang)) {
            return response()->json([
                'message' => 'Ma giam gia khong ap dung cho tai khoan nay.',
            ], 403);
        }

        if (
            $khachHang instanceof KhachHang
            && $this->isFirstOrderCoupon($item)
            && $this->customerHasCompletedOrder($khachHang)
        ) {
            return response()->json([
                'message' => 'Ma don dau tien chi ap dung cho don hang dau tien.',
            ], 422);
        }

        if ($subtotal < (int) $item->gia_tri_don_toi_thieu) {
            return response()->json([
                'message' => 'Don hang chua dat gia tri toi thieu de dung ma nay.',
                'data' => [
                    'gia_tri_don_toi_thieu' => (int) $item->gia_tri_don_toi_thieu,
                ],
            ], 422);
        }

        $soLanDaDung = $khachHang instanceof KhachHang
            ? $this->usageCount($item->id, $khachHang->id_khach_hang)
            : 0;

        if (
            $khachHang instanceof KhachHang
            && $item->gioi_han_moi_khach !== null
            && $soLanDaDung >= (int) $item->gioi_han_moi_khach
        ) {
            return response()->json([
                'message' => 'Ban da dung het so lan cho phep cua ma giam gia nay.',
                'data' => [
                    'gioi_han_moi_khach' => (int) $item->gioi_han_moi_khach,
                    'so_lan_da_dung' => $soLanDaDung,
                ],
            ], 422);
        }

        $discount = $item->tinhTienGiam($subtotal);

        return response()->json([
            'message' => 'Ap dung ma giam gia thanh cong.',
            'data' => [
                ...$this->transform($item),
                'tong_tam_tinh' => $subtotal,
                'giam_gia_don_hang' => $discount,
                'tong_sau_giam' => max($subtotal - $discount, 0),
                'so_lan_da_dung' => $soLanDaDung,
                'so_lan_con_lai' => $item->gioi_han_moi_khach !== null
                    ? max((int) $item->gioi_han_moi_khach - $soLanDaDung, 0)
                    : null,
            ],
        ]);
    }

    public function redeemCode(Request $request): JsonResponse
    {
        MaGiamGia::deactivateExpired();

        $khachHang = $request->user();
        if (! $khachHang instanceof KhachHang) {
            return response()->json([
                'message' => 'Chi khach hang moi duoc su dung ma giam gia.',
            ], 403);
        }

        $validationResponse = $this->validateCode($request);
        if ($validationResponse->status() !== 200) {
            return $validationResponse;
        }

        $code = $this->normalizeCode((string) $request->input('ma_giam_gia'));
        $item = MaGiamGia::query()
            ->dangHoatDong()
            ->whereRaw('UPPER(ma_giam_gia) = ?', [$code])
            ->firstOrFail();

        $usage = MaGiamGiaLuotDung::firstOrCreate(
            [
                'ma_giam_gia_id' => $item->id,
                'id_khach_hang' => $khachHang->id_khach_hang,
            ],
            [
                'so_lan_su_dung' => 0,
            ]
        );

        $usage->increment('so_lan_su_dung');
        $usage->update(['lan_su_dung_cuoi' => now()]);
        $usage->refresh();

        return response()->json([
            'message' => 'Da ghi nhan su dung ma giam gia.',
            'data' => [
                'ma_giam_gia' => $item->ma_giam_gia,
                'so_lan_da_dung' => (int) $usage->so_lan_su_dung,
                'gioi_han_moi_khach' => $item->gioi_han_moi_khach !== null ? (int) $item->gioi_han_moi_khach : null,
            ],
        ]);
    }

    private function transform(MaGiamGia $item): array
    {
        return [
            'id' => $item->id,
            'ma_giam_gia' => $item->ma_giam_gia,
            'ten_ma' => $item->ten_ma,
            'mo_ta' => $item->mo_ta,
            'loai_ap_dung' => $item->loai_ap_dung,
            'gia_tri' => (int) $item->gia_tri,
            'gia_tri_don_toi_thieu' => (int) $item->gia_tri_don_toi_thieu,
            'gioi_han_moi_khach' => $item->gioi_han_moi_khach !== null ? (int) $item->gioi_han_moi_khach : null,
            'trang_thai' => $item->trang_thai,
            'ngay_bat_dau' => optional($item->ngay_bat_dau)->toDateTimeString(),
            'ngay_ket_thuc' => optional($item->ngay_ket_thuc)->toDateTimeString(),
            'da_bat_dau' => $item->isStarted(),
            'da_het_han' => $item->isExpired(),
            'dang_hoat_dong' => $item->isDangHoatDong(),
            'nhan_vien_cap_nhat' => $item->nhanVien?->ho_ten,
            'id_khach_hang' => $item->id_khach_hang !== null ? (int) $item->id_khach_hang : null,
            'loai_ma' => $item->loai_ma ?: 'general',
            'tu_dong_ap_dung' => (bool) $item->tu_dong_ap_dung,
        ];
    }

    private function isCouponVisibleToCustomer(MaGiamGia $item, mixed $khachHang): bool
    {
        if ($item->id_khach_hang === null) {
            return true;
        }

        return $khachHang instanceof KhachHang
            && (int) $item->id_khach_hang === (int) $khachHang->id_khach_hang;
    }

    private function isCouponAlreadyUnavailable(MaGiamGia $item, mixed $khachHang): bool
    {
        if (! $khachHang instanceof KhachHang) {
            return false;
        }

        if ($this->isFirstOrderCoupon($item) && $this->customerHasCompletedOrder($khachHang)) {
            return true;
        }

        if ($item->gioi_han_moi_khach === null) {
            return false;
        }

        return $this->usageCount($item->id, $khachHang->id_khach_hang) >= (int) $item->gioi_han_moi_khach;
    }

    private function customerCouponBlockReason(
        MaGiamGia $item,
        int $subtotal,
        mixed $khachHang,
        bool $checkMinimum = true
    ): ?string {
        if (! $this->isCouponVisibleToCustomer($item, $khachHang)) {
            return 'Ma giam gia khong ap dung cho tai khoan nay.';
        }

        if (
            $khachHang instanceof KhachHang
            && $this->isFirstOrderCoupon($item)
            && $this->customerHasCompletedOrder($khachHang)
        ) {
            return 'Ma don dau tien chi ap dung cho don hang dau tien.';
        }

        if (
            $khachHang instanceof KhachHang
            && $item->gioi_han_moi_khach !== null
            && $this->usageCount($item->id, $khachHang->id_khach_hang) >= (int) $item->gioi_han_moi_khach
        ) {
            return 'Ban da dung het so lan cho phep cua ma giam gia nay.';
        }

        if ($checkMinimum && $subtotal < (int) $item->gia_tri_don_toi_thieu) {
            return 'Chua du dieu kien de su dung ma nay.';
        }

        return null;
    }

    private function isFirstOrderCoupon(MaGiamGia $item): bool
    {
        return (string) $item->loai_ma === 'first_order';
    }

    private function customerHasCompletedOrder(KhachHang $khachHang): bool
    {
        return HoaDon::query()
            ->where('id_khach_hang', $khachHang->id_khach_hang)
            ->whereIn('trang_thai_xu_ly', ['cho_xac_nhan', 'da_xac_nhan', 'hoan_thanh'])
            ->whereHas('chiTiets')
            ->exists();
    }

    private function usageCount(int $maGiamGiaId, int $khachHangId): int
    {
        return (int) MaGiamGiaLuotDung::query()
            ->where('ma_giam_gia_id', $maGiamGiaId)
            ->where('id_khach_hang', $khachHangId)
            ->value('so_lan_su_dung');
    }

    private function normalizeCode(string $value): string
    {
        return Str::upper(trim($value));
    }

    private function normalizeDatePayload(array $payload): array
    {
        foreach (['ngay_bat_dau', 'ngay_ket_thuc'] as $field) {
            if (! filled($payload[$field] ?? null)) {
                $payload[$field] = null;
                continue;
            }

            $payload[$field] = Carbon::parse(
                (string) $payload[$field],
                'Asia/Ho_Chi_Minh'
            )->setTimezone(config('app.timezone'));
        }

        return $payload;
    }
}
