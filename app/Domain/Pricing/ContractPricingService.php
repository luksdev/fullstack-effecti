<?php

namespace App\Domain\Pricing;

use App\Domain\Pricing\Contracts\PricingRule;
use App\Domain\Pricing\DTOs\PricingResult;
use App\Models\ContractItem;
use App\Models\Contract;

class ContractPricingService
{
    /**
     * @param  iterable<PricingRule>  $pricingRules
     */
    public function __construct(
        private iterable $pricingRules,
    ) {}

    public function calculate(Contract $contract): PricingResult
    {
        $contract->loadMissing('contractItems');

        $subtotal = (int) $contract->contractItems->sum(
            fn (ContractItem$item) => $item->quantity * $item->unit_price
        );

        $result = new PricingResult(
            subtotal: $subtotal,
            adjustments: [],
        );

        foreach ($this->pricingRules as $rule) {
            $result = $rule->apply($contract, $result);
        }

        return $result;
    }
}
