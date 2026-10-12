<?php

namespace Database\Factories;

use App\Enums\ShareMode;
use App\Models\Share;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Share>
 */
class ShareFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token_hash' => Share::hashToken(Str::random(22)),
            'mode' => ShareMode::WithoutPrices,
            'expires_at' => now()->addDays(7),
        ];
    }
}
