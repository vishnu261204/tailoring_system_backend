<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\StatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusHistoryFactory extends Factory
{
    protected $model = StatusHistory::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'old_status' => fake()->randomElement([null, 'pending', 'in_progress', 'trial']),
            'new_status' => fake()->randomElement(['pending', 'in_progress', 'trial', 'completed', 'delivered']),
            'changed_at' => fake()->dateTimeBetween('-2 months', 'now'),
        ];
    }
}
