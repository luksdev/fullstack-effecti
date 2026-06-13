<?php

namespace Tests\Feature\Customer;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

it('lists customers', function () {
    Customer::factory()->count(3)->create();

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('customers/Index')
            ->has('customers.data', 3)
        );
});

it('creates a customer with a masked document', function () {
    $response = $this->post(route('customers.store'), [
        'name' => 'Acme Ltda',
        'federal_document' => '11.222.333/0001-81',
        'email' => 'acme@example.com',
        'status' => CustomerStatus::Active->value,
    ]);

    $response->assertRedirect(route('customers.index'));

    $this->assertDatabaseHas('customers', [
        'name' => 'Acme Ltda',
        'federal_document' => '11222333000181',
        'email' => 'acme@example.com',
        'status' => CustomerStatus::Active->value,
    ]);
});

it('rejects an invalid document', function () {
    $response = $this->from(route('customers.create'))->post(route('customers.store'), [
        'name' => 'Bad Doc',
        'federal_document' => '111.111.111-11',
        'email' => 'bad@example.com',
        'status' => CustomerStatus::Active->value,
    ]);

    $response->assertSessionHasErrors('federal_document');
    $this->assertDatabaseMissing('customers', ['email' => 'bad@example.com']);
});

it('rejects a duplicate document', function () {
    Customer::factory()->create(['federal_document' => '52998224725']);

    $response = $this->post(route('customers.store'), [
        'name' => 'Dup',
        'federal_document' => '529.982.247-25',
        'email' => 'dup@example.com',
        'status' => CustomerStatus::Active->value,
    ]);

    $response->assertSessionHasErrors('federal_document');
});

it('updates a customer ignoring its own unique document', function () {
    $customer = Customer::factory()->create([
        'federal_document' => '52998224725',
        'email' => 'keep@example.com',
    ]);

    $response = $this->put(route('customers.update', $customer), [
        'name' => 'Renamed',
        'federal_document' => '52998224725',
        'email' => 'keep@example.com',
        'status' => CustomerStatus::Inactive->value,
    ]);

    $response->assertRedirect(route('customers.index'));

    expect($customer->fresh()->name)->toBe('Renamed')
        ->and($customer->fresh()->status)->toBe(CustomerStatus::Inactive);
});

it('soft deletes a customer', function () {
    $customer = Customer::factory()->create();

    $this->delete(route('customers.destroy', $customer))
        ->assertRedirect(route('customers.index'));

    $this->assertSoftDeleted($customer);
});
