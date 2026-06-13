<?php

namespace Tests\Feature\Domain\Pricing;

use App\Domain\Pricing\ContractPricingService;
use App\Domain\Pricing\Rules\QuantityDiscountRule;
use App\Models\Contract;
use App\Models\ContractItem;

it('calculates the subtotal from multiple contract items', function () {
    $contract = Contract::factory()->create();

    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => 2,
        'unit_price' => 1000,
    ]);

    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => 3,
        'unit_price' => 2000,
    ]);

    $contract->load('contractItems');

    $service = new ContractPricingService(pricingRules: []);

    $result = $service->calculate($contract);

    expect($result->subtotal)->toBe(8000)
        ->and($result->adjustments)->toBeEmpty()
        ->and($result->total())->toBe(8000);
});

it('returns zero subtotal when contract has no items', function () {
    $contract = Contract::factory()->create();

    $service = new ContractPricingService(pricingRules: []);

    $result = $service->calculate($contract);

    expect($result->subtotal)->toBe(0)
        ->and($result->adjustments)->toBeEmpty()
        ->and($result->total())->toBe(0);
});

it('applies registered rules to the calculated subtotal', function () {
    $quantity = 5;
    $unitPrice = 10000;
    $subtotal = $quantity * $unitPrice;

    $contract = Contract::factory()->create();

    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
    ]);

    $contract->load('contractItems');

    $service = new ContractPricingService([new QuantityDiscountRule()]);

    $result = $service->calculate($contract);

    expect($result->subtotal)->toBe($subtotal)
        ->and($result->adjustments)->toHaveCount(1)
        ->and($result->total())->toBe(47500);
});