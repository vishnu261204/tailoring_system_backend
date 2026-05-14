<?php

use App\Models\Customer;
use App\Models\Measurement;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $user = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($user);
});

it('lists measurements for a customer', function () {
    $customer = Customer::factory()->create();
    Measurement::factory(3)->create(['customer_id' => $customer->id]);

    $this->getJson("/api/customers/{$customer->id}/measurements")
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
});

it('filters measurements by type', function () {
    $customer = Customer::factory()->create();
    Measurement::factory()->create(['customer_id' => $customer->id, 'type' => 'shirt']);
    Measurement::factory()->create(['customer_id' => $customer->id, 'type' => 'pant']);

    $response = $this->getJson("/api/customers/{$customer->id}/measurements?type=shirt")
        ->assertStatus(200);

    expect($response->json('meta.total'))->toBe(1);
});

it('returns latest measurement', function () {
    $customer = Customer::factory()->create();
    Measurement::factory()->create(['customer_id' => $customer->id, 'type' => 'shirt', 'version' => 1]);
    $latest = Measurement::factory()->create(['customer_id' => $customer->id, 'type' => 'shirt', 'version' => 2]);

    $response = $this->getJson("/api/customers/{$customer->id}/measurements/latest?type=shirt")
        ->assertStatus(200);

    expect($response->json('measurement.version'))->toBe(2);
});

it('returns 404 when no measurement found', function () {
    $customer = Customer::factory()->create();

    $this->getJson("/api/customers/{$customer->id}/measurements/latest")
        ->assertStatus(404);
});

it('creates measurement with auto-incremented version', function () {
    $customer = Customer::factory()->create();
    Measurement::factory()->create([
        'customer_id' => $customer->id,
        'type' => 'shirt',
        'version' => 1,
    ]);

    $response = $this->postJson("/api/customers/{$customer->id}/measurements", [
        'type' => 'shirt',
        'data' => ['neck' => 16, 'chest' => 42],
    ]);

    $response->assertStatus(201);
    expect($response->json('measurement.version'))->toBe(2);
});

it('creates first measurement with version 1', function () {
    $customer = Customer::factory()->create();

    $response = $this->postJson("/api/customers/{$customer->id}/measurements", [
        'type' => 'shirt',
        'data' => ['neck' => 16],
    ]);

    $response->assertStatus(201);
    expect($response->json('measurement.version'))->toBe(1);
});

it('validates measurement type', function () {
    $customer = Customer::factory()->create();

    $this->postJson("/api/customers/{$customer->id}/measurements", [
        'type' => 'invalid',
        'data' => ['neck' => 16],
    ])->assertStatus(422);
});
