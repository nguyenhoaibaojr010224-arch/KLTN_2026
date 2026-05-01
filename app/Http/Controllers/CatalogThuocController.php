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
        $matchedHashtags = collect();

        $thuocs = Thuoc::query()
            ->with([
                'nhaSanXuat',
                'khuyenMais' => fn ($query) => $query->latest(),
            ])
            ->withSum('loThuocs as so_luong_ton_co_so', 'so_luong_con')
            ->orderBy('ten_thuoc')
            ->get()
            ->map(fn (Thuoc $thuoc) => $this->transformThuoc($thuoc));

        if ($keyword !== '') {
            ['thuocs' => $thuocs, 'matched_hashtags' => $matchedHashtags] = $this->searchThuocsByKeyword($thuocs, $keyword);
        }

        return response()->json([
            'message' => 'Lay danh muc thuoc thanh cong.',
            'data' => $thuocs,
            'meta' => [
                'matched_hashtags' => $matchedHashtags->values()->all(),
            ],
        ]);
    }

    public function show(string $maThuoc): JsonResponse
    {
        $thuoc = Thuoc::query()
            ->with([
                'nhaSanXuat',
                'khuyenMais' => fn ($query) => $query->latest(),
            ])
            ->withSum('loThuocs as so_luong_ton_co_so', 'so_luong_con')
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
        $nhanItems = $this->resolveNhanItems($thuoc);
        $nhanText = $nhanItems->implode(', ');
        $nhomThuoc = $nhanText !== '' ? $nhanText : 'thuoc thong dung';
        $giaNiemYet = (int) $thuoc->gia_ban;
        $khuyenMai = $thuoc->khuyenMais
            ->first(fn (KhuyenMai $item): bool => $item->trang_thai === 'active'
                && $item->ngay_bat_dau?->lte(now())
                && ($item->ngay_ket_thuc === null || $item->ngay_ket_thuc->gte(now())));
        $giaSauGiam = $khuyenMai ? $khuyenMai->tinhGiaSauGiam($giaNiemYet) : $giaNiemYet;
        $coKhuyenMai = $khuyenMai && $giaSauGiam < $giaNiemYet;
        $baseDescription = "{$thuoc->ten_thuoc} la san pham thuoc thuoc nhom {$nhomThuoc}, phu hop cho cac nhu cau cham soc suc khoe thong thuong va nen duoc su dung theo huong dan cua duoc si.";
        $description = filled($thuoc->mo_ta) ? trim((string) $thuoc->mo_ta) : $baseDescription;
        $trieuChung = $this->resolveSymptoms($thuoc)->values();
        $dosage = $this->resolveDosage($thuoc);
        $dosageLines = $this->resolveDosageLines($dosage);
        $hashtags = $this->resolveHashtags($thuoc, $trieuChung, $nhanItems)->values();
        $baseStock = (int) ($thuoc->so_luong_ton_co_so ?? 0);
        $unitOptions = $this->resolveUnitOptions($thuoc, $khuyenMai, $baseStock);
        $defaultUnit = collect($unitOptions)->first(fn (array $item): bool => (bool) ($item['mac_dinh'] ?? false)) ?? ($unitOptions[0] ?? null);

        return [
            'ma_thuoc' => $thuoc->ma_thuoc,
            'ten_thuoc' => $thuoc->ten_thuoc,
            'ham_luong' => $thuoc->ham_luong,
            'don_vi_tinh' => $thuoc->don_vi_tinh,
            'don_vi_co_so' => $thuoc->don_vi_co_so,
            'he_so_quy_doi' => (int) ($thuoc->he_so_quy_doi ?? 1),
            'quy_cach_don_vi' => $thuoc->quy_cach_don_vi,
            'don_vi_options' => $unitOptions,
            'hinh_anh' => $thuoc->hinh_anh,
            'hinh_anh_url' => $thuoc->hinh_anh_url,
            'gia_ban' => $giaSauGiam,
            'gia_niem_yet' => $giaNiemYet,
            'trang_thai' => $thuoc->trang_thai,
            'so_luong_ton' => (int) ($defaultUnit['so_luong_ton'] ?? $baseStock),
            'so_luong_ton_co_so' => $baseStock,
            'loai_thuoc' => $nhanText,
            'nhan' => $nhanText,
            'nhan_items' => $nhanItems->values()->all(),
            'danh_muc_thuoc_slug' => $thuoc->danh_muc_thuoc_slug,
            'nha_san_xuat' => $thuoc->nhaSanXuat?->ten_nha_san_xuat,
            'mo_ta' => $description,
            'lieu_luong' => $dosage,
            'lieu_luong_items' => $dosageLines,
            'co_khuyen_mai' => (bool) $coKhuyenMai,
            'khuyen_mai' => $coKhuyenMai ? [
                'id' => $khuyenMai->id,
                'ten_khuyen_mai' => $khuyenMai->ten_khuyen_mai,
                'nhan_hien_thi' => $khuyenMai->nhan_hien_thi ?: $khuyenMai->ten_khuyen_mai,
                'loai_ap_dung' => $khuyenMai->loai_ap_dung,
                'gia_tri' => (int) $khuyenMai->gia_tri,
                'gia_sau_giam' => $giaSauGiam,
                'ngay_ket_thuc' => optional($khuyenMai->ngay_ket_thuc)->toDateTimeString(),
            ] : null,
            'hashtags' => $hashtags,
        ];
    }

    private function resolveUnitOptions(Thuoc $thuoc, ?KhuyenMai $khuyenMai, int $baseStock): array
    {
        return collect($thuoc->buildUnitOptions())
            ->filter(fn (array $item): bool => $item['ten_don_vi'] !== '' && (int) ($item['gia_ban'] ?? 0) > 0)
            ->map(function (array $item) use ($khuyenMai, $baseStock): array {
                $giaNiemYet = (int) ($item['gia_ban'] ?? 0);
                $giaBan = $khuyenMai ? $khuyenMai->tinhGiaSauGiam($giaNiemYet) : $giaNiemYet;
                $soLuongQuyDoi = max(1, (int) ($item['so_luong_quy_doi'] ?? 1));

                return [
                    'ten_don_vi' => $item['ten_don_vi'],
                    'gia_ban' => $giaBan,
                    'gia_niem_yet' => $giaNiemYet,
                    'mac_dinh' => (bool) ($item['mac_dinh'] ?? false),
                    'so_luong_quy_doi' => $soLuongQuyDoi,
                    'don_vi_co_so' => $item['don_vi_co_so'] ?? $thuoc->baseUnitName(),
                    'so_luong_ton' => (int) floor($baseStock / $soLuongQuyDoi),
                ];
            })
            ->values()
            ->all();
    }

    private function resolveSymptoms(Thuoc $thuoc): Collection
    {
        $ten = $this->normalizeText((string) $thuoc->ten_thuoc);
        $nhan = $this->normalizeText($this->resolveNhanText($thuoc));

        if (str_contains($ten, 'para') || str_contains($nhan, 'ha sot')) {
            return collect(['Sot nhe', 'Dau dau', 'Dau nhuc co the']);
        }

        if (str_contains($ten, 'vitamin') || str_contains($nhan, 'vitamin')) {
            return collect(['Met moi', 'Can bo sung vi chat', 'Can tang de khang']);
        }

        if (
            $this->containsWord($nhan, 'ho')
            || $this->containsWord($nhan, 'hong')
            || $this->containsWord($ten, 'ho')
            || str_contains($ten, 'siro')
        ) {
            return collect(['Ho khan', 'Dau hong', 'Kho chiu duong ho hap']);
        }

        return collect(['Trieu chung thong thuong', 'Can tham khao duoc si', 'Cham soc suc khoe hang ngay']);
    }

    private function resolveDosage(Thuoc $thuoc): string
    {
        if (filled($thuoc->lieu_luong)) {
            return trim((string) $thuoc->lieu_luong);
        }

        $hamLuong = trim((string) ($thuoc->ham_luong ?? ''));

        return collect([
            $hamLuong !== '' ? "Tham khảo dược sĩ để được hướng dẫn liều phù hợp với hàm lượng {$hamLuong}." : null,
            'Đọc kỹ hướng dẫn sử dụng trước khi dùng.',
            'Không tự ý tăng liều hoặc kéo dài thời gian sử dụng nếu chưa có tư vấn chuyên môn.',
        ])->filter()->implode("\n");
    }

    private function resolveDosageLines(string $dosage): Collection
    {
        return collect(preg_split('/\r\n|\r|\n/', $dosage) ?: [])
            ->map(function (string $line): string {
                $normalizedLine = trim($line);

                if (! str_contains($normalizedLine, '|')) {
                    return $normalizedLine;
                }

                [$quantity, $interval] = array_pad(
                    array_map('trim', explode('|', $normalizedLine, 2)),
                    2,
                    ''
                );

                return collect([$quantity, $interval])
                    ->filter(fn (string $item): bool => $item !== '')
                    ->implode(' / ');
            })
            ->filter()
            ->values();
    }

    private function resolveHashtags(Thuoc $thuoc, Collection $trieuChung, Collection $nhanItems): Collection
    {
        $tenThuoc = $thuoc->ten_thuoc;
        $nhanText = $nhanItems->implode(' ');
        $tenNormalized = $this->normalizeText($tenThuoc);
        $nhanNormalized = $this->normalizeText($nhanText);

        $tags = collect([
            $tenThuoc,
            ...$nhanItems,
            'thuoc',
            'thuoc ' . $tenThuoc,
        ])->merge($trieuChung);

        if (
            $this->containsWord($tenNormalized, 'ho')
            || str_contains($tenNormalized, 'siro')
            || $this->containsWord($nhanNormalized, 'ho')
            || $this->containsWord($nhanNormalized, 'hong')
        ) {
            $tags = $tags->merge([
                'thuoc ho',
                'thuoc dau hong',
                'thuoc ho khan',
                'thuoc ho co dom',
                'thuoc cam',
            ]);
        }

        if (str_contains($tenNormalized, 'vitamin') || str_contains($nhanNormalized, 'vitamin')) {
            $tags = $tags->merge([
                'vitamin',
                'vitamin tong hop',
                'thuc pham chuc nang',
                'bo sung de khang',
            ]);
        }

        if (str_contains($tenNormalized, 'para') || str_contains($nhanNormalized, 'ha sot')) {
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
        return $this->keywordScore($thuoc, $keyword) > 0;
    }

    private function searchThuocsByKeyword(Collection $thuocs, string $keyword): array
    {
        $normalizedKeyword = $this->normalizeText($keyword);

        if ($normalizedKeyword === '') {
            return [
                'thuocs' => $thuocs->values(),
                'matched_hashtags' => collect(),
            ];
        }

        $directMatches = $thuocs
            ->map(function (array $thuoc) use ($keyword): array {
                $score = $this->keywordScore($thuoc, $keyword);

                return [
                    'thuoc' => $thuoc,
                    'score' => $score,
                    'hashtags' => $this->extractNormalizedHashtags($thuoc),
                ];
            })
            ->filter(fn (array $item): bool => $item['score'] > 0)
            ->values();

        $queryHashtags = $directMatches
            ->flatMap(fn (array $item): Collection => $item['hashtags']->filter(
                fn (array $tag): bool => str_contains($tag['normalized'], $normalizedKeyword)
                    || str_contains($normalizedKeyword, $tag['normalized'])
            ))
            ->filter(fn (array $tag): bool => $this->isExpandableHashtag($tag['normalized']))
            ->values();

        $strongMatchHashtags = $queryHashtags->isNotEmpty()
            ? collect()
            : $directMatches
                ->filter(fn (array $item): bool => $item['score'] >= 600)
                ->flatMap(fn (array $item): Collection => $item['hashtags'])
                ->filter(fn (array $tag): bool => $this->isExpandableHashtag($tag['normalized']))
                ->values();

        $matchedHashtags = $queryHashtags
            ->concat($strongMatchHashtags)
            ->unique('normalized')
            ->values()
            ->map(function (array $item) use ($thuocs): array {
                return [
                    'tag' => $item['tag'],
                    'so_luong_thuoc' => $thuocs->filter(
                        fn (array $thuoc): bool => $this->thuocHasHashtag($thuoc, $item['normalized'])
                    )->count(),
                    'normalized' => $item['normalized'],
                ];
            })
            ->sortByDesc('so_luong_thuoc')
            ->values();

        $matchedHashtagKeys = $matchedHashtags
            ->pluck('normalized')
            ->filter()
            ->values();

        $scoredThuocs = $thuocs
            ->map(function (array $thuoc) use ($keyword, $matchedHashtagKeys): array {
                $directScore = $this->keywordScore($thuoc, $keyword);
                $sharedHashtags = $this->extractNormalizedHashtags($thuoc)->filter(
                    fn (array $tag): bool => $matchedHashtagKeys->contains($tag['normalized'])
                );
                $sharedScore = $sharedHashtags->isEmpty()
                    ? 0
                    : (($directScore > 0 ? 80 : 220) + ($sharedHashtags->count() * 45));

                return [
                    'thuoc' => $thuoc,
                    'score' => $directScore + $sharedScore,
                ];
            })
            ->filter(fn (array $item): bool => $item['score'] > 0)
            ->sortByDesc('score')
            ->pluck('thuoc')
            ->values();

        return [
            'thuocs' => $scoredThuocs,
            'matched_hashtags' => $matchedHashtags->map(fn (array $item): array => [
                'tag' => $item['tag'],
                'so_luong_thuoc' => $item['so_luong_thuoc'],
            ]),
        ];
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

    private function tokenizeNormalized(string $value): Collection
    {
        return collect(explode(' ', $this->normalizeText($value)))
            ->filter()
            ->values();
    }

    private function containsWord(string $value, string $word): bool
    {
        return $this->tokenizeNormalized($value)->contains($this->normalizeText($word));
    }

    private function keywordScore(array $thuoc, string $keyword): int
    {
        $normalizedKeyword = $this->normalizeText($keyword);

        if ($normalizedKeyword === '') {
            return 0;
        }

        $fields = $this->buildSearchFields($thuoc);
        $tokens = $this->tokenizeNormalized($normalizedKeyword);
        $wordPool = collect([
            $fields['ten_thuoc'],
            $fields['ma_thuoc'],
            $fields['nhan'],
            $fields['loai_thuoc'],
            $fields['danh_muc_thuoc'],
            $fields['nha_san_xuat'],
            $fields['mo_ta'],
            $fields['lieu_luong'],
            ...$fields['hashtags'],
        ])
            ->flatMap(fn (string $value): Collection => $this->tokenizeNormalized($value))
            ->unique()
            ->values();

        $score = 0;

        if ($fields['ten_thuoc'] === $normalizedKeyword) {
            $score += 1000;
        } elseif ($fields['ten_thuoc'] !== '' && str_contains($fields['ten_thuoc'], $normalizedKeyword)) {
            $score += 650;
        }

        if ($fields['ma_thuoc'] === $normalizedKeyword) {
            $score += 900;
        } elseif ($fields['ma_thuoc'] !== '' && str_contains($fields['ma_thuoc'], $normalizedKeyword)) {
            $score += 500;
        }

        foreach ([
            ['value' => $fields['nhan'], 'score' => 420],
            ['value' => $fields['loai_thuoc'], 'score' => 360],
            ['value' => $fields['danh_muc_thuoc'], 'score' => 320],
            ['value' => $fields['nha_san_xuat'], 'score' => 200],
            ['value' => $fields['mo_ta'], 'score' => 180],
            ['value' => $fields['lieu_luong'], 'score' => 120],
        ] as $field) {
            if ($field['value'] !== '' && str_contains($field['value'], $normalizedKeyword)) {
                $score += $field['score'];
            }
        }

        if (collect($fields['hashtags'])->contains(fn (string $tag): bool => str_contains($tag, $normalizedKeyword))) {
            $score += 280;
        }

        if ($tokens->isNotEmpty() && $tokens->every(fn (string $token): bool => $wordPool->contains($token))) {
            $score += 140;
        }

        return $score;
    }

    private function buildSearchFields(array $thuoc): array
    {
        return [
            'ten_thuoc' => $this->normalizeText((string) ($thuoc['ten_thuoc'] ?? '')),
            'ma_thuoc' => $this->normalizeText((string) ($thuoc['ma_thuoc'] ?? '')),
            'loai_thuoc' => $this->normalizeText((string) ($thuoc['loai_thuoc'] ?? '')),
            'nhan' => $this->normalizeText((string) ($thuoc['nhan'] ?? '')),
            'danh_muc_thuoc' => $this->normalizeText(str_replace('-', ' ', (string) ($thuoc['danh_muc_thuoc_slug'] ?? ''))),
            'nha_san_xuat' => $this->normalizeText((string) ($thuoc['nha_san_xuat'] ?? '')),
            'mo_ta' => $this->normalizeText((string) ($thuoc['mo_ta'] ?? '')),
            'lieu_luong' => $this->normalizeText((string) ($thuoc['lieu_luong'] ?? '')),
            'hashtags' => collect($thuoc['hashtags'] ?? [])
                ->map(fn ($value): string => $this->normalizeText((string) $value))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function extractNormalizedHashtags(array $thuoc): Collection
    {
        return collect($thuoc['hashtags'] ?? [])
            ->map(function ($value): array {
                $tag = trim((string) $value);
                $normalized = $this->normalizeText($tag);

                return [
                    'tag' => Str::startsWith($tag, '#') ? $tag : '#' . $tag,
                    'normalized' => $normalized,
                ];
            })
            ->filter(fn (array $item): bool => $item['normalized'] !== '')
            ->unique('normalized')
            ->values();
    }

    private function thuocHasHashtag(array $thuoc, string $normalizedHashtag): bool
    {
        return $this->extractNormalizedHashtags($thuoc)
            ->contains(fn (array $tag): bool => $tag['normalized'] === $normalizedHashtag);
    }

    private function isExpandableHashtag(string $normalizedHashtag): bool
    {
        return ! in_array($normalizedHashtag, [
            '',
            'thuoc',
        ], true);
    }

    private function resolveNhanText(Thuoc $thuoc): string
    {
        return $this->resolveNhanItems($thuoc)->implode(', ');
    }

    private function resolveNhanItems(Thuoc $thuoc): Collection
    {
        $rawNhan = trim((string) ($thuoc->nhan ?? ''));

        $items = collect(preg_split('/[\r\n,;|]+/u', $rawNhan) ?: [])
            ->map(fn (string $item): string => trim(preg_replace('/\s+/u', ' ', $item)))
            ->filter()
            ->values();

        if ($items->isNotEmpty()) {
            return $items->unique(fn (string $item): string => $this->normalizeText($item))->values();
        }

        return collect();
    }
}
