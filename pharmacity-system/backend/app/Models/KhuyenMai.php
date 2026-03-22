<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class KhuyenMai extends Model
{
    use HasFactory;

    protected $fillable = [
        'ma_thuoc',
        'ten_khuyen_mai',
        'mo_ta',
        'loai_ap_dung',
        'gia_tri',
        'nhan_hien_thi',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
        'id_nhan_vien',
    ];

    protected function casts(): array
    {
        return [
            'ngay_bat_dau' => 'datetime',
            'ngay_ket_thuc' => 'datetime',
        ];
    }

    public function thuoc(): BelongsTo
    {
        return $this->belongsTo(Thuoc::class, 'ma_thuoc', 'ma_thuoc');
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
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

    public function tinhGiaSauGiam(int $giaNiemYet): int
    {
        return match ($this->loai_ap_dung) {
            'phan_tram' => max((int) round($giaNiemYet - (($giaNiemYet * $this->gia_tri) / 100)), 0),
            'so_tien' => max($giaNiemYet - (int) $this->gia_tri, 0),
            'gia_co_dinh' => max(min((int) $this->gia_tri, $giaNiemYet), 0),
            default => $giaNiemYet,
        };
    }
}
