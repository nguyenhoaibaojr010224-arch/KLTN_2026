<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoThuoc extends Model
{
    /** @use HasFactory<\Database\Factories\LoThuocFactory> */
    use HasFactory;

    protected $primaryKey = 'id_lo';

    protected $fillable = [
        'id_thuoc',
        'so_lo',
        'ngay_san_xuat',
        'han_su_dung',
        'don_vi_nhap',
        'don_vi_co_so',
        'so_luong_nhap',
        'so_luong_nhap_goc',
        'so_luong_con',
        'he_so_quy_doi_nhap',
        'gia_nhap',
        'gia_nhap_quy_doi',
    ];

    protected function casts(): array
    {
        return [
            'so_luong_nhap' => 'integer',
            'so_luong_nhap_goc' => 'integer',
            'so_luong_con' => 'integer',
            'he_so_quy_doi_nhap' => 'integer',
            'gia_nhap' => 'integer',
            'gia_nhap_quy_doi' => 'decimal:2',
        ];
    }

    public function thuoc()
    {
        return $this->belongsTo(Thuoc::class, 'id_thuoc', 'ma_thuoc');
    }

    public function chiTietPhieuNhaps(): HasMany
    {
        return $this->hasMany(ChiTietPhieuNhap::class, 'id_lo', 'id_lo');
    }

    public function chiTietHoaDons(): HasMany
    {
        return $this->hasMany(ChiTietHoaDon::class, 'id_lo', 'id_lo');
    }
}
