<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceCatalogRequest;
use App\Http\Requests\UpdateServiceCatalogRequest;
use App\Models\Category;
use App\Models\ServiceCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCatalogController extends Controller
{
    /**
     * Display a listing of the service catalog.
     */
    public function index(Request $request): Response
    {
        $services = ServiceCatalog::query()
            ->with('category:id,name')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('master/services/index', [
            'services' => $services,
            'categories' => Category::query()->where('type', 'service')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): Response
    {
        return Inertia::render('master/services/form', [
            'service' => null,
            'categories' => Category::query()->where('type', 'service')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created service.
     */
    public function store(StoreServiceCatalogRequest $request): RedirectResponse
    {
        ServiceCatalog::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Jasa servis berhasil dibuat.')]);

        return redirect()->route('master.services.index');
    }

    /**
     * Show the form for editing the service.
     */
    public function edit(ServiceCatalog $service): Response
    {
        return Inertia::render('master/services/form', [
            'service' => $service,
            'categories' => Category::query()->where('type', 'service')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the service.
     */
    public function update(UpdateServiceCatalogRequest $request, ServiceCatalog $service): RedirectResponse
    {
        $service->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Jasa servis berhasil diperbarui.')]);

        return redirect()->route('master.services.index');
    }

    /**
     * Remove the service.
     */
    public function destroy(ServiceCatalog $service): RedirectResponse
    {
        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Jasa servis berhasil dihapus.')]);

        return back();
    }
}
