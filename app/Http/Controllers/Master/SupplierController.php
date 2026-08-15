<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Support\CsvImporter;
use App\Support\CsvStream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Export the filtered suppliers as a CSV download.
     */
    public function export(Request $request): StreamedResponse
    {
        $suppliers = Supplier::query()
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->get();

        return CsvStream::export('supplier.csv', [
            'Nama', 'Kontak Person', 'Telepon', 'Email', 'Alamat', 'Catatan',
        ], $suppliers->map(fn (Supplier $supplier): array => [
            $supplier->name,
            $supplier->contact_person,
            $supplier->phone,
            $supplier->email,
            $supplier->address,
            $supplier->notes,
        ]));
    }

    /**
     * Import suppliers from an uploaded CSV, upserting by name, then phone,
     * then email.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = CsvImporter::run(
            $request->file('file')->getRealPath(),
            Supplier::class,
            fn (array $row, int $rowNumber): array => $this->normalizeSupplierRow($row, $rowNumber),
            fn (array $data): ?Supplier => $this->findSupplierForImport($data),
        );

        Inertia::flash('toast', [
            'type' => $result['failed'] > 0 ? 'warning' : 'success',
            'message' => CsvImporter::summaryMessage($result),
        ]);

        return back();
    }

    /**
     * Normalise and validate a single supplier import row.
     *
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>
     */
    private function normalizeSupplierRow(array $row, int $rowNumber): array
    {
        $name = trim((string) ($row['nama'] ?? $row['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Nama supplier wajib diisi.');
        }

        return [
            'name' => $name,
            'contact_person' => CsvImporter::nullableCell($row['kontak_person'] ?? $row['contact_person'] ?? null),
            'phone' => CsvImporter::nullableCell($row['telepon'] ?? $row['phone'] ?? null),
            'email' => CsvImporter::nullableCell($row['email'] ?? null),
            'address' => CsvImporter::nullableCell($row['alamat'] ?? $row['address'] ?? null),
            'notes' => CsvImporter::nullableCell($row['catatan'] ?? $row['notes'] ?? null),
        ];
    }

    /**
     * Locate an existing supplier by name, then phone, then email.
     *
     * @param  array<string, mixed>  $data
     */
    private function findSupplierForImport(array $data): ?Supplier
    {
        if ($data['name'] !== null) {
            return Supplier::query()->where('name', $data['name'])->first();
        }

        if ($data['phone'] !== null) {
            return Supplier::query()->where('phone', $data['phone'])->first();
        }

        return Supplier::query()->where('email', $data['email'])->first();
    }
}
