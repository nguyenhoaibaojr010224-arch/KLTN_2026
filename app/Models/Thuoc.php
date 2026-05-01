<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Thuoc extends Model
{
    /** @use HasFactory<\Database\Factories\ThuocFactory> */
    use HasFactory;

    protected $primaryKey = 'ma_thuoc';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'ma_thuoc',
        'ten_thuoc',
        'ham_luong',
        'don_vi_tinh',
        'don_vi_co_so',
        'he_so_quy_doi',
        'quy_cach_don_vi',
        'gia_ban',
        'mo_ta',
        'lieu_luong',
        'nhan',
        'trang_thai',
        'hinh_anh',
        'id_nha_san_xuat',
        'danh_muc_thuoc_slug',
    ];

    protected $appends = [
        'hinh_anh_url',
    ];

    protected function casts(): array
    {
        return [
            'he_so_quy_doi' => 'integer',
            'quy_cach_don_vi' => 'array',
        ];
    }

    public function buildUnitOptions(): array
    {
        $donViChinh = $this->normalizeUnitName($this->don_vi_tinh);
        $donViCoSo = $this->baseUnitName();
        $heSoQuyDoi = max(1, (int) ($this->he_so_quy_doi ?? 1));
        $baseStock = $this->resolveBaseStockQuantity();

        $options = [];

        if ($donViChinh !== '') {
            $options[] = [
                'ten_don_vi' => $donViChinh,
                'gia_ban' => (int) round((float) $this->gia_ban),
                'so_luong_quy_doi' => $heSoQuyDoi,
                'so_luong_ton' => (int) floor($baseStock / $heSoQuyDoi),
                'don_vi_co_so' => $donViCoSo,
                'mac_dinh' => true,
            ];
        }

        foreach ($this->quy_cach_don_vi ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $tenDonVi = $this->normalizeUnitName($item['ten_don_vi'] ?? null);
            if ($tenDonVi === '') {
                continue;
            }

            $options[] = [
                'ten_don_vi' => $tenDonVi,
                'gia_ban' => (int) ($item['gia_ban'] ?? 0),
                'so_luong_quy_doi' => max(1, (int) ($item['so_luong_quy_doi'] ?? 1)),
                'so_luong_ton' => (int) floor($baseStock / max(1, (int) ($item['so_luong_quy_doi'] ?? 1))),
                'don_vi_co_so' => $donViCoSo,
                'mac_dinh' => false,
            ];
        }

        $uniqueOptions = [];
        $seen = [];

        foreach ($options as $option) {
            $lookupKey = mb_strtolower($option['ten_don_vi']);
            if (isset($seen[$lookupKey])) {
                continue;
            }

            $seen[$lookupKey] = true;
            $uniqueOptions[] = $option;
        }

        return $uniqueOptions;
    }

    public function findUnitOption(?string $requestedUnit): ?array
    {
        $normalizedRequestedUnit = $this->normalizeUnitName($requestedUnit);
        $options = $this->buildUnitOptions();

        if ($normalizedRequestedUnit === '') {
            return $options[0] ?? null;
        }

        foreach ($options as $option) {
            if (mb_strtolower($option['ten_don_vi']) === mb_strtolower($normalizedRequestedUnit)) {
                return $option;
            }
        }

        return null;
    }

    public function baseUnitName(): string
    {
        return $this->normalizeUnitName($this->don_vi_co_so ?: $this->don_vi_tinh);
    }

    private function normalizeUnitName(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) ($value ?? '')));
    }

    private function resolveBaseStockQuantity(): int
    {
        if (array_key_exists('so_luong_ton_co_so', $this->attributes)) {
            return max(0, (int) ($this->attributes['so_luong_ton_co_so'] ?? 0));
        }

        if ($this->relationLoaded('loThuocs')) {
            return max(0, (int) $this->loThuocs->sum('so_luong_con'));
        }

        return max(0, (int) $this->loThuocs()->sum('so_luong_con'));
    }

    public function nhaSanXuat()
    {
        return $this->belongsTo(NhaSanXuat::class, 'id_nha_san_xuat');
    }

    public function loThuocs()
    {
        return $this->hasMany(LoThuoc::class, 'id_thuoc', 'ma_thuoc');
    }

    public function khuyenMais(): HasMany
    {
        return $this->hasMany(KhuyenMai::class, 'ma_thuoc', 'ma_thuoc');
    }

    public function getHinhAnhUrlAttribute(): ?string
    {
        if (! $this->hinh_anh) {
            return null;
        }

        if ($this->isExternalImageReference($this->hinh_anh)) {
            return $this->hinh_anh;
        }

        return url(Storage::disk('public')->url($this->hinh_anh));
    }

    private function isExternalImageReference(?string $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        return preg_match('/^https?:\/\//i', $value) === 1;
    }
}
