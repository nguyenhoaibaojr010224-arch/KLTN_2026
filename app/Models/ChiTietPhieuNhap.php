<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiTietPhieuNhap extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_phieu_nhaps';

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'id_phieu_nhap',
        'id_lo',
        'don_vi_nhap',
        'don_vi_co_so',
        'so_luong_nhap_goc',
        'he_so_quy_doi_nhap',
        'so_luong',
        'gia_nhap',
        'gia_nhap_quy_doi',
        'thanh_tien',
    ];

    protected function casts(): array
    {
        return [
            'so_luong_nhap_goc' => 'integer',
            'he_so_quy_doi_nhap' => 'integer',
            'so_luong' => 'integer',
            'gia_nhap' => 'decimal:2',
            'gia_nhap_quy_doi' => 'decimal:2',
            'thanh_tien' => 'decimal:2',
            'ngay_tao' => 'datetime',
            'ngay_cap_nhat' => 'datetime',
        ];
    }

    public function phieuNhap(): BelongsTo
    {
        return $this->belongsTo(PhieuNhap::class, 'id_phieu_nhap', 'id_phieu_nhap');
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'id_lo', 'id_lo');
    }
}
