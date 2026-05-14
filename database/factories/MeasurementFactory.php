<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Measurement;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeasurementFactory extends Factory
{
    protected $model = Measurement::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['shirt', 'pant', 'custom']);

        $data = match ($type) {
            'shirt' => [
                'neck' => fake()->numberBetween(14, 18),
                'chest' => fake()->numberBetween(36, 46),
                'waist' => fake()->numberBetween(28, 40),
                'shoulder' => fake()->numberBetween(16, 22),
                'sleeve' => fake()->numberBetween(22, 26),
                'length' => fake()->numberBetween(26, 32),
            ],
            'pant' => [
                'waist' => fake()->numberBetween(28, 42),
                'length' => fake()->numberBetween(38, 44),
                'hips' => fake()->numberBetween(34, 46),
                'thigh' => fake()->numberBetween(20, 28),
                'bottom' => fake()->numberBetween(12, 20),
            ],
            default => [
                'description' => fake()->sentence(),
            ],
        };

        return [
            'customer_id' => Customer::factory(),
            'type' => $type,
            'data' => $data,
            'version' => fake()->numberBetween(1, 5),
        ];
    }
}
