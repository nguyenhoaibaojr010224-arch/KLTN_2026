<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThongBaoKhachHangDaDoc extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_thong_bao',
        'id_khach_hang',
        'da_doc_luc',
    ];

    protected function casts(): array
    {
        return [
            'da_doc_luc' => 'datetime',
        ];
    }

    public function thongBao(): BelongsTo
    {
        return $this->belongsTo(ThongBaoKhachHang::class, 'id_thong_bao', 'id_thong_bao');
    }

    public function khachHang(): BelongsTo
    {
        return $this->belongsTo(KhachHang::class, 'id_khach_hang', 'id_khach_hang');
    }
}
