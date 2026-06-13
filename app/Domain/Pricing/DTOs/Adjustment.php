<?php

namespace App\Domain\Pricing\DTOs;

class Adjustment
{
    public function __construct(
        public readonly string $label,
        public readonly int $amount,
    ) {}
}
