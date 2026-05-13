<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone_country_code' => '+62',
            'phone_number' => fake()->numerify('8##########'),
            'is_active' => true,
            'address' => fake()->address(),
            'description' => fake()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
