<?php

namespace App\Http\Controllers;

use App\Models\KhuyenMai;
use App\Models\Thuoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CatalogThuocController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->string('q'));

        $thuocs = Thuoc::query()
            ->with([
                'loaiThuoc',
                'nhaSanXuat',
                'khuyenMais' => fn ($query) => $query->latest(),
            ])
            ->withSum('loThuocs as so_luong_ton', 'so_luong_con')
            ->orderBy('ten_thuoc')
            ->get()
            ->map(fn (Thuoc $thuoc) => $this->transformThuoc($thuoc));

        if ($keyword !== '') {
            $thuocs = $thuocs
                ->filter(fn (array $thuoc): bool => $this->matchesKeyword($thuoc, $keyword))
                ->values();
        }

        return response()->json([
            'message' => 'Lay danh muc thuoc thanh cong.',
            'data' => $thuocs,
        ]);
    }

    public function show(string $maThuoc): JsonResponse
    {
        $thuoc = Thuoc::query()
            ->with([
                'loaiThuoc',
                'nhaSanXuat',
                'khuyenMais' => fn ($query) => $query->latest(),
            ])
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
        $loai = $thuoc->loaiThuoc?->ten_loai ?: 'thuoc thong dung';
        $giaNiemYet = (int) $thuoc->gia_ban;
        $khuyenMai = $thuoc->khuyenMais
            ->first(fn (KhuyenMai $item): bool => $item->trang_thai === 'active'
                && $item->ngay_bat_dau?->lte(now())
                && ($item->ngay_ket_thuc === null || $item->ngay_ket_thuc->gte(now())));
        $giaSauGiam = $khuyenMai ? $khuyenMai->tinhGiaSauGiam($giaNiemYet) : $giaNiemYet;
        $baseDescription = "{$thuoc->ten_thuoc} la san pham thuoc thuoc nhom {$loai}, phu hop cho cac nhu cau cham soc suc khoe thong thuong va nen duoc su dung theo huong dan cua duoc si.";
        $trieuChung = $this->resolveSymptoms($thuoc)->values();
        $tacDungPhu = $this->resolveSideEffects($thuoc)->values();
        $hashtags = $this->resolveHashtags($thuoc, $trieuChung)->values();

        return [
            'ma_thuoc' => $thuoc->ma_thuoc,
            'ten_thuoc' => $thuoc->ten_thuoc,
            'ham_luong' => $thuoc->ham_luong,
            'don_vi_tinh' => $thuoc->don_vi_tinh,
            'gia_ban' => $giaSauGiam,
            'gia_niem_yet' => $giaNiemYet,
            'trang_thai' => $thuoc->trang_thai,
            'so_luong_ton' => (int) ($thuoc->so_luong_ton ?? 0),
            'loai_thuoc' => $thuoc->loaiThuoc?->ten_loai,
            'nha_san_xuat' => $thuoc->nhaSanXuat?->ten_nha_san_xuat,
            'mo_ta' => $baseDescription,
            'co_khuyen_mai' => (bool) $khuyenMai,
            'khuyen_mai' => $khuyenMai ? [
                'id' => $khuyenMai->id,
                'ten_khuyen_mai' => $khuyenMai->ten_khuyen_mai,
                'nhan_hien_thi' => $khuyenMai->nhan_hien_thi ?: $khuyenMai->ten_khuyen_mai,
                'loai_ap_dung' => $khuyenMai->loai_ap_dung,
                'gia_tri' => (int) $khuyenMai->gia_tri,
                'gia_sau_giam' => $giaSauGiam,
                'ngay_ket_thuc' => optional($khuyenMai->ngay_ket_thuc)->toDateTimeString(),
            ] : null,
            'trieu_chung' => $trieuChung,
            'tac_dung_phu' => $tacDungPhu,
            'hashtags' => $hashtags,
        ];
    }

    private function resolveSymptoms(Thuoc $thuoc): Collection
    {
        $ten = mb_strtolower($thuoc->ten_thuoc);
        $loai = mb_strtolower((string) ($thuoc->loaiThuoc?->ten_loai ?? ''));

        if (str_contains($ten, 'para') || str_contains($loai, 'ha sot')) {
            return collect(['Sot nhe', 'Dau dau', 'Dau nhuc co the']);
        }

        if (str_contains($ten, 'vitamin') || str_contains($loai, 'vitamin')) {
            return collect(['Met moi', 'Can bo sung vi chat', 'Can tang de khang']);
        }

        if (
            str_contains($loai, 'ho')
            || str_contains($loai, 'hong')
            || str_contains($ten, 'ho')
            || str_contains($ten, 'siro')
        ) {
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

    private function resolveHashtags(Thuoc $thuoc, Collection $trieuChung): Collection
    {
        $tenThuoc = $thuoc->ten_thuoc;
        $loaiThuoc = (string) ($thuoc->loaiThuoc?->ten_loai ?? '');
        $tenNormalized = $this->normalizeText($tenThuoc);
        $loaiNormalized = $this->normalizeText($loaiThuoc);

        $tags = collect([
            $tenThuoc,
            $loaiThuoc,
            'thuoc',
            'thuoc ' . $tenThuoc,
        ])->merge($trieuChung);

        if (
            str_contains($tenNormalized, 'ho')
            || str_contains($tenNormalized, 'siro')
            || str_contains($loaiNormalized, 'ho')
            || str_contains($loaiNormalized, 'hong')
        ) {
            $tags = $tags->merge([
                'thuoc ho',
                'thuoc dau hong',
                'thuoc ho khan',
                'thuoc ho co dom',
                'thuoc cam',
            ]);
        }

        if (str_contains($tenNormalized, 'vitamin') || str_contains($loaiNormalized, 'vitamin')) {
            $tags = $tags->merge([
                'vitamin',
                'vitamin tong hop',
                'thuc pham chuc nang',
                'bo sung de khang',
            ]);
        }

        if (str_contains($tenNormalized, 'para') || str_contains($loaiNormalized, 'ha sot')) {
            $tags = $tags->merge([
                'thuoc ha sot',
                'thuoc giam dau',
                'thuoc dau dau',
            ]);
        }

        return $tags
            ->filter(fn ($tag) => filled($tag))
            ->map(function (string $tag): string {
                $cleanTag = trim(preg_replace('/\s+/', ' ', $tag));
                return Str::startsWith($cleanTag, '#') ? $cleanTag : '#' . $cleanTag;
            })
            ->unique(fn (string $tag) => $this->normalizeText($tag))
            ->values();
    }

    private function matchesKeyword(array $thuoc, string $keyword): bool
    {
        $normalizedKeyword = $this->normalizeText($keyword);
        $tokens = collect(explode(' ', $normalizedKeyword))
            ->filter()
            ->values();

        $haystack = collect([
            $thuoc['ten_thuoc'] ?? '',
            $thuoc['ma_thuoc'] ?? '',
            $thuoc['loai_thuoc'] ?? '',
            $thuoc['nha_san_xuat'] ?? '',
            $thuoc['mo_ta'] ?? '',
        ])
            ->merge($thuoc['trieu_chung'] ?? [])
            ->merge($thuoc['tac_dung_phu'] ?? [])
            ->merge($thuoc['hashtags'] ?? [])
            ->map(fn ($value) => $this->normalizeText((string) $value))
            ->implode(' ');

        if ($normalizedKeyword !== '' && str_contains($haystack, $normalizedKeyword)) {
            return true;
        }

        return $tokens->isNotEmpty() && $tokens->every(fn (string $token): bool => str_contains($haystack, $token));
    }

    private function normalizeText(string $value): string
    {
        $normalized = Str::of($value)
            ->replace('#', ' ')
            ->ascii()
            ->lower()
            ->toString();

        $normalized = preg_replace('/[^a-z0-9\s]/', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        return trim($normalized);
    }
}
