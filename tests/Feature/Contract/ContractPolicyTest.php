<?php

namespace Tests\Feature\Contract;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Service;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->actingAs(User::factory()->create());
});

it('blocks editing a cancelled contract on the web', function () {
    $contract = Contract::factory()->cancelled()->create();

    $this->put(route('contracts.update', $contract), [
        'customer_id' => $contract->customer_id,
        'start_date' => '2026-01-01',
        'end_date' => null,
        'status' => ContractStatus::Cancelled->value,
    ])->assertForbidden();
});

it('blocks adding items to a cancelled contract on the web', function () {
    $contract = Contract::factory()->cancelled()->create();
    $service = Service::factory()->create();

    $this->post(route('contracts.items.store', $contract), [
        'service_id' => $service->id,
        'quantity' => 1,
    ])->assertForbidden();

    $this->assertDatabaseMissing('contract_items', ['contract_id' => $contract->id]);
});

it('blocks removing items from a cancelled contract on the web', function () {
    $contract = Contract::factory()->cancelled()->create();
    $item = ContractItem::factory()->create(['contract_id' => $contract->id]);

    $this->delete(route('contracts.items.destroy', [$contract, $item]))
        ->assertForbidden();

    $this->assertDatabaseHas('contract_items', ['id' => $item->id]);
});

it('still allows deleting a cancelled contract on the web', function () {
    $contract = Contract::factory()->cancelled()->create();

    $this->delete(route('contracts.destroy', $contract))
        ->assertRedirect(route('contracts.index'));

    $this->assertSoftDeleted($contract);
});

it('blocks editing a cancelled contract via api', function () {
    $contract = Contract::factory()->cancelled()->create();

    $this->putJson("/api/contracts/{$contract->id}", [
        'customer_id' => $contract->customer_id,
        'start_date' => '2026-01-01',
        'end_date' => null,
        'status' => ContractStatus::Cancelled->value,
    ])->assertForbidden();
});

it('blocks adding items to a cancelled contract via api', function () {
    $contract = Contract::factory()->cancelled()->create();
    $service = Service::factory()->create();

    $this->postJson("/api/contracts/{$contract->id}/items", [
        'service_id' => $service->id,
        'quantity' => 1,
    ])->assertForbidden();
});

it('blocks removing items from a cancelled contract via api', function () {
    $contract = Contract::factory()->cancelled()->create();
    $item = ContractItem::factory()->create(['contract_id' => $contract->id]);

    $this->deleteJson("/api/contracts/{$contract->id}/items/{$item->id}")
        ->assertForbidden();
});

it('still allows deleting a cancelled contract via api', function () {
    $contract = Contract::factory()->cancelled()->create();

    $this->deleteJson("/api/contracts/{$contract->id}")
        ->assertNoContent();

    $this->assertSoftDeleted($contract);
});
