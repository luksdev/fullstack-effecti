<?php

namespace App\Domain\Pricing\Rules;

use App\Domain\Pricing\Contracts\PricingRule;
use App\Domain\Pricing\DTOs\Adjustment;
use App\Domain\Pricing\DTOs\PricingResult;
use App\Models\Contract;

class QuantityDiscountRule implements PricingRule
{
    public function __construct(
        private int $minQuantity = 3,
        private int $discountPercentage = 5
    ) {}

    public function apply(Contract $contract, PricingResult $result): PricingResult
    {
        $totalQuantity = $contract->contractItems->sum('quantity');

        if ($totalQuantity < $this->minQuantity) {
            return $result;
        }

        $discount = (int) round($result->subtotal * ($this->discountPercentage / 100));

        $adjustment = new Adjustment(
            label: "Desconto por quantidade ({$this->discountPercentage}%)",
            amount: -$discount
        );

        return new PricingResult(
            subtotal: $result->subtotal,
            adjustments: [...$result->adjustments, $adjustment]
        );
    }
}
