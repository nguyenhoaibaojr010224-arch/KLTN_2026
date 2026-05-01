<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiaChiKhachHang extends Model
{
    use HasFactory;

    protected $table = 'dia_chi_khach_hangs';

    protected $primaryKey = 'id_dia_chi';

    protected $fillable = [
        'id_khach_hang',
        'ho_ten',
        'so_dien_thoai',
        'tinh_thanh',
        'quan_huyen',
        'phuong_xa',
        'so_nha',
        'loai_dia_chi',
        'mac_dinh',
    ];

    protected function casts(): array
    {
        return [
            'mac_dinh' => 'boolean',
        ];
    }

    public function khachHang(): BelongsTo
    {
        return $this->belongsTo(KhachHang::class, 'id_khach_hang', 'id_khach_hang');
    }
}
