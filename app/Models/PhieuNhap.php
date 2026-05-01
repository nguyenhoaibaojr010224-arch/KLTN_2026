<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhieuNhap extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_phieu_nhap';

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_phieu_nhap',
        'id_nha_san_xuat',
        'id_nhan_vien',
        'so_hoa_don_giay',
        'ngay_hoa_don',
        'chung_tu_url',
        'ghi_chu',
        'tong_tien',
        'ngay_nhap',
    ];

    protected function casts(): array
    {
        return [
            'tong_tien' => 'decimal:2',
            'ngay_hoa_don' => 'date',
            'ngay_nhap' => 'datetime',
            'ngay_tao' => 'datetime',
            'ngay_cap_nhat' => 'datetime',
        ];
    }

    public function nhaSanXuat(): BelongsTo
    {
        return $this->belongsTo(NhaSanXuat::class, 'id_nha_san_xuat');
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(ChiTietPhieuNhap::class, 'id_phieu_nhap', 'id_phieu_nhap');
    }
}
