<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NhanVienDangNhapLog extends Model
{
    use HasFactory;

    protected $table = 'nhan_vien_dang_nhap_logs';

    protected $fillable = [
        'id_nhan_vien',
        'token_id',
        'kenh_dang_nhap',
        'thoi_gian_dang_nhap',
        'thoi_gian_dang_xuat',
        'het_han_luc',
        'thoi_luong_giay',
        'ly_do_dang_xuat',
        'dang_hoat_dong',
        'dia_chi_ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'thoi_gian_dang_nhap' => 'datetime',
            'thoi_gian_dang_xuat' => 'datetime',
            'het_han_luc' => 'datetime',
            'dang_hoat_dong' => 'boolean',
        ];
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
    }
}
