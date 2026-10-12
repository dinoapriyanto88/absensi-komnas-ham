<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->role ??= User::ROLE_ADMIN;
        });
    }

    public function superadmin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_SUPERADMIN]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN]);
    }
}
