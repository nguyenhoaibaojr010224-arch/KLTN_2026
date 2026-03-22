<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'gia_ban',
        'trang_thai',
        'id_loai_thuoc',
        'id_nha_san_xuat',
    ];

    public function loaiThuoc()
    {
        return $this->belongsTo(LoaiThuoc::class, 'id_loai_thuoc');
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
}
