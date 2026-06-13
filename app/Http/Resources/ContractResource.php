<?php

namespace App\Http\Resources;

use App\Domain\Pricing\ContractPricingService;
use App\Domain\Pricing\DTOs\Adjustment;
use App\Models\Contract;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contract
 */
class ContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pricing = app(ContractPricingService::class)->calculate($this->resource);

        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'items' => ContractItemResource::collection($this->whenLoaded('contractItems')),
            'pricing' => [
                'subtotal_cents' => $pricing->subtotal,
                'subtotal' => Money::toReais($pricing->subtotal),
                'adjustments' => array_map(fn (Adjustment $adjustment) => [
                    'label' => $adjustment->label,
                    'amount_cents' => $adjustment->amount,
                    'amount' => Money::toReais($adjustment->amount),
                ], $pricing->adjustments),
                'total_cents' => $pricing->total(),
                'total' => Money::toReais($pricing->total()),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
