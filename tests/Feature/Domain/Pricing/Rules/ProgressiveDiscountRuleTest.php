<?php

namespace Tests\Feature\Domain\Pricing\Rules;

use App\Domain\Pricing\DTOs\Adjustment;
use App\Domain\Pricing\DTOs\PricingResult;
use App\Domain\Pricing\Rules\ProgressiveDiscountRule;
use App\Models\Contract;

it('does not apply discount when subtotal is below the lowest tier', function () {
    $contract = Contract::factory()->create();
    $subtotal = 40000;

    $result = (new ProgressiveDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    expect($result->adjustments)->toBeEmpty()
        ->and($result->subtotal)->toBe($subtotal)
        ->and($result->total())->toBe($subtotal);
});

it('applies the lower tier discount when subtotal reaches the lower threshold', function () {
    $contract = Contract::factory()->create();
    $subtotal = 60000;

    $result = (new ProgressiveDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-3000)
        ->and($adjustment->label)->toBe('Desconto progressivo (5%)')
        ->and($result->total())->toBe(57000);
});

it('applies the higher tier discount when subtotal reaches the higher threshold', function () {
    $contract = Contract::factory()->create();
    $subtotal = 200000;

    $result = (new ProgressiveDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-20000)
        ->and($adjustment->label)->toBe('Desconto progressivo (10%)')
        ->and($result->total())->toBe(180000);
});

it('applies the lower tier discount when subtotal equals its exact threshold', function () {
    $contract = Contract::factory()->create();
    $subtotal = 50000;

    $result = (new ProgressiveDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-2500)
        ->and($adjustment->label)->toBe('Desconto progressivo (5%)')
        ->and($result->total())->toBe(47500);
});

it('applies the higher tier discount when subtotal equals its exact threshold', function () {
    $contract = Contract::factory()->create();
    $subtotal = 100000;

    $result = (new ProgressiveDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-10000)
        ->and($adjustment->label)->toBe('Desconto progressivo (10%)')
        ->and($result->total())->toBe(90000);
});

it('applies the highest applicable tier when more than one matches', function () {
    $contract = Contract::factory()->create();
    $subtotal = 150000;

    $result = (new ProgressiveDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-15000)
        ->and($adjustment->label)->toBe('Desconto progressivo (10%)')
        ->and($result->total())->toBe(135000);
});

it('rounds the discount to the nearest cent', function () {
    $contract = Contract::factory()->create();
    $subtotal = 50010;

    $result = (new ProgressiveDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-2501)
        ->and($adjustment->label)->toBe('Desconto progressivo (5%)')
        ->and($result->total())->toBe(47509);
});

it('respects custom tiers', function () {
    $contract = Contract::factory()->create();
    $subtotal = 2000;

    $rule = new ProgressiveDiscountRule(tiers: [
        ['threshold' => 1000, 'discount_percentage' => 20],
    ]);

    $result = $rule->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: []
    ));

    $adjustment = $result->adjustments[0];

    expect($result->adjustments)->toHaveCount(1)
        ->and($adjustment->amount)->toBe(-400)
        ->and($adjustment->label)->toBe('Desconto progressivo (20%)')
        ->and($result->total())->toBe(1600);
});

it('preserves existing adjustments when applying a tier', function () {
    $contract = Contract::factory()->create();
    $subtotal = 60000;

    $existing = new Adjustment(
        label: 'Ajuste anterior',
        amount: -1000
    );

    $result = (new ProgressiveDiscountRule)->apply($contract, new PricingResult(
        subtotal: $subtotal,
        adjustments: [$existing]
    ));

    expect($result->adjustments)->toHaveCount(2)
        ->and($result->adjustments[0])->toBe($existing)
        ->and($result->adjustments[1]->amount)->toBe(-3000)
        ->and($result->total())->toBe(56000);
});
