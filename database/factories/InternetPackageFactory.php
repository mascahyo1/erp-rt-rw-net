<?php

namespace Database\Factories;

use App\Models\InternetPackage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InternetPackageFactory extends Factory
{
    protected $model = InternetPackage::class;

    public function definition(): array
    {
        $codes = ['b10', 'p25', 'u50', 'b20', 'p50', 'u100', 'basic', 'pro', 'ultimate'];
        return [
            'id' => Str::uuid(),
            'company_id' => null,
            'code' => $this->faker->unique()->randomElement($codes) . $this->faker->numerify('##'),
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->randomElement([100000, 150000, 200000, 250000, 400000]),
            'speed_down_kbps' => $this->faker->randomElement([10240, 20480, 51200]),
            'speed_up_kbps' => $this->faker->randomElement([5120, 10240, 20480]),
            'quota_gb' => $this->faker->randomElement([100, 300, 500]),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'weekly', 'daily', 'yearly']),
            'is_unlimited' => $this->faker->boolean(30),
            'max_devices' => $this->faker->optional()->numberBetween(1, 10),
            'fup_quota_down' => $this->faker->optional()->numberBetween(10, 200),
            'fup_quota_up' => $this->faker->optional()->numberBetween(10, 100),
            'fup_speed_down_kbps' => $this->faker->optional()->numberBetween(512, 5120),
            'fup_speed_up_kbps' => $this->faker->optional()->numberBetween(256, 2560),
            'is_active' => true,
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
