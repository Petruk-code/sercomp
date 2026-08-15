<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(Request $request): Response
    {
        $roles = Role::query()
            ->withCount('users')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('roles/index', [
            'roles' => $roles,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): Response
    {
        return Inertia::render('roles/form', [
            'role' => null,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Role::query()->create([
            'name' => $request->input('name'),
            'guard_name' => 'web',
            'label' => $request->input('label'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role berhasil dibuat.')]);

        return redirect()->route('roles.index');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): Response
    {
        return Inertia::render('roles/form', [
            'role' => $role->only(['id', 'name', 'label']),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'name' => in_array($role->name, Role::BUILT_IN) ? $role->name : $request->input('name'),
            'label' => $request->input('label'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role berhasil diperbarui.')]);

        return redirect()->route('roles.index');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, Role::BUILT_IN)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Role bawaan tidak dapat dihapus.')]);

            return back();
        }

        if ($role->users()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Role masih dipakai pengguna, tidak dapat dihapus.')]);

            return back();
        }

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role berhasil dihapus.')]);

        return back();
    }
}
