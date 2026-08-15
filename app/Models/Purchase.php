<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $purchase_number
 * @property int|null $supplier_id
 * @property int $user_id
 * @property CarbonImmutable $purchase_date
 * @property CarbonImmutable|null $due_date
 * @property string $subtotal
 * @property string $discount
 * @property string $total
 * @property string $paid_amount
 * @property string $payment_status
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'purchase_number',
    'supplier_id',
    'user_id',
    'purchase_date',
    'due_date',
    'subtotal',
    'discount',
    'total',
    'paid_amount',
    'payment_status',
    'notes',
])]
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    public const PAYMENT_METHODS = ['cash', 'transfer', 'qris'];

    public const PAYMENT_STATUSES = ['paid', 'partial', 'unpaid'];

    public const PAYMENT_METHOD_LABELS = [
        'cash' => 'Tunai',
        'transfer' => 'Transfer',
        'qris' => 'QRIS',
    ];

    public const PAYMENT_STATUS_LABELS = [
        'paid' => 'Lunas',
        'partial' => 'Sebagian',
        'unpaid' => 'Belum Bayar',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<PurchaseItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * @return HasMany<PurchasePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    /**
     * @return HasMany<PurchaseReturn, $this>
     */
    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function balanceDue(): float
    {
        return max((float) $this->total - (float) $this->paid_amount, 0);
    }

    public function isOverdue(): bool
    {
        return $this->payment_status !== 'paid'
            && $this->due_date !== null
            && $this->due_date->endOfDay()->isPast();
    }
}
