<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HoTroHoiThoai extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_hoi_thoai';

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'id_khach_hang',
        'guest_session_id',
        'guest_display_name',
        'id_nhan_vien_phu_trach',
        'trang_thai',
        'thoi_gian_tin_nhan_cuoi',
    ];

    protected function casts(): array
    {
        return [
            'thoi_gian_tin_nhan_cuoi' => 'datetime',
            'ngay_tao' => 'datetime',
            'ngay_cap_nhat' => 'datetime',
        ];
    }

    public function khachHang(): BelongsTo
    {
        return $this->belongsTo(KhachHang::class, 'id_khach_hang', 'id_khach_hang');
    }

    public function nhanVienPhuTrach(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien_phu_trach', 'id_nhan_vien');
    }

    public function tinNhans(): HasMany
    {
        return $this->hasMany(HoTroTinNhan::class, 'id_hoi_thoai', 'id_hoi_thoai');
    }

    public function latestTinNhan(): HasOne
    {
        return $this->hasOne(HoTroTinNhan::class, 'id_hoi_thoai', 'id_hoi_thoai')->latestOfMany('thoi_gian');
    }
}
