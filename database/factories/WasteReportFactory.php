<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WasteReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WasteReport> */
class WasteReportFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'title' => fake()->sentence(4), 'description' => fake()->paragraph(),
            'category' => 'plastic', 'status' => 'pending', 'address' => fake()->streetAddress(),
            'latitude' => -6.2, 'longitude' => 106.8, 'photo_path' => 'reports/test.jpg'];
    }
}
