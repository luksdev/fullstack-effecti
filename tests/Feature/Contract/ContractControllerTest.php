<?php

namespace Tests\Feature\Contract;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

it('lists contracts with their calculated total', function () {
    $contract = Contract::factory()->create();
    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => 5,
        'unit_price' => 10000,
    ]);

    $this->get(route('contracts.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('contracts/Index')
            ->has('contracts.data', 1)
            ->where('contracts.data.0.pricing.total', '450.00')
        );
});

it('creates a contract and redirects to edit', function () {
    $customer = Customer::factory()->create();

    $response = $this->post(route('contracts.store'), [
        'customer_id' => $customer->id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => ContractStatus::Active->value,
    ]);

    $contract = Contract::firstOrFail();
    $response->assertRedirect(route('contracts.edit', $contract));
    $response->assertSessionHas('toast');
    $this->assertDatabaseHas('contracts', ['customer_id' => $customer->id]);
});

it('rejects an end date before the start date', function () {
    $customer = Customer::factory()->create();

    $this->from(route('contracts.create'))->post(route('contracts.store'), [
        'customer_id' => $customer->id,
        'start_date' => '2026-12-31',
        'end_date' => '2026-01-01',
        'status' => ContractStatus::Active->value,
    ])->assertSessionHasErrors('end_date');
});

it('adds an item using an explicit unit price', function () {
    $contract = Contract::factory()->create();
    $service = Service::factory()->create(['base_price' => 10000]);

    $this->post(route('contracts.items.store', $contract), [
        'service_id' => $service->id,
        'quantity' => 2,
        'unit_price' => 7500,
    ])->assertRedirect(route('contracts.edit', $contract));

    $this->assertDatabaseHas('contract_items', [
        'contract_id' => $contract->id,
        'service_id' => $service->id,
        'quantity' => 2,
        'unit_price' => 7500,
    ]);
});

it('freezes the service base price when no unit price is given', function () {
    $contract = Contract::factory()->create();
    $service = Service::factory()->create(['base_price' => 12345]);

    $this->post(route('contracts.items.store', $contract), [
        'service_id' => $service->id,
        'quantity' => 1,
    ])->assertRedirect(route('contracts.edit', $contract));

    $this->assertDatabaseHas('contract_items', [
        'contract_id' => $contract->id,
        'service_id' => $service->id,
        'unit_price' => 12345,
    ]);

    // A later change to the service must not affect the frozen item price.
    $service->update(['base_price' => 99999]);
    expect(ContractItem::where('contract_id', $contract->id)->value('unit_price'))->toBe(12345);
});

it('blocks adding the same service twice', function () {
    $contract = Contract::factory()->create();
    $service = Service::factory()->create();
    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'service_id' => $service->id,
    ]);

    $this->from(route('contracts.edit', $contract))
        ->post(route('contracts.items.store', $contract), [
            'service_id' => $service->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('service_id');
});

it('removes an item from a contract', function () {
    $contract = Contract::factory()->create();
    $item = ContractItem::factory()->create(['contract_id' => $contract->id]);

    $this->delete(route('contracts.items.destroy', [$contract, $item]))
        ->assertRedirect(route('contracts.edit', $contract));

    $this->assertDatabaseMissing('contract_items', ['id' => $item->id]);
});

it('soft deletes a contract', function () {
    $contract = Contract::factory()->create();

    $this->delete(route('contracts.destroy', $contract))
        ->assertRedirect(route('contracts.index'));

    $this->assertSoftDeleted($contract);
});
