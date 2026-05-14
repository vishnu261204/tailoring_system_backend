<?php

it('rejects unauthenticated dashboard access', function () {
    $this->getJson('/api/dashboard')->assertStatus(401);
});

it('rejects unauthenticated customer access', function () {
    $this->getJson('/api/customers')->assertStatus(401);
    $this->postJson('/api/customers', [])->assertStatus(401);
    $this->getJson('/api/customers/1')->assertStatus(401);
    $this->putJson('/api/customers/1', [])->assertStatus(401);
    $this->deleteJson('/api/customers/1')->assertStatus(401);
});

it('rejects unauthenticated order access', function () {
    $this->getJson('/api/orders')->assertStatus(401);
    $this->postJson('/api/orders', [])->assertStatus(401);
    $this->getJson('/api/orders/1')->assertStatus(401);
    $this->putJson('/api/orders/1', [])->assertStatus(401);
    $this->patchJson('/api/orders/1/status', [])->assertStatus(401);
});

it('rejects unauthenticated measurement access', function () {
    $this->getJson('/api/customers/1/measurements')->assertStatus(401);
    $this->postJson('/api/customers/1/measurements', [])->assertStatus(401);
    $this->getJson('/api/customers/1/measurements/latest')->assertStatus(401);
});

it('rejects unauthenticated user endpoint', function () {
    $this->getJson('/api/user')->assertStatus(401);
});

it('rejects unauthenticated logout', function () {
    $this->postJson('/api/logout')->assertStatus(401);
});
