<?php

namespace App\Http\Controllers;

use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
        ];

        $services = Service::query()
            ->when(
                $filters['search'],
                fn (Builder $query, string $search) => $query->whereRaw('lower(name) like ?', ['%'.mb_strtolower($search).'%'])
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('services/Index', [
            'services' => ServiceResource::collection($services),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('services/Form', [
            'service' => null,
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        Service::create($request->validated());

        return to_route('services.index')
            ->with('toast', ['type' => 'success', 'message' => 'Serviço criado.']);
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('services/Form', [
            'service' => new ServiceResource($service),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return to_route('services.index')
            ->with('toast', ['type' => 'success', 'message' => 'Serviço atualizado.']);
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return to_route('services.index')
            ->with('toast', ['type' => 'success', 'message' => 'Serviço excluído.']);
    }
}
