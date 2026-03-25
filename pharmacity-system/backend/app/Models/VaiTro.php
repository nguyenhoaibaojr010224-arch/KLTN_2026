<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaiTro extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_vai_tro';

    protected $fillable = ['ten_vai_tro', 'mo_ta'];

    public function nhanViens()
    {
        return $this->hasMany(NhanVien::class, 'id_vai_tro', 'id_vai_tro');
    }
}
