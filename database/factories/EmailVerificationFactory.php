<?php

namespace Database\Factories;

use App\Models\EmailVerification;
use App\Models\KhachHang;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailVerification>
 */
class EmailVerificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => KhachHang::query()->inRandomOrder()->value('email') ?? $this->faker->safeEmail(),
            'token' => Str::random(64),
            'expired_at' => $this->faker->dateTimeBetween('now', '+3 days'),
            'verified' => $this->faker->boolean(),
        ];
    }
}
