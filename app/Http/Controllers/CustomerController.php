<?php

namespace App\Http\Controllers;

use App\Enums\CustomerStatus;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $customers = Customer::query()
            ->latest()
            ->paginate(15);

        return Inertia::render('customers/Index', [
            'customers' => CustomerResource::collection($customers),
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

        return to_route('customers.index');
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

        return to_route('customers.index');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return to_route('customers.index');
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
