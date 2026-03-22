<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LichSuDonHang extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_lich_su';

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'id_hoa_don',
        'trang_thai',
        'ghi_chu',
        'thoi_gian',
        'id_nhan_vien',
    ];

    protected function casts(): array
    {
        return [
            'thoi_gian' => 'datetime',
            'ngay_tao' => 'datetime',
            'ngay_cap_nhat' => 'datetime',
        ];
    }

    public function hoaDon(): BelongsTo
    {
        return $this->belongsTo(HoaDon::class, 'id_hoa_don', 'id_hoa_don');
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
    }
}
