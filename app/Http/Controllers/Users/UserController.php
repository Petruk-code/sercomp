<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('roles:id,name')
            ->when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('users/index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(['id', 'name', 'label']),
            'stores' => Store::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('users/form', [
            'user' => null,
            'currentRole' => null,
            'roles' => Role::query()->orderBy('name')->get(['id', 'name', 'label']),
            'stores' => Store::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'store_id' => $request->input('store_id'),
        ]);

        $user->syncRoles([$request->input('role')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pengguna '.$user->name.' berhasil dibuat.')]);

        return redirect()->route('users.index');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $user->load('roles:id,name');

        return Inertia::render('users/form', [
            'user' => $user->only(['id', 'name', 'email', 'store_id']),
            'currentRole' => $user->getRoleNames()->first(),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name', 'label']),
            'stores' => Store::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $role = $request->input('role');

        if ($this->isLastAdminBeingDemoted($user, $role)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Minimal satu pengguna dengan role admin harus ada.')]);

            return back();
        }

        $user->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->filled('password') ? $request->input('password') : $user->password,
            'store_id' => $request->input('store_id'),
        ]);

        $user->syncRoles([$role]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pengguna '.$user->name.' berhasil diperbarui.')]);

        return redirect()->route('users.index');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Anda tidak dapat menghapus akun sendiri.')]);

            return back();
        }

        if ($this->isLastAdminBeingDemoted($user, null)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Minimal satu pengguna dengan role admin harus ada.')]);

            return back();
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pengguna berhasil dihapus.')]);

        return back();
    }

    /**
     * Update a user's role.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = $request->input('role');

        if ($this->isLastAdminBeingDemoted($user, $role)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Minimal satu pengguna dengan role admin harus ada.')]);

            return back();
        }

        $user->syncRoles([$role]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role '.$user->name.' berhasil diperbarui.')]);

        return back();
    }

    /**
     * Determine whether removing the given user (or changing its role)
     * would leave the application without an admin.
     */
    protected function isLastAdminBeingDemoted(User $user, ?string $newRole): bool
    {
        if (! $user->hasRole('admin')) {
            return false;
        }

        if ($newRole === 'admin') {
            return false;
        }

        return User::query()->role('admin')->whereKeyNot($user->id)->doesntExist();
    }
}
