<?php

namespace App\Domain\Pricing\DTOs;

class PricingResult
{
    public function __construct(
        public readonly int $subtotal,
        public readonly array $adjustments = [],
    ) {}

    public function total(): int
    {
        return $this->subtotal + array_sum(
            array_map(fn (Adjustment $adjustment) => $adjustment->amount, $this->adjustments)
        );
    }
}
