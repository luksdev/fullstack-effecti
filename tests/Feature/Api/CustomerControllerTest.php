<?php

namespace Tests\Feature\Api;

use App\Enums\CustomerStatus;
use App\Models\Customer;

it('lists customers as json', function () {
    Customer::factory()->count(2)->create();

    $this->getJson('/api/customers')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'federal_document', 'email', 'status' => ['value', 'label']]],
        ]);
});

it('creates a customer via api', function () {
    $response = $this->postJson('/api/customers', [
        'name' => 'Acme Ltda',
        'federal_document' => '11.222.333/0001-81',
        'email' => 'acme@example.com',
        'status' => CustomerStatus::Active->value,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.federal_document', '11222333000181')
        ->assertJsonPath('data.status.label', 'Ativo');

    $this->assertDatabaseHas('customers', ['email' => 'acme@example.com']);
});

it('returns validation errors for an invalid document via api', function () {
    $this->postJson('/api/customers', [
        'name' => 'Bad',
        'federal_document' => '111.111.111-11',
        'email' => 'bad@example.com',
        'status' => CustomerStatus::Active->value,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('federal_document');
});

it('shows a customer via api', function () {
    $customer = Customer::factory()->create();

    $this->getJson("/api/customers/{$customer->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $customer->id);
});

it('updates a customer via api', function () {
    $customer = Customer::factory()->create(['federal_document' => '52998224725']);

    $this->putJson("/api/customers/{$customer->id}", [
        'name' => 'Renamed',
        'federal_document' => '52998224725',
        'email' => $customer->email,
        'status' => CustomerStatus::Inactive->value,
    ])->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.status.value', 'inactive');
});

it('soft deletes a customer via api', function () {
    $customer = Customer::factory()->create();

    $this->deleteJson("/api/customers/{$customer->id}")
        ->assertNoContent();

    $this->assertSoftDeleted($customer);
});
