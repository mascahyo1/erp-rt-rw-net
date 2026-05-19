<?php

namespace Database\Factories;

use App\Models\CustInternet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustInternetFactory extends Factory
{
    protected $model = CustInternet::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'customer_id' => null,
            'internet_package_id' => null,
            'account_number' => 'NET-' . strtoupper(Str::random(8)),
            'internet_status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'usage_upload_kb' => $this->faker->optional()->randomFloat(2, 0, 100000),
            'usage_download_kb' => $this->faker->optional()->randomFloat(2, 0, 500000),
            'company_notes' => $this->faker->optional()->sentence(),
        ];
    }
}
