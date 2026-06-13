<?php

namespace App\Http\Resources;

use App\Models\Service;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_price_cents' => $this->base_price,
            'base_price' => Money::toReais($this->base_price),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
