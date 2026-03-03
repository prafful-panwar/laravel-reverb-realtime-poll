<?php

namespace Database\Factories;

use App\Models\Poll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
/**
 * @extends Factory<Poll>
 */
use Illuminate\Support\Str;

class PollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slug' => Str::random(8),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
        ];
    }
}
