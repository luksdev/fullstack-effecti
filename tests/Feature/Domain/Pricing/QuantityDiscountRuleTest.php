<?php

namespace Tests\Feature\Domain\Pricing;

use App\Domain\Pricing\DTOs\PricingResult;
use App\Domain\Pricing\Rules\QuantityDiscountRule;
use App\Models\Contract;
use App\Models\ContractItem;

it('does not apply discount when quantity is below minimum', function () {
    $contract = Contract::factory()->create();

    $quantity = 2;
    $unitPrice = 1000;
    $subtotal = $quantity * $unitPrice;

    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
    ]);

    $contract->load('contractItems');

    $result = (new QuantityDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    expect($result->adjustments)->toBeEmpty()
        ->and($result->subtotal)->toBe($subtotal)
        ->and($result->total())->toBe($subtotal);
});

it('applies discount when quantity meets minimum', function () {
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

    $result = (new QuantityDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-2500)
        ->and($adjustment->label)->toBe('Desconto por quantidade (5%)')
        ->and($result->total())->toBe(47500);
});

it('applies discount when quantity equals the exact minimum', function () {
    $quantity = 3;
    $unitPrice = 20000;
    $subtotal = $quantity * $unitPrice;

    $contract = Contract::factory()->create();

    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
    ]);

    $contract->load('contractItems');

    $result = (new QuantityDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-3000)
        ->and($adjustment->label)->toBe('Desconto por quantidade (5%)')
        ->and($result->total())->toBe(57000);
});

it('rounds the discount to the nearest cent', function () {
    $quantity = 4;
    $unitPrice = 3333; // Subtotal will be 13332, 5% is 666.6 which should round to 667
    $subtotal = $quantity * $unitPrice;

    $contract = Contract::factory()->create();

    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
    ]);

    $contract->load('contractItems');

    $result = (new QuantityDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-667)
        ->and($adjustment->label)->toBe('Desconto por quantidade (5%)')
        ->and($result->total())->toBe(12665);
});

it('respects custom minimum and percentage', function () {
    $quantity = 10;
    $unitPrice = 5000;
    $subtotal = $quantity * $unitPrice;

    $contract = Contract::factory()->create();

    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
    ]);

    $contract->load('contractItems');

    $rule = new QuantityDiscountRule(minQuantity: 5, discountPercentage: 10);

    $result = $rule->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-5000)
        ->and($adjustment->label)->toBe('Desconto por quantidade (10%)')
        ->and($result->total())->toBe(45000);
});

it('sums quantity across multiple items', function () {
    $unitPrice = 2000;

    $contract = Contract::factory()->create();

    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => 2,
        'unit_price' => $unitPrice,
    ]);

    ContractItem::factory()->create([
        'contract_id' => $contract->id,
        'quantity' => 2,
        'unit_price' => $unitPrice,
    ]);

    $contract->load('contractItems');

    $subtotal = 4 * $unitPrice;

    $result = (new QuantityDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-400)
        ->and($adjustment->label)->toBe('Desconto por quantidade (5%)')
        ->and($result->total())->toBe(7600);
});