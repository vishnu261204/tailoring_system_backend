<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    User::factory()->create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);
});

it('registers a new user', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'New User',
        'email' => 'new@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'user', 'token']);
});

it('fails registration with duplicate email', function () {
    $this->postJson('/api/register', [
        'name' => 'User',
        'email' => 'admin@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(422);
});

it('logs in with valid credentials', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['message', 'user', 'token']);
});

it('fails login with invalid credentials', function () {
    $this->postJson('/api/login', [
        'email' => 'admin@test.com',
        'password' => 'wrong',
    ])->assertStatus(422);
});

it('logs out authenticated user', function () {
    Sanctum::actingAs(User::first());

    $this->postJson('/api/logout')
        ->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully']);
});

it('returns authenticated user', function () {
    Sanctum::actingAs(User::first());

    $this->getJson('/api/user')
        ->assertStatus(200)
        ->assertJson(['id' => 1]);
});

it('rejects unauthenticated access', function () {
    $this->getJson('/api/user')->assertStatus(401);
    $this->postJson('/api/logout')->assertStatus(401);
});
