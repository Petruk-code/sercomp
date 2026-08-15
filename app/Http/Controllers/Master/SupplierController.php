<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Display a listing of the suppliers.
     */
    public function index(Request $request): Response
    {
        $suppliers = Supplier::query()
            ->withCount('products')
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('master/suppliers/index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create(): Response
    {
        return Inertia::render('master/suppliers/form', [
            'supplier' => null,
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Supplier::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier berhasil dibuat.')]);

        return redirect()->route('master.suppliers.index');
    }

    /**
     * Show the form for editing the supplier.
     */
    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('master/suppliers/form', [
            'supplier' => $supplier,
        ]);
    }

    /**
     * Update the supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier berhasil diperbarui.')]);

        return redirect()->route('master.suppliers.index');
    }

    /**
     * Remove the supplier.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier berhasil dihapus.')]);

        return back();
    }
}
