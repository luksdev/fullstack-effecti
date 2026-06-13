<?php

namespace Tests\Feature\Api;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Customer;
use App\Models\Service;

it('creates a contract via api', function () {
    $customer = Customer::factory()->create();

    $this->postJson('/api/contracts', [
        'customer_id' => $customer->id,
        'start_date' => '2026-01-01',
        'end_date' => null,
        'status' => ContractStatus::Active->value,
    ])->assertCreated()
        ->assertJsonPath('data.customer_id', $customer->id)
        ->assertJsonPath('data.pricing.total', '0.00');
});

it('exposes the pricing breakdown matching the expected total', function () {
    // subtotal 50000 -> quantity discount 5% (-2500) + progressive 5% (-2500) -> total 45000
    $contract = Contract::factory()->create();
    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => 5,
        'unit_price' => 10000,
    ]);

    $this->getJson("/api/contracts/{$contract->id}")
        ->assertOk()
        ->assertJsonPath('data.pricing.subtotal', '500.00')
        ->assertJsonPath('data.pricing.total', '450.00')
        ->assertJsonCount(2, 'data.pricing.adjustments')
        ->assertJsonPath('data.pricing.adjustments.0.amount', '-25.00');
});

it('adds an item via the nested api endpoint freezing the price', function () {
    $contract = Contract::factory()->create();
    $service = Service::factory()->create(['base_price' => 12345]);

    $this->postJson("/api/contracts/{$contract->id}/items", [
        'service_id' => $service->id,
        'quantity' => 1,
    ])->assertCreated()
        ->assertJsonPath('data.unit_price_cents', 12345)
        ->assertJsonPath('data.unit_price', '123.45');

    $this->assertDatabaseHas('contract_items', [
        'contract_id' => $contract->id,
        'service_id' => $service->id,
        'unit_price' => 12345,
    ]);
});

it('blocks duplicate services via api', function () {
    $contract = Contract::factory()->create();
    $service = Service::factory()->create();
    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'service_id' => $service->id,
    ]);

    $this->postJson("/api/contracts/{$contract->id}/items", [
        'service_id' => $service->id,
        'quantity' => 1,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('service_id');
});

it('removes an item via the nested api endpoint', function () {
    $contract = Contract::factory()->create();
    $item = ContractItem::factory()->create(['contract_id' => $contract->id]);

    $this->deleteJson("/api/contracts/{$contract->id}/items/{$item->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('contract_items', ['id' => $item->id]);
});

it('returns 404 when removing an item from the wrong contract', function () {
    $contract = Contract::factory()->create();
    $other = Contract::factory()->create();
    $item = ContractItem::factory()->create(['contract_id' => $other->id]);

    $this->deleteJson("/api/contracts/{$contract->id}/items/{$item->id}")
        ->assertNotFound();
});

it('soft deletes a contract via api', function () {
    $contract = Contract::factory()->create();

    $this->deleteJson("/api/contracts/{$contract->id}")
        ->assertNoContent();

    $this->assertSoftDeleted($contract);
});
