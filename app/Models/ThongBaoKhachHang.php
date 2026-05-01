<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ThongBaoKhachHang extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_thong_bao';

    protected $fillable = [
        'nhom',
        'tieu_de',
        'noi_dung',
        'loai_gui',
        'doi_tuong',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
        'id_nhan_vien_tao',
    ];

    protected function casts(): array
    {
        return [
            'ngay_bat_dau' => 'datetime',
            'ngay_ket_thuc' => 'datetime',
        ];
    }

    public function nhanVienTao(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien_tao', 'id_nhan_vien');
    }

    public function daDocs(): HasMany
    {
        return $this->hasMany(ThongBaoKhachHangDaDoc::class, 'id_thong_bao', 'id_thong_bao');
    }

    public function scopeDangHienThiChoKhachHang($query)
    {
        $now = Carbon::now();

        return $query
            ->where('doi_tuong', 'tat_ca_khach_hang')
            ->where('trang_thai', 'active')
            ->where(function ($innerQuery) use ($now): void {
                $innerQuery
                    ->whereNull('ngay_bat_dau')
                    ->orWhere('ngay_bat_dau', '<=', $now);
            })
            ->where(function ($innerQuery) use ($now): void {
                $innerQuery
                    ->whereNull('ngay_ket_thuc')
                    ->orWhere('ngay_ket_thuc', '>=', $now);
            });
    }
}
