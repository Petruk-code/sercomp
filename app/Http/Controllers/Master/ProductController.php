<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

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
}
