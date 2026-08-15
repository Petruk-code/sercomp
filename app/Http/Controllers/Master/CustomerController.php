<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Support\CsvImporter;
use App\Support\CsvStream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Export the filtered customers as a CSV download.
     */
    public function export(Request $request): StreamedResponse
    {
        $customers = Customer::query()
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->get();

        return CsvStream::export('pelanggan.csv', [
            'Nama', 'Telepon', 'Email', 'Alamat', 'Catatan',
        ], $customers->map(fn (Customer $customer): array => [
            $customer->name,
            $customer->phone,
            $customer->email,
            $customer->address,
            $customer->notes,
        ]));
    }

    /**
     * Import customers from an uploaded CSV, upserting by phone, then email,
     * then name.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = CsvImporter::run(
            $request->file('file')->getRealPath(),
            Customer::class,
            fn (array $row, int $rowNumber): array => $this->normalizeCustomerRow($row, $rowNumber),
            fn (array $data): ?Customer => $this->findCustomerForImport($data),
        );

        Inertia::flash('toast', [
            'type' => $result['failed'] > 0 ? 'warning' : 'success',
            'message' => CsvImporter::summaryMessage($result),
        ]);

        return back();
    }

    /**
     * Normalise and validate a single customer import row.
     *
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>
     */
    private function normalizeCustomerRow(array $row, int $rowNumber): array
    {
        $name = trim((string) ($row['nama'] ?? $row['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Nama pelanggan wajib diisi.');
        }

        return [
            'name' => $name,
            'phone' => CsvImporter::nullableCell($row['telepon'] ?? $row['phone'] ?? null),
            'email' => CsvImporter::nullableCell($row['email'] ?? null),
            'address' => CsvImporter::nullableCell($row['alamat'] ?? $row['address'] ?? null),
            'notes' => CsvImporter::nullableCell($row['catatan'] ?? $row['notes'] ?? null),
        ];
    }

    /**
     * Locate an existing customer by phone, then email, then name.
     *
     * @param  array<string, mixed>  $data
     */
    private function findCustomerForImport(array $data): ?Customer
    {
        if ($data['phone'] !== null) {
            return Customer::query()->where('phone', $data['phone'])->first();
        }

        if ($data['email'] !== null) {
            return Customer::query()->where('email', $data['email'])->first();
        }

        return Customer::query()->where('name', $data['name'])->first();
    }
}
