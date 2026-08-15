<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Support\CsvImporter;
use App\Support\CsvStream;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->with(['category:id,name', 'supplier:id,name'])
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            }))
            ->when($request->category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->low_stock, fn ($query) => $query->whereColumn('stock', '<=', 'min_stock')->where('min_stock', '>', 0))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('master/products/index', [
            'products' => $products,
            'categories' => Category::query()->where('type', 'product')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'category_id', 'low_stock']),
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): Response
    {
        return Inertia::render('master/products/form', [
            'product' => null,
            'categories' => Category::query()->where('type', 'product')->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Produk berhasil dibuat.')]);

        return redirect()->route('master.products.index');
    }

    /**
     * Show the form for editing the product.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('master/products/form', [
            'product' => $product,
            'categories' => Category::query()->where('type', 'product')->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the product.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Produk berhasil diperbarui.')]);

        return redirect()->route('master.products.index');
    }

    /**
     * Remove the product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Produk berhasil dihapus.')]);

        return back();
    }

    /**
     * Generate a PDF of barcode labels for the selected products.
     */
    public function barcode(Request $request): HttpResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids')))->filter();

        $products = Product::query()
            ->whereIn('id', $ids->isEmpty() ? [-1] : $ids->all())
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('pdf.barcode', ['products' => $products])
            ->setPaper('a4');

        return $pdf->stream('barcode-produk.pdf');
    }

    /**
     * Export the filtered products as a CSV download.
     */
    public function export(Request $request): StreamedResponse
    {
        $products = Product::query()
            ->with('category:id,name')
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            }))
            ->when($request->category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->low_stock, fn ($query) => $query->whereColumn('stock', '<=', 'min_stock')->where('min_stock', '>', 0))
            ->orderByDesc('id')
            ->get();

        return CsvStream::export('produk.csv', [
            'SKU', 'Barcode', 'Nama', 'Kategori', 'Unit', 'Harga Beli', 'Harga Jual', 'Stok', 'Stok Minimum', 'Deskripsi',
        ], $products->map(fn (Product $product): array => [
            $product->sku,
            $product->barcode,
            $product->name,
            $product->category?->name,
            $product->unit,
            $product->purchase_price,
            $product->selling_price,
            $product->stock,
            $product->min_stock,
            $product->description,
        ]));
    }

    /**
     * Import products from an uploaded CSV, upserting by SKU, then barcode,
     * then name. Rows with invalid data are skipped and reported.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = CsvImporter::run(
            $request->file('file')->getRealPath(),
            Product::class,
            fn (array $row, int $rowNumber): array => $this->normalizeProductRow($row, $rowNumber),
            fn (array $data): ?Product => $this->findProductForImport($data),
        );

        Inertia::flash('toast', [
            'type' => $result['failed'] > 0 ? 'warning' : 'success',
            'message' => CsvImporter::summaryMessage($result),
        ]);

        return back();
    }

    /**
     * Normalise and validate a single product import row.
     *
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>
     */
    private function normalizeProductRow(array $row, int $rowNumber): array
    {
        $name = trim((string) ($row['nama'] ?? $row['name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Nama produk wajib diisi.');
        }

        $sku = trim((string) ($row['sku'] ?? ''));
        $barcode = trim((string) ($row['barcode'] ?? ''));

        $category = null;
        $categoryName = trim((string) ($row['kategori'] ?? $row['category'] ?? ''));

        if ($categoryName !== '') {
            $category = Category::query()
                ->where('type', 'product')
                ->where('name', $categoryName)
                ->first();

            if ($category === null) {
                $category = Category::query()->create([
                    'name' => $categoryName,
                    'slug' => (string) Str::slug($categoryName),
                    'type' => 'product',
                ]);
            }
        }

        return [
            'name' => $name,
            'sku' => $sku !== '' ? $sku : null,
            'barcode' => $barcode !== '' ? $barcode : null,
            'category_id' => $category?->id,
            'unit' => trim((string) ($row['unit'] ?? '')) !== '' ? trim((string) $row['unit']) : 'pcs',
            'purchase_price' => CsvImporter::parseDecimal($row['harga_beli'] ?? $row['purchase_price'] ?? 0, 'Harga beli'),
            'selling_price' => CsvImporter::parseDecimal($row['harga_jual'] ?? $row['selling_price'] ?? 0, 'Harga jual'),
            'stock' => CsvImporter::parseInteger($row['stok'] ?? $row['stock'] ?? 0, 'Stok'),
            'min_stock' => CsvImporter::parseInteger($row['stok_minimum'] ?? $row['min_stock'] ?? 0, 'Stok minimum'),
            'description' => trim((string) ($row['deskripsi'] ?? $row['description'] ?? '')),
        ];
    }

    /**
     * Locate an existing product by SKU, then barcode, then name.
     *
     * @param  array<string, mixed>  $data
     */
    private function findProductForImport(array $data): ?Product
    {
        if ($data['sku'] !== null) {
            return Product::query()->where('sku', $data['sku'])->first();
        }

        if ($data['barcode'] !== null) {
            return Product::query()->where('barcode', $data['barcode'])->first();
        }

        return Product::query()->where('name', $data['name'])->first();
    }
}
