<?php

namespace App\Models;

use App\Concerns\BelongsToStore;
use Carbon\CarbonImmutable;
use Database\Factories\ServiceOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $invoice_number
 * @property string $tracking_token
 * @property int|null $customer_id
 * @property int $user_id
 * @property int|null $technician_id
 * @property string $item_name
 * @property string|null $serial_number
 * @property string $complaint
 * @property string|null $physical_condition
 * @property string $status
 * @property string $estimated_cost
 * @property string $labor_cost
 * @property string $total_cost
 * @property string|null $notes
 * @property int|null $warranty_days
 * @property CarbonImmutable|null $warranty_end_date
 * @property string|null $warranty_notes
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $estimated_completion_at
 * @property int|null $store_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'invoice_number',
    'tracking_token',
    'customer_id',
    'user_id',
    'technician_id',
    'item_name',
    'serial_number',
    'complaint',
    'physical_condition',
    'status',
    'estimated_cost',
    'labor_cost',
    'total_cost',
    'notes',
    'warranty_days',
    'warranty_end_date',
    'warranty_notes',
    'completed_at',
    'estimated_completion_at',
])]
class ServiceOrder extends Model
{
    /** @use HasFactory<ServiceOrderFactory> */
    use BelongsToStore, HasFactory;

    public const STATUSES = ['pending', 'diagnosed', 'in_progress', 'done', 'taken', 'cancelled'];

    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'diagnosed' => 'Diagnosa',
        'in_progress' => 'Proses',
        'done' => 'Selesai',
        'taken' => 'Diambil',
        'cancelled' => 'Dibatalkan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'labor_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'warranty_days' => 'integer',
            'warranty_end_date' => 'date',
            'completed_at' => 'datetime',
            'estimated_completion_at' => 'datetime',
        ];
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
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * @return HasMany<ServiceOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }

    /**
     * @return HasOne<Sale, $this>
     */
    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    /**
     * Status changes recorded over time, oldest first.
     *
     * @return HasMany<ServiceOrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ServiceOrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function recalculateTotal(): void
    {
        $partsTotal = $this->items->sum('subtotal');

        $this->total_cost = $this->labor_cost + $partsTotal;
        $this->save();
    }

    /**
     * (Re)calculate the warranty end date from the completion date plus the
     * warranty duration. Persists quietly to avoid re-triggering model events.
     */
    public function recomputeWarrantyEndDate(): void
    {
        $days = $this->warranty_days;
        $base = $this->completed_at;

        $this->warranty_end_date = $days && $base
            ? $base->copy()->addDays($days)
            : null;

        $this->saveQuietly();
    }

    /**
     * Warranty lifecycle: none (no warranty), pending (set but service not yet
     * completed), active (covered), or expired.
     */
    public function warrantyStatus(): string
    {
        if (! $this->warranty_days) {
            return 'none';
        }

        if (! $this->warranty_end_date) {
            return 'pending';
        }

        return $this->warranty_end_date->endOfDay()->isPast() ? 'expired' : 'active';
    }
}
