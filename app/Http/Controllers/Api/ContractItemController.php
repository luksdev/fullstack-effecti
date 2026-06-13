<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\StoreContractItemRequest;
use App\Http\Resources\ContractItemResource;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Services\ContractItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ContractItemController extends Controller
{
    public function store(StoreContractItemRequest $request, Contract $contract, ContractItemService $service): JsonResponse
    {
        Gate::authorize('addItem', $contract);

        $item = $service->addItem($contract, $request->validated());

        return (new ContractItemResource($item->load('service')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Contract $contract, ContractItem $item): Response
    {
        Gate::authorize('removeItem', $contract);

        abort_unless($item->contract_id === $contract->id, 404);

        $item->delete();

        return response()->noContent();
    }
}
