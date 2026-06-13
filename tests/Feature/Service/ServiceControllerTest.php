<?php

namespace Tests\Feature\Service;

use App\Models\Service;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

it('lists services', function () {
    Service::factory()->count(3)->create();

    $this->get(route('services.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('services/Index', false)
            ->has('services.data', 3)
        );
});

it('creates a service with price in cents', function () {
    $response = $this->post(route('services.store'), [
        'name' => 'Consultoria',
        'base_price' => 15000,
    ]);

    $response->assertRedirect(route('services.index'));

    $this->assertDatabaseHas('services', [
        'name' => 'Consultoria',
        'base_price' => 15000,
    ]);
});

it('rejects a negative price', function () {
    $this->from(route('services.create'))
        ->post(route('services.store'), ['name' => 'Bad', 'base_price' => -1])
        ->assertSessionHasErrors('base_price');
});

it('rejects a non-integer price', function () {
    $this->from(route('services.create'))
        ->post(route('services.store'), ['name' => 'Bad', 'base_price' => 15.5])
        ->assertSessionHasErrors('base_price');
});

it('updates a service', function () {
    $service = Service::factory()->create(['base_price' => 1000]);

    $this->put(route('services.update', $service), [
        'name' => 'Renamed',
        'base_price' => 2000,
    ])->assertRedirect(route('services.index'));

    expect($service->fresh()->base_price)->toBe(2000)
        ->and($service->fresh()->name)->toBe('Renamed');
});

it('soft deletes a service', function () {
    $service = Service::factory()->create();

    $this->delete(route('services.destroy', $service))
        ->assertRedirect(route('services.index'));

    $this->assertSoftDeleted($service);
});
