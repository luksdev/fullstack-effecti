<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Service;

class ContractItemService
{
    /**
     * Add a service to a contract, freezing the unit price at the moment of addition.
     * When no unit_price is provided, the service's current base_price is used as the
     * default and stays fixed regardless of future changes to the Service.
     *
     * @param  array{service_id: string, quantity: int, unit_price?: int|null}  $data
     */
    public function addItem(Contract $contract, array $data): ContractItem
    {
        $unitPrice = $data['unit_price']
            ?? Service::query()->whereKey($data['service_id'])->value('base_price');

        return $contract->contractItems()->create([
            'service_id' => $data['service_id'],
            'quantity' => $data['quantity'],
            'unit_price' => $unitPrice,
        ]);
    }
}
