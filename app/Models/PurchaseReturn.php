<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PurchaseReturnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $return_number
 * @property int $purchase_id
 * @property int $user_id
 * @property CarbonImmutable $return_date
 * @property string $result_type
 * @property string|null $reason
 * @property string $total
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'return_number',
    'purchase_id',
    'user_id',
    'return_date',
    'result_type',
    'reason',
    'total',
    'notes',
])]
class PurchaseReturn extends Model
{
    /** @use HasFactory<PurchaseReturnFactory> */
    use HasFactory;

    public const RESULT_TYPES = ['replacement', 'refund', 'debt_deduction'];

    public const RESULT_TYPE_LABELS = [
        'replacement' => 'Ganti Barang',
        'refund' => 'Refund Uang',
        'debt_deduction' => 'Potong Hutang',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Purchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<PurchaseReturnItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
