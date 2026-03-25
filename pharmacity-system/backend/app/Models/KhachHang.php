<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class KhachHang extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\KhachHangFactory> */
    use HasFactory, HasApiTokens;

    protected $primaryKey = 'id_khach_hang';

    protected $fillable = [
        'ten_khach_hang',
        'so_dien_thoai',
        'email',
        'dia_chi',
        'avatar',
        'diem_tich_luy',
        'mat_khau',
        'email_verified',
        'email_verified_at'
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected $hidden = [
        'mat_khau',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verified' => 'boolean',
            'mat_khau' => 'hashed',
        ];
    }

    public function hoaDons(): HasMany
    {
        return $this->hasMany(HoaDon::class, 'id_khach_hang', 'id_khach_hang');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar);
    }
}
