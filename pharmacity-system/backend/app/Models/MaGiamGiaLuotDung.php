<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaGiamGiaLuotDung extends Model
{
    use HasFactory;

    protected $fillable = [
        'ma_giam_gia_id',
        'id_khach_hang',
        'so_lan_su_dung',
        'lan_su_dung_cuoi',
    ];

    protected function casts(): array
    {
        return [
            'lan_su_dung_cuoi' => 'datetime',
        ];
    }

    public function maGiamGia(): BelongsTo
    {
        return $this->belongsTo(MaGiamGia::class, 'ma_giam_gia_id');
    }

    public function khachHang(): BelongsTo
    {
        return $this->belongsTo(KhachHang::class, 'id_khach_hang', 'id_khach_hang');
    }
}
