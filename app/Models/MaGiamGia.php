<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class MaGiamGia extends Model
{
    use HasFactory;

    protected $fillable = [
        'ma_giam_gia',
        'ten_ma',
        'mo_ta',
        'loai_ap_dung',
        'gia_tri',
        'gia_tri_don_toi_thieu',
        'gioi_han_moi_khach',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
        'id_nhan_vien',
        'id_khach_hang',
        'loai_ma',
        'tu_dong_ap_dung',
    ];

    protected function casts(): array
    {
        return [
            'ngay_bat_dau' => 'datetime',
            'ngay_ket_thuc' => 'datetime',
            'tu_dong_ap_dung' => 'boolean',
        ];
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
    }

    public function khachHang(): BelongsTo
    {
        return $this->belongsTo(KhachHang::class, 'id_khach_hang', 'id_khach_hang');
    }

    public function luotDungs(): HasMany
    {
        return $this->hasMany(MaGiamGiaLuotDung::class, 'ma_giam_gia_id');
    }

    public function scopeDangHoatDong($query)
    {
        $now = Carbon::now();

        return $query
            ->where('trang_thai', 'active')
            ->where('ngay_bat_dau', '<=', $now)
            ->where(function ($innerQuery) use ($now): void {
                $innerQuery
                    ->whereNull('ngay_ket_thuc')
                    ->orWhere('ngay_ket_thuc', '>=', $now);
            });
    }

    public static function deactivateExpired(): int
    {
        return static::query()
            ->where('trang_thai', 'active')
            ->whereNotNull('ngay_ket_thuc')
            ->where('ngay_ket_thuc', '<', Carbon::now())
            ->update([
                'trang_thai' => 'inactive',
                'updated_at' => Carbon::now(),
            ]);
    }

    public function isExpired(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        return $this->ngay_ket_thuc !== null
            && $this->ngay_ket_thuc->lt($now);
    }

    public function isStarted(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        return $this->ngay_bat_dau === null
            || $this->ngay_bat_dau->lte($now);
    }

    public function isDangHoatDong(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        return $this->trang_thai === 'active'
            && $this->isStarted($now)
            && ! $this->isExpired($now);
    }

    public function tinhGiaSauGiam(int $tongDon): int
    {
        return match ($this->loai_ap_dung) {
            'phan_tram' => max((int) round($tongDon - (($tongDon * $this->gia_tri) / 100)), 0),
            'so_tien' => max($tongDon - (int) $this->gia_tri, 0),
            'gia_co_dinh' => max(min((int) $this->gia_tri, $tongDon), 0),
            default => $tongDon,
        };
    }

    public function tinhTienGiam(int $tongDon): int
    {
        return max($tongDon - $this->tinhGiaSauGiam($tongDon), 0);
    }
}
