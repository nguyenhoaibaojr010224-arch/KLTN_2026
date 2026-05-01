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
        'thoi_gian_dang_nhap',
        'dia_chi_ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'thoi_gian_dang_nhap' => 'datetime',
        ];
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
    }
}
