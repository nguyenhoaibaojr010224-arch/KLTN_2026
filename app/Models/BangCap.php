<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BangCap extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_bang_cap';

    protected $fillable = ['ten_bang_cap'];

    public function nhanViens()
    {
        return $this->hasMany(NhanVien::class, 'id_bang_cap', 'id_bang_cap');
    }
}
