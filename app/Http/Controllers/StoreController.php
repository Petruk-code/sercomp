<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Support\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    /**
     * List cabang.
     */
    public function index(): Response
    {
        return Inertia::render('stores/index', [
            'stores' => Store::query()
                ->withCount(['users', 'products'])
                ->orderBy('id')
                ->get(),
            'activeStoreId' => StoreContext::id(),
        ]);
    }

    /**
     * Form tambah cabang.
     */
    public function create(): Response
    {
        return Inertia::render('stores/form', [
            'store' => null,
        ]);
    }

    /**
     * Simpan cabang baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Store::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cabang '.$data['name'].' berhasil dibuat.')]);

        return redirect()->route('stores.index');
    }

    /**
     * Form edit cabang.
     */
    public function edit(Store $store): Response
    {
        return Inertia::render('stores/form', [
            'store' => $store->only(['id', 'name', 'address', 'phone', 'is_active']),
        ]);
    }

    /**
     * Perbarui cabang.
     */
    public function update(Request $request, Store $store): RedirectResponse
    {
        $store->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cabang '.$store->name.' berhasil diperbarui.')]);

        return redirect()->route('stores.index');
    }

    /**
     * Pindah cabang aktif untuk sesi ini (hanya user yang tidak terikat cabang).
     */
    public function switchActive(Request $request): RedirectResponse
    {
        abort_unless(StoreContext::canSwitch($request->user()), 403);

        $validated = $request->validate([
            'store_id' => ['required', 'integer', Rule::exists('stores', 'id')->where('is_active', true)],
        ]);

        StoreContext::set((int) $validated['store_id']);

        return back();
    }

    /**
     * @return array{name: string, address: string|null, phone: string|null, is_active: bool}
     */
    private function validated(Request $request): array
    {
        /** @var array{name: string, address?: string|null, phone?: string|null, is_active?: bool|string} $validated */
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:32'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => (string) $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            // Select form mengirim "1"/"0"; tanpa field (API) default aktif.
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }
}
