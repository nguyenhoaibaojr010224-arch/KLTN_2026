<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoTroTinNhan extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_tin_nhan';

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'id_hoi_thoai',
        'nguoi_gui_loai',
        'id_khach_hang',
        'id_nhan_vien',
        'noi_dung',
        'du_lieu_bo_sung',
        'da_doc',
        'thoi_gian',
    ];

    protected function casts(): array
    {
        return [
            'da_doc' => 'boolean',
            'du_lieu_bo_sung' => 'array',
            'thoi_gian' => 'datetime',
            'ngay_tao' => 'datetime',
            'ngay_cap_nhat' => 'datetime',
        ];
    }

    public function hoiThoai(): BelongsTo
    {
        return $this->belongsTo(HoTroHoiThoai::class, 'id_hoi_thoai', 'id_hoi_thoai');
    }

    public function khachHang(): BelongsTo
    {
        return $this->belongsTo(KhachHang::class, 'id_khach_hang', 'id_khach_hang');
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
    }
}
