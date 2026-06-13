<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Requests\Contract\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ContractController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $contracts = Contract::query()
            ->with(['customer', 'contractItems.service'])
            ->latest()
            ->paginate(15);

        return ContractResource::collection($contracts);
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        $contract = Contract::create($request->validated());

        return (new ContractResource($contract->load(['customer', 'contractItems.service'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Contract $contract): ContractResource
    {
        return new ContractResource($contract->load(['customer', 'contractItems.service']));
    }

    public function update(UpdateContractRequest $request, Contract $contract): ContractResource
    {
        Gate::authorize('update', $contract);

        $contract->update($request->validated());

        return new ContractResource($contract->load(['customer', 'contractItems.service']));
    }

    public function destroy(Contract $contract): Response
    {
        $contract->delete();

        return response()->noContent();
    }
}
