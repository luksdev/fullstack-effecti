<?php

namespace App\Http\Resources;

use App\Models\ContractItem;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContractItem
 */
class ContractItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lineTotal = $this->quantity * $this->unit_price;

        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'service_name' => $this->whenLoaded('service', fn () => $this->service->name),
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price,
            'unit_price' => Money::toReais($this->unit_price),
            'line_total_cents' => $lineTotal,
            'line_total' => Money::toReais($lineTotal),
        ];
    }
}
