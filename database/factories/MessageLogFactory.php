<?php

namespace Database\Factories;

use App\Models\MessageLog;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageLogFactory extends Factory
{
    protected $model = MessageLog::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'phone' => fake()->numerify('98########'),
            'message' => fake()->randomElement([
                'order_created',
                'trial_reminder',
                'order_completed',
                'order_delivered',
            ]),
            'status' => fake()->randomElement(['sent', 'failed']),
            'response' => fake()->optional()->sentence(),
        ];
    }
}
