<?php

namespace App\Models;

use App\Concerns\BelongsToStore;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $invoice_number
 * @property string $type
 * @property int|null $service_order_id
 * @property int|null $customer_id
 * @property int $user_id
 * @property string $subtotal
 * @property string $discount
 * @property string $total
 * @property string $payment_method
 * @property string $payment_status
 * @property string $paid_amount
 * @property string|null $notes
 * @property string|null $client_reference
 * @property int|null $store_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'invoice_number',
    'type',
    'service_order_id',
    'customer_id',
    'user_id',
    'subtotal',
    'discount',
    'total',
    'payment_method',
    'payment_status',
    'paid_amount',
    'notes',
    'client_reference',
])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use BelongsToStore, HasFactory;

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
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ServiceOrder, $this>
     */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function changeAmount(): float
    {
        return max((float) $this->paid_amount - (float) $this->total, 0);
    }
}
