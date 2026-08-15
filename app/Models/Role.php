<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Role names that gate route access (see the `role` middleware).
     */
    public const BUILT_IN = ['admin', 'owner', 'cashier', 'technician'];

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'guard_name', 'label'];

    /**
     * @return MorphToMany<User, $this, MorphPivot>
     */
    public function users(): MorphToMany
    {
        return $this->morphToMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            'role_id',
            config('permission.column_names.model_morph_key'),
            null,
            null,
            null,
            true,
        );
    }
}
