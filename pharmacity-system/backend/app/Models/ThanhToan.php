<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThanhToan extends Model
{
    use HasFactory;

    protected $table = 'thanh_toan';

    protected $primaryKey = 'id_hoa_don';

    public $incrementing = false;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'id_hoa_don',
        'phuong_thuc',
        'so_tien',
        'thoi_gian',
        'ma_giao_dich',
    ];

    protected function casts(): array
    {
        return [
            'so_tien' => 'decimal:2',
            'thoi_gian' => 'datetime',
            'ngay_tao' => 'datetime',
            'ngay_cap_nhat' => 'datetime',
        ];
    }

    public function hoaDon(): BelongsTo
    {
        return $this->belongsTo(HoaDon::class, 'id_hoa_don', 'id_hoa_don');
    }
}
