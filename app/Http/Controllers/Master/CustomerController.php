<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\ServiceOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     */
    public function index(Request $request): Response
    {
        $customers = Customer::query()
            ->withCount(['serviceOrders', 'sales'])
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('master/customers/index', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): Response
    {
        return Inertia::render('master/customers/form', [
            'customer' => null,
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pelanggan berhasil dibuat.')]);

        return redirect()->route('master.customers.index');
    }

    /**
     * Show the customer profile with their service & purchase history.
     */
    public function show(Request $request, Customer $customer): Response
    {
        $customer->loadCount(['serviceOrders', 'sales']);

        return Inertia::render('master/customers/show', [
            'customer' => $customer,
            'serviceOrders' => $customer->serviceOrders()
                ->with(['technician:id,name', 'sale:id,payment_status'])
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString(),
            'totalPaid' => (float) $customer->sales()->where('payment_status', 'paid')->sum('total'),
            'lastServiceAt' => $customer->serviceOrders()->latest()->value('created_at'),
            'statusLabels' => ServiceOrder::STATUS_LABELS,
        ]);
    }

    /**
     * Show the form for editing the customer.
     */
    public function edit(Customer $customer): Response
    {
        return Inertia::render('master/customers/form', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update the customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pelanggan berhasil diperbarui.')]);

        return redirect()->route('master.customers.index');
    }

    /**
     * Remove the customer.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pelanggan berhasil dihapus.')]);

        return back();
    }
}
