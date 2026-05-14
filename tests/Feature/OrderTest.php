<?php

use App\Models\Customer;
use App\Models\Measurement;
use App\Models\Order;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $user = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($user);
});

it('creates an order with auto-generated ID', function () {
    $customer = Customer::factory()->create();

    $response = $this->postJson('/api/orders', [
        'customer_id' => $customer->id,
        'order_date' => '2026-05-09',
        'items' => [
            [
                'item_type' => 'shirt',
                'quantity' => 2,
                'notes' => 'Formal shirt',
            ],
        ],
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'order']);
    expect($response->json('order.order_id'))->toMatch('/^ORD-2026-/');
    expect($response->json('order.status'))->toBe('pending');
});

it('creates order with measurement auto-fill', function () {
    $customer = Customer::factory()->create();
    Measurement::factory()->create([
        'customer_id' => $customer->id,
        'type' => 'shirt',
        'version' => 1,
        'data' => ['neck' => 16, 'chest' => 42],
    ]);

    $response = $this->postJson('/api/orders', [
        'customer_id' => $customer->id,
        'order_date' => '2026-05-09',
        'items' => [
            ['item_type' => 'shirt', 'quantity' => 1],
        ],
    ]);

    $response->assertStatus(201);
    $item = $response->json('order.items.0');
    expect($item['measurements'])->toBe(['neck' => 16, 'chest' => 42]);
});

it('allows measurement override on order', function () {
    $customer = Customer::factory()->create();

    $response = $this->postJson('/api/orders', [
        'customer_id' => $customer->id,
        'order_date' => '2026-05-09',
        'items' => [
            [
                'item_type' => 'shirt',
                'quantity' => 1,
                'measurements' => ['neck' => 17, 'chest' => 44],
            ],
        ],
    ]);

    $response->assertStatus(201);
    expect($response->json('order.items.0.measurements'))->toBe(['neck' => 17, 'chest' => 44]);
});

it('lists orders', function () {
    Order::factory(3)->create();

    $this->getJson('/api/orders')
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
});

it('filters orders by status', function () {
    Order::factory()->pending()->create();
    Order::factory()->inProgress()->create();
    Order::factory()->inProgress()->create();

    $response = $this->getJson('/api/orders?status=in_progress')
        ->assertStatus(200);

    expect($response->json('meta.total'))->toBe(2);
});

it('filters orders by date range', function () {
    Order::factory()->create(['order_date' => '2026-01-01']);
    Order::factory()->create(['order_date' => '2026-06-01']);

    $response = $this->getJson('/api/orders?from_date=2026-05-01&to_date=2026-12-31')
        ->assertStatus(200);

    expect($response->json('meta.total'))->toBe(1);
});

it('searches orders by order_id', function () {
    $order = Order::factory()->create(['order_id' => 'ORD-2026-0042']);

    $response = $this->getJson('/api/orders?search=0042')
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(1);
});

it('shows order with relations', function () {
    $order = Order::factory()->create();

    $this->getJson("/api/orders/{$order->id}")
        ->assertStatus(200)
        ->assertJsonStructure(['order' => ['customer', 'items']]);
});

it('returns 404 for non-existent order', function () {
    $this->getJson('/api/orders/999')->assertStatus(404);
});

it('validates order creation fails without items', function () {
    $customer = Customer::factory()->create();

    $this->postJson('/api/orders', [
        'customer_id' => $customer->id,
        'order_date' => '2026-05-09',
        'items' => [],
    ])->assertStatus(422);
});

it('validates status transition from pending to in_progress', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $this->patchJson("/api/orders/{$order->id}/status", [
        'status' => 'in_progress',
    ])->assertStatus(200);
});

it('validates status transition from pending to cancelled', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $this->patchJson("/api/orders/{$order->id}/status", [
        'status' => 'cancelled',
    ])->assertStatus(200);
});

it('rejects invalid status transition', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $this->patchJson("/api/orders/{$order->id}/status", [
        'status' => 'delivered',
    ])->assertStatus(422);
});

it('tracks status history on each change', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'in_progress']);
    $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'trial_ready']);

    $order->refresh();
    expect($order->statusHistory()->count())->toBe(2); // in_progress + trial_ready
});

it('updates order dates', function () {
    $order = Order::factory()->create();

    $this->putJson("/api/orders/{$order->id}", [
        'trial_date' => '2026-06-01',
        'delivery_date' => '2026-06-15',
    ])->assertStatus(200);
});

it('orders can be updated with new dates', function () {
    $order = Order::factory()->create();

    $this->putJson("/api/orders/{$order->id}", [
        'trial_date' => '2026-07-01',
    ])->assertStatus(200);
});
