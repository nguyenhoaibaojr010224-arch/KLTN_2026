<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThongTinNhanVien extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_nhan_vien';

    public $incrementing = false;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'id_nhan_vien',
        'so_dien_thoai',
        'email',
        'dia_chi',
        'avatar',
        'ngay_sinh',
        'gioi_tinh',
        'ngay_vao_lam',
    ];

    protected function casts(): array
    {
        return [
            'ngay_tao' => 'datetime',
            'ngay_cap_nhat' => 'datetime',
            'ngay_sinh' => 'date:Y-m-d',
            'ngay_vao_lam' => 'date',
        ];
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
    }
}
