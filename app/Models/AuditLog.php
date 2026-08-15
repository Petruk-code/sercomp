<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property string $description
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $ip_address
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class AuditLog extends Model
{
    public const ACTION_LABELS = [
        'sale.created' => 'Penjualan dibuat',
        'sale.payment' => 'Pembayaran penjualan',
        'purchase.created' => 'Pembelian dibuat',
        'purchase.payment' => 'Pembayaran pembelian',
        'purchase.updated' => 'Pembelian diubah',
        'purchase.deleted' => 'Pembelian dihapus',
        'purchase_return.created' => 'Retur dibuat',
        'purchase_return.deleted' => 'Retur dihapus',
        'service.status_changed' => 'Status servis diubah',
        'product.stock_changed' => 'Stok produk diubah',
        'two_factor.enabled' => '2FA TOTP diaktifkan',
        'two_factor.disabled' => '2FA TOTP dinonaktifkan',
        'two_factor.confirmed' => '2FA TOTP dikonfirmasi',
        'two_factor.recovery_codes_generated' => 'Kode pemulihan 2FA dibuat',
        'two_factor.login' => 'Login via kode 2FA',
        'whatsapp_2fa.enabled' => 'WhatsApp 2FA diaktifkan',
        'whatsapp_2fa.disabled' => 'WhatsApp 2FA dinonaktifkan',
        'whatsapp_2fa.login' => 'Login via OTP WhatsApp',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }
}
