<?php

namespace App\Http\Controllers;

use App\Models\LoThuoc;
use App\Models\Thuoc;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoThuocController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(LoThuoc::with('thuoc')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_thuoc' => 'required|string|exists:thuocs,ma_thuoc',
            'so_lo' => 'required|string|unique:lo_thuocs,so_lo',
            'ngay_san_xuat' => 'required|date',
            'han_su_dung' => 'required|date',
            'don_vi_nhap' => 'required|string|max:50',
            'so_luong_nhap_goc' => 'required|integer|min:1',
            'gia_nhap' => 'required|numeric|min:1',
        ]);

        $this->validateDateRange($validated['ngay_san_xuat'], $validated['han_su_dung']);

        $thuoc = Thuoc::query()->findOrFail($validated['id_thuoc']);
        $unitOption = $this->resolveUnitOption($thuoc, $validated['don_vi_nhap']);
        $soLuongNhapGoc = (int) $validated['so_luong_nhap_goc'];
        $heSoQuyDoi = max(1, (int) ($unitOption['so_luong_quy_doi'] ?? 1));
        $soLuongNhapQuyDoi = $soLuongNhapGoc * $heSoQuyDoi;
        $giaNhap = (int) round((float) $validated['gia_nhap']);

        $loThuoc = LoThuoc::create([
            'id_thuoc' => $validated['id_thuoc'],
            'so_lo' => trim((string) $validated['so_lo']),
            'ngay_san_xuat' => $validated['ngay_san_xuat'],
            'han_su_dung' => $validated['han_su_dung'],
            'don_vi_nhap' => $unitOption['ten_don_vi'],
            'don_vi_co_so' => $thuoc->baseUnitName(),
            'so_luong_nhap_goc' => $soLuongNhapGoc,
            'he_so_quy_doi_nhap' => $heSoQuyDoi,
            'so_luong_nhap' => $soLuongNhapQuyDoi,
            'so_luong_con' => $soLuongNhapQuyDoi,
            'gia_nhap' => $giaNhap,
            'gia_nhap_quy_doi' => round($giaNhap / $heSoQuyDoi, 2),
        ]);

        return response()->json($loThuoc->load('thuoc'), 201);
    }

    public function show($id): JsonResponse
    {
        $loThuoc = LoThuoc::with('thuoc')->find($id);

        if (! $loThuoc) {
            return response()->json(['message' => 'Không tìm thấy lô thuốc'], 404);
        }

        return response()->json($loThuoc);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $loThuoc = LoThuoc::with('thuoc')->find($id);

        if (! $loThuoc) {
            return response()->json(['message' => 'Không tìm thấy lô thuốc'], 404);
        }

        $validated = $request->validate([
            'id_thuoc' => 'sometimes|required|string|exists:thuocs,ma_thuoc',
            'so_lo' => ['sometimes', 'required', 'string', Rule::unique('lo_thuocs')->ignore($loThuoc->id_lo, 'id_lo')],
            'ngay_san_xuat' => 'sometimes|required|date',
            'han_su_dung' => 'sometimes|required|date',
            'don_vi_nhap' => 'sometimes|required|string|max:50',
            'so_luong_nhap_them_goc' => 'prohibited',
            'so_luong_nhap_goc' => 'prohibited',
            'so_luong_nhap' => 'prohibited',
            'so_luong_con' => 'prohibited',
            'he_so_quy_doi_nhap' => 'prohibited',
            'gia_nhap' => 'sometimes|required|numeric|min:1',
        ]);

        $ngaySanXuat = $validated['ngay_san_xuat'] ?? $loThuoc->ngay_san_xuat;
        $hanSuDung = $validated['han_su_dung'] ?? $loThuoc->han_su_dung;
        $this->validateDateRange((string) $ngaySanXuat, (string) $hanSuDung);

        if (isset($validated['id_thuoc']) && $validated['id_thuoc'] !== $loThuoc->id_thuoc) {
            throw ValidationException::withMessages([
                'id_thuoc' => ['Không thể đổi thuốc áp dụng của lô đã tồn tại.'],
            ]);
        }

        if (isset($validated['don_vi_nhap']) && $this->normalizeUnitName($validated['don_vi_nhap']) !== $this->normalizeUnitName($loThuoc->don_vi_nhap)) {
            throw ValidationException::withMessages([
                'don_vi_nhap' => ['Không thể đổi đơn vị nhập của lô đã tồn tại.'],
            ]);
        }

        if (isset($validated['gia_nhap'])) {
            $giaNhap = (int) round((float) $validated['gia_nhap']);
            $loThuoc->gia_nhap = $giaNhap;
            $loThuoc->gia_nhap_quy_doi = round($giaNhap / max(1, (int) $loThuoc->he_so_quy_doi_nhap), 2);
        }

        if (isset($validated['so_lo'])) {
            $loThuoc->so_lo = trim((string) $validated['so_lo']);
        }

        if (isset($validated['ngay_san_xuat'])) {
            $loThuoc->ngay_san_xuat = $validated['ngay_san_xuat'];
        }

        if (isset($validated['han_su_dung'])) {
            $loThuoc->han_su_dung = $validated['han_su_dung'];
        }

        $loThuoc->save();

        return response()->json($loThuoc->fresh()->load('thuoc'));
    }

    public function destroy($id): JsonResponse
    {
        $loThuoc = LoThuoc::find($id);

        if (! $loThuoc) {
            return response()->json(['message' => 'Không tìm thấy lô thuốc'], 404);
        }

        $loThuoc->delete();

        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->get('q', ''));

        $loThuocs = LoThuoc::with('thuoc')
            ->when($query !== '', function ($builder) use ($query): void {
                $builder->where(function ($innerQuery) use ($query): void {
                    $innerQuery->whereHas('thuoc', function ($q) use ($query): void {
                        $q->where('ten_thuoc', 'like', '%' . $query . '%')
                            ->orWhere('ma_thuoc', 'like', '%' . $query . '%');
                    })
                        ->orWhere('so_lo', 'like', '%' . $query . '%')
                        ->orWhere('don_vi_nhap', 'like', '%' . $query . '%')
                        ->orWhere('don_vi_co_so', 'like', '%' . $query . '%');
                });
            })
            ->get();

        return response()->json($loThuocs);
    }

    public function expiring(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $thresholdDate = Carbon::now()->addDays($days);

        $loThuocs = LoThuoc::with('thuoc')
            ->where('han_su_dung', '<=', $thresholdDate->format('Y-m-d'))
            ->where('han_su_dung', '>=', Carbon::now()->format('Y-m-d'))
            ->get();

        return response()->json($loThuocs);
    }

    public function alerts(Request $request): JsonResponse
    {
        $days = max(1, min(365, (int) $request->get('days', 30)));
        $lowStockThreshold = max(1, (int) $request->get('low_stock', 20));
        $today = Carbon::now()->startOfDay();
        $expiryLimit = $today->copy()->addDays($days);

        $expiringLots = LoThuoc::with('thuoc')
            ->where('so_luong_con', '>', 0)
            ->whereDate('han_su_dung', '>=', $today->toDateString())
            ->whereDate('han_su_dung', '<=', $expiryLimit->toDateString())
            ->orderBy('han_su_dung')
            ->get()
            ->map(function (LoThuoc $loThuoc) use ($today): array {
                $expiryDate = Carbon::parse($loThuoc->han_su_dung)->startOfDay();

                return [
                    'id' => 'expiring-lot-' . $loThuoc->id_lo,
                    'type' => 'expiring_lot',
                    'id_lo' => $loThuoc->id_lo,
                    'so_lo' => $loThuoc->so_lo,
                    'ma_thuoc' => $loThuoc->id_thuoc,
                    'ten_thuoc' => $loThuoc->thuoc?->ten_thuoc,
                    'han_su_dung' => $loThuoc->han_su_dung,
                    'so_ngay_con_lai' => max(0, (int) $today->diffInDays($expiryDate, false)),
                    'so_luong_con' => (float) $loThuoc->so_luong_con,
                    'don_vi_ton_kho' => $loThuoc->don_vi_co_so ?: $loThuoc->thuoc?->baseUnitName(),
                    'message' => sprintf(
                        'Lô %s của %s sắp hết hạn vào %s.',
                        $loThuoc->so_lo,
                        $loThuoc->thuoc?->ten_thuoc ?? $loThuoc->id_thuoc,
                        Carbon::parse($loThuoc->han_su_dung)->format('d/m/Y')
                    ),
                    'lo_thuoc' => $loThuoc,
                    'thuoc' => $loThuoc->thuoc,
                ];
            })
            ->values();

        $lowStockMedicines = Thuoc::with(['loThuocs' => function ($query): void {
            $query->where('so_luong_con', '>', 0)->orderBy('so_luong_con');
        }])
            ->get()
            ->map(function (Thuoc $thuoc) use ($lowStockThreshold): ?array {
                $remainingStock = (float) $thuoc->loThuocs->sum('so_luong_con');

                if ($remainingStock <= 0 || $remainingStock > $lowStockThreshold) {
                    return null;
                }

                $stockUnit = $thuoc->baseUnitName();

                return [
                    'id' => 'low-stock-' . $thuoc->ma_thuoc,
                    'type' => 'low_stock',
                    'ma_thuoc' => $thuoc->ma_thuoc,
                    'ten_thuoc' => $thuoc->ten_thuoc,
                    'so_luong_con' => $remainingStock,
                    'don_vi_ton_kho' => $stockUnit,
                    'nguong_canh_bao' => $lowStockThreshold,
                    'message' => sprintf(
                        '%s gần hết hàng, hiện còn %s %s.',
                        $thuoc->ten_thuoc,
                        rtrim(rtrim(number_format($remainingStock, 2, '.', ''), '0'), '.'),
                        $stockUnit
                    ),
                    'lo_thuocs' => $thuoc->loThuocs->values(),
                    'thuoc' => $thuoc,
                ];
            })
            ->filter()
            ->sortBy('so_luong_con')
            ->values();

        return response()->json([
            'message' => 'Lấy cảnh báo tồn kho thành công.',
            'data' => [
                'lo_sap_het_han' => $expiringLots,
                'thuoc_gan_het_ton' => $lowStockMedicines,
            ],
            'tong_thong_bao' => $expiringLots->count() + $lowStockMedicines->count(),
            'nguong' => [
                'so_ngay_sap_het_han' => $days,
                'so_luong_gan_het_ton' => $lowStockThreshold,
            ],
        ]);
    }

    private function resolveUnitOption(Thuoc $thuoc, string $requestedUnit): array
    {
        $matchedUnit = $thuoc->findUnitOption($requestedUnit);

        if (! $matchedUnit) {
            throw ValidationException::withMessages([
                'don_vi_nhap' => ["Đơn vị {$requestedUnit} không hợp lệ cho thuốc {$thuoc->ten_thuoc}."],
            ]);
        }

        return $matchedUnit;
    }

    private function validateDateRange(string $ngaySanXuat, string $hanSuDung): void
    {
        if (Carbon::parse($hanSuDung)->lte(Carbon::parse($ngaySanXuat))) {
            throw ValidationException::withMessages([
                'han_su_dung' => ['Hạn sử dụng phải sau ngày sản xuất.'],
            ]);
        }
    }

    private function normalizeUnitName(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) ($value ?? '')));
    }
}
