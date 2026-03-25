<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NhaSanXuat extends Model
{
    /** @use HasFactory<\Database\Factories\NhaSanXuatFactory> */
    use HasFactory;

    protected $fillable = ['ten_nha_san_xuat', 'nuoc_san_xuat', 'dia_chi', 'so_dien_thoai'];

    public function thuocs(): HasMany
    {
        return $this->hasMany(Thuoc::class, 'id_nha_san_xuat');
    }

    public function phieuNhaps(): HasMany
    {
        return $this->hasMany(PhieuNhap::class, 'id_nha_san_xuat');
    }
}
