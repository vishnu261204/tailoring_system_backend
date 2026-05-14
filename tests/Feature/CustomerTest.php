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

it('lists customers with pagination', function () {
    Customer::factory(5)->create();

    $this->getJson('/api/customers')
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'meta', 'links']);
});

it('searches customers by name', function () {
    Customer::factory()->create(['name' => 'Rahul Sharma']);
    Customer::factory(3)->create();

    $response = $this->getJson('/api/customers?search=Rahul')
        ->assertStatus(200);

    expect($response->json('meta.total'))->toBe(1);
});

it('searches customers by phone', function () {
    Customer::factory()->create(['phone' => '9876543210']);
    Customer::factory(3)->create();

    $response = $this->getJson('/api/customers?search=9876')
        ->assertStatus(200);

    expect($response->json('meta.total'))->toBe(1);
});

it('creates a customer', function () {
    $response = $this->postJson('/api/customers', [
        'name' => 'Rahul',
        'phone' => '9876543210',
        'address' => 'Mumbai',
        'gender' => 'male',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'customer']);
});

it('fails creating customer with duplicate phone', function () {
    Customer::factory()->create(['phone' => '9876543210']);

    $this->postJson('/api/customers', [
        'name' => 'Rahul',
        'phone' => '9876543210',
        'gender' => 'male',
    ])->assertStatus(422);
});

it('shows a customer', function () {
    $customer = Customer::factory()->create();

    $this->getJson("/api/customers/{$customer->id}")
        ->assertStatus(200)
        ->assertJsonStructure(['customer']);
});

it('updates a customer', function () {
    $customer = Customer::factory()->create();

    $this->putJson("/api/customers/{$customer->id}", [
        'name' => 'Updated Name',
    ])->assertStatus(200)
        ->assertJson(['message' => 'Customer updated successfully']);
});

it('allows same phone on update', function () {
    $customer = Customer::factory()->create(['phone' => '9876543210']);

    $this->putJson("/api/customers/{$customer->id}", [
        'phone' => '9876543210',
    ])->assertStatus(200);
});

it('deletes a customer', function () {
    $customer = Customer::factory()->create();

    $this->deleteJson("/api/customers/{$customer->id}")
        ->assertStatus(200)
        ->assertJson(['message' => 'Customer deleted successfully']);

    expect(Customer::find($customer->id))->toBeNull();
});

it('returns 404 for non-existent customer', function () {
    $this->getJson('/api/customers/999')->assertStatus(404);
    $this->putJson('/api/customers/999', ['name' => 'Test'])->assertStatus(404);
    $this->deleteJson('/api/customers/999')->assertStatus(404);
});

it('updates existing measurement by id instead of creating duplicates', function () {
    $customer = Customer::factory()->create();
    $measurement = $customer->measurements()->create([
        'type' => 'shirt',
        'data' => ['length' => '28', 'chest' => '40'],
    ]);

    $this->putJson("/api/customers/{$customer->id}", [
        'name' => 'Updated',
        'phone' => $customer->phone,
        'gender' => 'male',
        'measurements' => [
            ['id' => $measurement->id, 'type' => 'shirt', 'label' => 'shirt', 'fields' => ['length' => '30', 'chest' => '42']],
        ],
    ])->assertStatus(200);

    expect($customer->measurements()->count())->toBe(1);
    expect($customer->measurements()->first()->data)->toBe(['length' => '30', 'chest' => '42']);
});

it('creates new measurement when id is not provided', function () {
    $customer = Customer::factory()->create();

    $this->putJson("/api/customers/{$customer->id}", [
        'name' => 'Updated',
        'phone' => $customer->phone,
        'gender' => 'male',
        'measurements' => [
            ['type' => 'shirt', 'label' => 'shirt', 'fields' => ['length' => '30', 'chest' => '42']],
        ],
    ])->assertStatus(200);

    expect($customer->measurements()->count())->toBe(1);
    expect($customer->measurements()->first()->type)->toBe('shirt');
});

it('deletes measurements via deleted_ids array', function () {
    $customer = Customer::factory()->create();
    $shirt = $customer->measurements()->create(['type' => 'shirt', 'data' => ['length' => '28']]);
    $pant = $customer->measurements()->create(['type' => 'pant', 'data' => ['length' => '40']]);

    $this->putJson("/api/customers/{$customer->id}", [
        'name' => 'Updated',
        'phone' => $customer->phone,
        'gender' => 'male',
        'deleted_ids' => [$pant->id],
    ])->assertStatus(200);

    expect($customer->measurements()->count())->toBe(1);
    expect($customer->measurements()->first()->id)->toBe($shirt->id);
});

it('removes measurements not included in update when using deleted_ids', function () {
    $customer = Customer::factory()->create();
    $shirt = $customer->measurements()->create(['type' => 'shirt', 'data' => ['length' => '28']]);
    $pant = $customer->measurements()->create(['type' => 'pant', 'data' => ['length' => '40']]);

    $this->putJson("/api/customers/{$customer->id}", [
        'name' => 'Updated',
        'phone' => $customer->phone,
        'gender' => 'male',
        'measurements' => [
            ['id' => $shirt->id, 'type' => 'shirt', 'label' => 'shirt', 'fields' => ['length' => '30']],
        ],
        'deleted_ids' => [$pant->id],
    ])->assertStatus(200);

    expect($customer->measurements()->count())->toBe(1);
    expect($customer->measurements()->first()->type)->toBe('shirt');
});

it('preserves measurements not in payload when deleted_ids not provided', function () {
    $customer = Customer::factory()->create();
    $shirt = $customer->measurements()->create(['type' => 'shirt', 'data' => ['length' => '28']]);
    $customer->measurements()->create(['type' => 'pant', 'data' => ['length' => '40']]);

    $this->putJson("/api/customers/{$customer->id}", [
        'name' => 'Updated',
        'phone' => $customer->phone,
        'gender' => 'male',
        'measurements' => [
            ['id' => $shirt->id, 'type' => 'shirt', 'label' => 'shirt', 'fields' => ['length' => '30']],
        ],
    ])->assertStatus(200);

    expect($customer->measurements()->count())->toBe(2);
});

it('deletes all measurements when empty array sent', function () {
    $customer = Customer::factory()->create();
    $shirt = $customer->measurements()->create(['type' => 'shirt', 'data' => ['length' => '28']]);
    $pant = $customer->measurements()->create(['type' => 'pant', 'data' => ['length' => '40']]);

    $this->putJson("/api/customers/{$customer->id}", [
        'name' => 'Updated',
        'phone' => $customer->phone,
        'gender' => 'male',
        'measurements' => [],
        'deleted_ids' => [$shirt->id, $pant->id],
    ])->assertStatus(200);

    expect($customer->measurements()->count())->toBe(0);
});

it('stores and returns custom label for measurements', function () {
    $response = $this->postJson('/api/customers', [
        'name' => 'Test',
        'phone' => '9876543211',
        'gender' => 'male',
        'measurements' => [
            ['type' => 'custom', 'label' => 'Blazer', 'fields' => ['shoulder' => '18', 'chest' => '42']],
        ],
    ])->assertStatus(201);

    $customerId = $response->json('customer.id');
    $measurement = Measurement::where('customer_id', $customerId)->first();

    expect($measurement->label)->toBe('Blazer');
    expect($measurement->type)->toBe('custom');
});

it('updates custom label on existing measurement', function () {
    $customer = Customer::factory()->create();
    $measurement = $customer->measurements()->create([
        'type' => 'custom',
        'label' => 'Old Blazer',
        'data' => ['shoulder' => '18'],
    ]);

    $this->putJson("/api/customers/{$customer->id}", [
        'name' => 'Updated',
        'phone' => $customer->phone,
        'gender' => 'male',
        'measurements' => [
            ['id' => $measurement->id, 'type' => 'custom', 'label' => 'New Blazer', 'fields' => ['shoulder' => '20']],
        ],
    ])->assertStatus(200);

    expect($measurement->fresh()->label)->toBe('New Blazer');
    expect($measurement->fresh()->data)->toBe(['shoulder' => '20']);
});

it('returns custom label in show response', function () {
    $customer = Customer::factory()->create();
    $customer->measurements()->create([
        'type' => 'custom',
        'label' => 'Jacket Style A',
        'data' => ['length' => '30'],
    ]);

    $response = $this->getJson("/api/customers/{$customer->id}")->assertStatus(200);

    expect($response->json('measurements.0.label'))->toBe('Jacket Style A');
    expect($response->json('measurements.0.type'))->toBe('custom');
});

it('deletes cascades to measurements', function () {
    $customer = Customer::factory()->create();
    Measurement::factory()->create(['customer_id' => $customer->id]);
    Order::factory()->create(['customer_id' => $customer->id]);

    $this->deleteJson("/api/customers/{$customer->id}")->assertStatus(200);

    expect(Measurement::where('customer_id', $customer->id)->count())->toBe(0);
    expect(Order::where('customer_id', $customer->id)->count())->toBe(0);
});
