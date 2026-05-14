<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_id' => fake()->unique()->regexify('ORD-[0-9]{4}-[0-9]{4}'),
            'customer_id' => Customer::factory(),
            'order_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'trial_date' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'delivery_date' => fake()->optional()->dateTimeBetween('+1 month', '+2 months'),
            'status' => fake()->randomElement([
                'pending', 'in_progress', 'trial', 'completed', 'delivered',
            ]),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => 'in_progress']);
    }

    public function delivered(): static
    {
        return $this->state(fn () => ['status' => 'delivered', 'delivery_date' => fake()->dateTimeBetween('-1 week', 'now')]);
    }
}
