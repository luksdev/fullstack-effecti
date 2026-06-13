<?php

namespace Tests\Feature\Api;

use App\Models\Service;

it('lists services as json', function () {
    Service::factory()->count(2)->create();

    $this->getJson('/api/services')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'base_price_cents', 'base_price']],
        ]);
});

it('converts cents to reais on output', function () {
    $service = Service::factory()->create(['base_price' => 15000]);

    $this->getJson("/api/services/{$service->id}")
        ->assertOk()
        ->assertJsonPath('data.base_price_cents', 15000)
        ->assertJsonPath('data.base_price', '150.00');
});

it('keeps two decimals for fractional reais', function () {
    $service = Service::factory()->create(['base_price' => 15050]);

    $this->getJson("/api/services/{$service->id}")
        ->assertOk()
        ->assertJsonPath('data.base_price', '150.50');
});

it('creates a service via api', function () {
    $this->postJson('/api/services', [
        'name' => 'Consultoria',
        'base_price' => 15000,
    ])->assertCreated()
        ->assertJsonPath('data.base_price_cents', 15000)
        ->assertJsonPath('data.base_price', '150.00');

    $this->assertDatabaseHas('services', ['name' => 'Consultoria', 'base_price' => 15000]);
});

it('rejects a negative price via api', function () {
    $this->postJson('/api/services', ['name' => 'Bad', 'base_price' => -1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('base_price');
});

it('updates a service via api', function () {
    $service = Service::factory()->create(['base_price' => 1000]);

    $this->putJson("/api/services/{$service->id}", [
        'name' => 'Renamed',
        'base_price' => 2000,
    ])->assertOk()
        ->assertJsonPath('data.base_price_cents', 2000);
});

it('soft deletes a service via api', function () {
    $service = Service::factory()->create();

    $this->deleteJson("/api/services/{$service->id}")
        ->assertNoContent();

    $this->assertSoftDeleted($service);
});
