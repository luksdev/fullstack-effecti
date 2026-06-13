<?php

namespace App\Http\Controllers;

use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        $services = Service::query()
            ->latest()
            ->paginate(15);

        return Inertia::render('services/Index', [
            'services' => ServiceResource::collection($services),
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
