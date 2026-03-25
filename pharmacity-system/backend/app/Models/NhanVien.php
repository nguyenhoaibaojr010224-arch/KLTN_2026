<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class NhanVien extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\NhanVienFactory> */
    use HasFactory, HasApiTokens;

    protected $primaryKey = 'id_nhan_vien';

    protected $fillable = [
        'ten_dang_nhap',
        'mat_khau',
        'ho_ten',
        'id_vai_tro',
        'id_bang_cap',
        'trang_thai'
    ];

    protected $hidden = [
        'mat_khau',
    ];

    protected function casts(): array
    {
        return [
            'mat_khau' => 'hashed',
        ];
    }

    public function vaiTro()
    {
        return $this->belongsTo(VaiTro::class, 'id_vai_tro', 'id_vai_tro');
    }

    public function bangCap()
    {
        return $this->belongsTo(BangCap::class, 'id_bang_cap', 'id_bang_cap');
    }

    public function hoaDons(): HasMany
    {
        return $this->hasMany(HoaDon::class, 'id_nhan_vien', 'id_nhan_vien');
    }

    public function thongTinNhanVien(): HasOne
    {
        return $this->hasOne(ThongTinNhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
    }

    public function phieuNhaps(): HasMany
    {
        return $this->hasMany(PhieuNhap::class, 'id_nhan_vien', 'id_nhan_vien');
    }

    public function lichSuDonHangs(): HasMany
    {
        return $this->hasMany(LichSuDonHang::class, 'id_nhan_vien', 'id_nhan_vien');
    }
}
