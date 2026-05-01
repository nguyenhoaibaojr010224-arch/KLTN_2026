<?php

namespace Database\Factories;

use App\Models\KhachHang;
use App\Models\PasswordReset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PasswordReset>
 */
class PasswordResetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => KhachHang::query()->inRandomOrder()->value('email') ?? $this->faker->safeEmail(),
            'token' => Str::random(64),
            'expired_at' => $this->faker->dateTimeBetween('now', '+2 days'),
            'used' => false,
        ];
    }
}
