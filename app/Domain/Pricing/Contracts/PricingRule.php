<?php

namespace App\Domain\Pricing\Contracts;

use App\Domain\Pricing\DTOs\PricingResult;
use App\Models\Contract;

interface PricingRule
{
    public function apply(Contract $contract, PricingResult $result): PricingResult;
}
