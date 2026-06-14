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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
        ];

        $contracts = Contract::query()
            ->with(['customer', 'contractItems.service'])
            ->when($filters['search'], fn (Builder $query, string $search) => $query->whereHas(
                'customer',
                fn (Builder $customer) => $customer->whereRaw('lower(name) like ?', ['%'.mb_strtolower($search).'%'])
            ))
            ->when(
                ContractStatus::tryFrom($filters['status']),
                fn (Builder $query, ContractStatus $status) => $query->where('status', $status)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('contracts/Index', [
            'contracts' => ContractResource::collection($contracts),
            'filters' => $filters,
            'statuses' => $this->statusOptions(),
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

        return to_route('contracts.edit', $contract)
            ->with('toast', ['type' => 'success', 'message' => 'Contrato criado. Adicione os itens abaixo.']);
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

        return to_route('contracts.index')
            ->with('toast', ['type' => 'success', 'message' => 'Contrato atualizado.']);
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return to_route('contracts.index')
            ->with('toast', ['type' => 'success', 'message' => 'Contrato excluído.']);
    }

    public function storeItem(StoreContractItemRequest $request, Contract $contract, ContractItemService $service): RedirectResponse
    {
        Gate::authorize('addItem', $contract);

        $service->addItem($contract, $request->validated());

        return to_route('contracts.edit', $contract)
            ->with('toast', ['type' => 'success', 'message' => 'Item adicionado ao contrato.']);
    }

    public function destroyItem(Contract $contract, ContractItem $item): RedirectResponse
    {
        Gate::authorize('removeItem', $contract);

        abort_unless($item->contract_id === $contract->id, 404);

        $item->delete();

        return to_route('contracts.edit', $contract)
            ->with('toast', ['type' => 'success', 'message' => 'Item removido do contrato.']);
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
