<?php

namespace App\Concerns;

use App\Models\Store;
use App\Support\StoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Multi-cabang (opsi A): model dengan trait ini otomatis
 * - ter-filter ke cabang aktif saat query (global scope), dan
 * - ter-isi store_id dari konteks saat dibuat.
 *
 * Konteks null (console/publik/belum pilih cabang) = tanpa scoping,
 * sehingga command terjadwal & halaman publik tetap melihat semua data.
 */
trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope('store', function (Builder $builder) {
            $storeId = StoreContext::id();

            if ($storeId !== null) {
                $builder->where($builder->getModel()->getTable().'.store_id', $storeId);
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('store_id') === null) {
                $model->setAttribute('store_id', StoreContext::id());
            }
        });
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
