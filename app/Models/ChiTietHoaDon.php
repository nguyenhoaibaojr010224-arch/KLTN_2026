<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiTietHoaDon extends Model
{
    use HasFactory;

    protected $table = 'chi_tiet_hoa_don';

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'id_hoa_don',
        'id_lo',
        'don_vi_ban',
        'he_so_quy_doi_ban',
        'so_luong',
        'gia_ban',
        'thanh_tien',
    ];

    protected function casts(): array
    {
        return [
            'he_so_quy_doi_ban' => 'integer',
            'so_luong' => 'integer',
            'gia_ban' => 'decimal:2',
            'thanh_tien' => 'decimal:2',
            'ngay_tao' => 'datetime',
            'ngay_cap_nhat' => 'datetime',
        ];
    }

    public function hoaDon(): BelongsTo
    {
        return $this->belongsTo(HoaDon::class, 'id_hoa_don', 'id_hoa_don');
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'id_lo', 'id_lo');
    }
}
