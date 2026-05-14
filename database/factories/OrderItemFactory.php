<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $itemType = fake()->randomElement(['shirt', 'pant', 'custom']);

        $measurements = match ($itemType) {
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
            ],
            default => ['description' => fake()->sentence()],
        };

        return [
            'order_id' => Order::factory(),
            'item_type' => $itemType,
            'measurements' => $measurements,
            'notes' => fake()->optional()->sentence(),
            'fabric_details' => fake()->optional()->randomElement([
                ['type' => 'cotton', 'color' => 'white'],
                ['type' => 'linen', 'color' => 'blue'],
                ['type' => 'silk', 'color' => 'black'],
                ['type' => 'polyester', 'color' => 'grey'],
            ]),
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }
}
