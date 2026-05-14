<?php

use App\Models\Order;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $user = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($user);
});

it('returns dashboard stats', function () {
    Order::factory()->pending()->create();
    Order::factory()->inProgress()->create();
    Order::factory()->inProgress()->create();
    Order::factory()->delivered()->create();

    $response = $this->getJson('/api/dashboard')
        ->assertStatus(200)
        ->assertJsonStructure(['stats' => [
            'total_orders', 'pending', 'in_progress', 'trial',
            'completed', 'delivered', 'cancelled', 'today_deliveries',
        ]]);

    expect($response->json('stats'))->toEqual([
        'total_orders' => 4,
        'pending' => 1,
        'in_progress' => 2,
        'trial' => 0,
        'completed' => 0,
        'delivered' => 1,
        'cancelled' => 0,
        'today_deliveries' => 0,
    ]);
});

it('returns zero counts when no orders exist', function () {
    $response = $this->getJson('/api/dashboard')->assertStatus(200);

    expect($response->json('stats'))->toEqual([
        'total_orders' => 0,
        'pending' => 0,
        'in_progress' => 0,
        'trial' => 0,
        'completed' => 0,
        'delivered' => 0,
        'cancelled' => 0,
        'today_deliveries' => 0,
    ]);
});
