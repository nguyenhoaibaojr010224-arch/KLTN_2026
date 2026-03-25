<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoaiThuoc extends Model
{
    /** @use HasFactory<\Database\Factories\LoaiThuocFactory> */
    use HasFactory;

    protected $fillable = ['ten_loai', 'mo_ta'];

    public function thuocs()
    {
        return $this->hasMany(Thuoc::class, 'id_loai_thuoc');
    }
}
