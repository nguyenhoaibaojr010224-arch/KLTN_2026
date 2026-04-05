<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HoaDon extends Model
{
    /** @use HasFactory<\Database\Factories\HoaDonFactory> */
    use HasFactory;

    protected $primaryKey = 'id_hoa_don';

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_hoa_don',
        'id_khach_hang',
        'id_nhan_vien',
        'tong_tien',
        'giam_gia',
        'tien_thanh_toan',
        'ngay_ban',
    ];

    protected function casts(): array
    {
        return [
            'tong_tien' => 'decimal:2',
            'giam_gia' => 'decimal:2',
            'tien_thanh_toan' => 'decimal:2',
            'ngay_tao' => 'datetime',
            'ngay_cap_nhat' => 'datetime',
            'ngay_ban' => 'datetime',
        ];
    }

    public function khachHang(): BelongsTo
    {
        return $this->belongsTo(KhachHang::class, 'id_khach_hang', 'id_khach_hang');
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(ChiTietHoaDon::class, 'id_hoa_don', 'id_hoa_don');
    }

    public function thanhToan(): HasOne
    {
        return $this->hasOne(ThanhToan::class, 'id_hoa_don', 'id_hoa_don');
    }

    public function lichSuDonHangs(): HasMany
    {
        return $this->hasMany(LichSuDonHang::class, 'id_hoa_don', 'id_hoa_don');
    }

    public function latestLichSuDonHang(): HasOne
    {
        return $this->hasOne(LichSuDonHang::class, 'id_hoa_don', 'id_hoa_don')->latestOfMany('thoi_gian');
    }
}
