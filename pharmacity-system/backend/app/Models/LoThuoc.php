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
        'so_luong_nhap',
        'so_luong_con',
        'gia_nhap',
    ];

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
