<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(Request $request): Response
    {
        $categories = Category::query()
            ->withCount(['products', 'serviceCatalogs'])
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('master/categories/index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'type']),
            'types' => Category::TYPES,
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): Response
    {
        return Inertia::render('master/categories/form', [
            'category' => null,
            'types' => Category::TYPES,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        Category::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori berhasil dibuat.')]);

        return redirect()->route('master.categories.index');
    }

    /**
     * Show the form for editing the category.
     */
    public function edit(Category $category): Response
    {
        return Inertia::render('master/categories/form', [
            'category' => $category,
            'types' => Category::TYPES,
        ]);
    }

    /**
     * Update the category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        $category->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori berhasil diperbarui.')]);

        return redirect()->route('master.categories.index');
    }

    /**
     * Remove the category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Kategori berhasil dihapus.')]);

        return back();
    }
}
