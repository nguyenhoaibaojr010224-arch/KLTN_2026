<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
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

    protected $appends = [
        'so_dien_thoai',
        'email',
        'dia_chi',
        'ngay_sinh',
        'gioi_tinh',
        'avatar_url',
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

    public function getSoDienThoaiAttribute(): ?string
    {
        return $this->thongTinNhanVien?->so_dien_thoai;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->thongTinNhanVien?->email;
    }

    public function getDiaChiAttribute(): ?string
    {
        return $this->thongTinNhanVien?->dia_chi;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $avatar = $this->thongTinNhanVien?->avatar;

        return $avatar ? Storage::disk('public')->url($avatar) : null;
    }

    public function getNgaySinhAttribute(): ?string
    {
        return $this->thongTinNhanVien?->ngay_sinh?->toDateString();
    }

    public function getGioiTinhAttribute(): ?string
    {
        return $this->thongTinNhanVien?->gioi_tinh;
    }
}
