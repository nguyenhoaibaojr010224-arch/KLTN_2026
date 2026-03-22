<?php

namespace App\Http\Controllers;

use App\Models\Thuoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CatalogThuocController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->string('q'));

        $thuocs = Thuoc::query()
            ->with(['loaiThuoc', 'nhaSanXuat'])
            ->withSum('loThuocs as so_luong_ton', 'so_luong_con')
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($innerQuery) use ($keyword): void {
                    $innerQuery->where('ten_thuoc', 'like', '%' . $keyword . '%')
                        ->orWhere('ma_thuoc', 'like', '%' . $keyword . '%')
                        ->orWhereHas('loaiThuoc', fn ($loaiQuery) => $loaiQuery->where('ten_loai_thuoc', 'like', '%' . $keyword . '%'));
                });
            })
            ->orderBy('ten_thuoc')
            ->get();

        return response()->json([
            'message' => 'Lay danh muc thuoc thanh cong.',
            'data' => $thuocs->map(fn (Thuoc $thuoc) => $this->transformThuoc($thuoc)),
        ]);
    }

    public function show(string $maThuoc): JsonResponse
    {
        $thuoc = Thuoc::query()
            ->with(['loaiThuoc', 'nhaSanXuat'])
            ->withSum('loThuocs as so_luong_ton', 'so_luong_con')
            ->find($maThuoc);

        if (! $thuoc) {
            return response()->json([
                'message' => 'Khong tim thay thuoc.',
            ], 404);
        }

        return response()->json([
            'message' => 'Lay chi tiet thuoc thanh cong.',
            'data' => $this->transformThuoc($thuoc),
        ]);
    }

    private function transformThuoc(Thuoc $thuoc): array
    {
        $loai = $thuoc->loaiThuoc?->ten_loai_thuoc ?: 'thuoc thong dung';
        $baseDescription = "{$thuoc->ten_thuoc} la san pham thuoc thuoc nhom {$loai}, phu hop cho cac nhu cau cham soc suc khoe thong thuong va nen duoc su dung theo huong dan cua duoc si.";

        return [
            'ma_thuoc' => $thuoc->ma_thuoc,
            'ten_thuoc' => $thuoc->ten_thuoc,
            'ham_luong' => $thuoc->ham_luong,
            'don_vi_tinh' => $thuoc->don_vi_tinh,
            'gia_ban' => (int) $thuoc->gia_ban,
            'trang_thai' => $thuoc->trang_thai,
            'so_luong_ton' => (int) ($thuoc->so_luong_ton ?? 0),
            'loai_thuoc' => $thuoc->loaiThuoc?->ten_loai_thuoc,
            'nha_san_xuat' => $thuoc->nhaSanXuat?->ten_nha_san_xuat,
            'mo_ta' => $baseDescription,
            'trieu_chung' => $this->resolveSymptoms($thuoc),
            'tac_dung_phu' => $this->resolveSideEffects($thuoc),
        ];
    }

    private function resolveSymptoms(Thuoc $thuoc): Collection
    {
        $ten = mb_strtolower($thuoc->ten_thuoc);
        $loai = mb_strtolower((string) $thuoc->loaiThuoc?->ten_loai_thuoc);

        if (str_contains($ten, 'para') || str_contains($loai, 'ha sot')) {
            return collect(['Sot nhe', 'Dau dau', 'Dau nhuc co the']);
        }

        if (str_contains($ten, 'vitamin') || str_contains($loai, 'vitamin')) {
            return collect(['Met moi', 'Can bo sung vi chat', 'Can tang de khang']);
        }

        if (str_contains($loai, 'ho') || str_contains($loai, 'hong')) {
            return collect(['Ho khan', 'Dau hong', 'Kho chiu duong ho hap']);
        }

        return collect(['Trieu chung thong thuong', 'Can tham khao duoc si', 'Cham soc suc khoe hang ngay']);
    }

    private function resolveSideEffects(Thuoc $thuoc): Collection
    {
        $ten = mb_strtolower($thuoc->ten_thuoc);

        if (str_contains($ten, 'vitamin')) {
            return collect(['Kho chiu nhe duong tieu hoa', 'Day bung', 'Met nhe neu dung khong dung lieu']);
        }

        return collect(['Buon non nhe', 'Kho chiu da day', 'Chong mat nhe o mot so truong hop']);
    }
}
