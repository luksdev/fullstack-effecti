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
