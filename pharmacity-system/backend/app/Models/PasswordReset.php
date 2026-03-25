<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'email',
        'token',
        'expired_at',
        'used',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'ngay_cap_nhat' => 'datetime',
            'expired_at' => 'datetime',
            'used' => 'boolean',
        ];
    }
}
