<?php

namespace App\Http\Controllers;

use App\Enums\CustomerStatus;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
        ];

        $customers = Customer::query()
            ->when($filters['search'], function (Builder $query, string $search): void {
                $term = '%'.mb_strtolower($search).'%';
                $digits = preg_replace('/\D/', '', $search);

                $query->where(function (Builder $inner) use ($term, $digits): void {
                    $inner->whereRaw('lower(name) like ?', [$term])
                        ->orWhereRaw('lower(email) like ?', [$term]);

                    if ($digits !== '') {
                        $inner->orWhere('federal_document', 'like', "%{$digits}%");
                    }
                });
            })
            ->when(
                CustomerStatus::tryFrom($filters['status']),
                fn (Builder $query, CustomerStatus $status) => $query->where('status', $status)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('customers/Index', [
            'customers' => CustomerResource::collection($customers),
            'filters' => $filters,
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('customers/Form', [
            'customer' => null,
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return to_route('customers.index')
            ->with('toast', ['type' => 'success', 'message' => 'Cliente criado.']);
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('customers/Form', [
            'customer' => new CustomerResource($customer),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return to_route('customers.index')
            ->with('toast', ['type' => 'success', 'message' => 'Cliente atualizado.']);
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return to_route('customers.index')
            ->with('toast', ['type' => 'success', 'message' => 'Cliente excluído.']);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (CustomerStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            CustomerStatus::cases(),
        );
    }
}
