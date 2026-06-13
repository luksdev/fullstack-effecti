<?php

namespace App\Http\Controllers;

use App\Enums\ContractStatus;
use App\Http\Requests\Contract\StoreContractItemRequest;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Requests\Contract\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\ServiceResource;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Customer;
use App\Models\Service;
use App\Services\ContractItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function index(): Response
    {
        $contracts = Contract::query()
            ->with(['customer', 'contractItems.service'])
            ->latest()
            ->paginate(15);

        return Inertia::render('contracts/Index', [
            'contracts' => ContractResource::collection($contracts),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('contracts/Form', [
            'contract' => null,
            'customers' => CustomerResource::collection(Customer::orderBy('name')->get()),
            'services' => ServiceResource::collection(Service::orderBy('name')->get()),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        $contract = Contract::create($request->validated());

        return to_route('contracts.edit', $contract);
    }

    public function edit(Contract $contract): Response
    {
        $contract->load(['customer', 'contractItems.service']);

        return Inertia::render('contracts/Form', [
            'contract' => new ContractResource($contract),
            'customers' => CustomerResource::collection(Customer::orderBy('name')->get()),
            'services' => ServiceResource::collection(Service::orderBy('name')->get()),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        Gate::authorize('update', $contract);

        $contract->update($request->validated());

        return to_route('contracts.index');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return to_route('contracts.index');
    }

    public function storeItem(StoreContractItemRequest $request, Contract $contract, ContractItemService $service): RedirectResponse
    {
        Gate::authorize('addItem', $contract);

        $service->addItem($contract, $request->validated());

        return to_route('contracts.edit', $contract);
    }

    public function destroyItem(Contract $contract, ContractItem $item): RedirectResponse
    {
        Gate::authorize('removeItem', $contract);

        abort_unless($item->contract_id === $contract->id, 404);

        $item->delete();

        return to_route('contracts.edit', $contract);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (ContractStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            ContractStatus::cases(),
        );
    }
}
