<?php

namespace App\Domain\Pricing\Rules;

use App\Domain\Pricing\Contracts\PricingRule;
use App\Domain\Pricing\DTOs\Adjustment;
use App\Domain\Pricing\DTOs\PricingResult;
use App\Models\Contract;

class ProgressiveDiscountRule implements PricingRule
{
    public function __construct(
        private array $tiers = [
            ['threshold' => 100000, 'discount_percentage' => 10],
            ['threshold' => 50000, 'discount_percentage' => 5],
        ]
    ) {}

    public function apply(Contract $contract, PricingResult $result): PricingResult
    {
        $orderedTiers = collect($this->tiers)->sortByDesc('threshold');

        foreach ($orderedTiers as $tier) {
            if ($result->subtotal >= $tier['threshold']) {
                $discount = (int) round($result->subtotal * ($tier['discount_percentage'] / 100));

                $adjustment = new Adjustment(
                    label: "Desconto progressivo ({$tier['discount_percentage']}%)",
                    amount: -$discount
                );

                return new PricingResult(
                    subtotal: $result->subtotal,
                    adjustments: [...$result->adjustments, $adjustment]
                );
            }
        }

        return $result;
    }
}
