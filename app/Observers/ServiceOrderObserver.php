<?php

namespace App\Observers;

use App\Models\ServiceOrder;
use App\Models\User;
use App\Notifications\ServiceReadyForPickupAlert;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ServiceOrderObserver
{
    /**
     * Default a new order to 'pending' and assign its tracking token.
     */
    public function creating(ServiceOrder $serviceOrder): void
    {
        if (blank($serviceOrder->status)) {
            $serviceOrder->status = 'pending';
        }

        if (blank($serviceOrder->tracking_token)) {
            $serviceOrder->tracking_token = (string) Str::uuid();
        }
    }

    /**
     * Record the initial status when a service order is created.
     */
    public function created(ServiceOrder $serviceOrder): void
    {
        $this->record($serviceOrder);
    }

    /**
     * Record a status change when the service order status updates.
     */
    public function updated(ServiceOrder $serviceOrder): void
    {
        if (! $serviceOrder->isDirty('status')) {
            return;
        }

        $this->record($serviceOrder);

        AuditLogger::record(
            'service.status_changed',
            __('Status servis :invoice: :old → :new.', [
                'invoice' => $serviceOrder->invoice_number,
                'old' => ServiceOrder::STATUS_LABELS[$serviceOrder->getOriginal('status')] ?? $serviceOrder->getOriginal('status'),
                'new' => $serviceOrder->statusLabel(),
            ]),
            $serviceOrder,
            ['status' => $serviceOrder->getOriginal('status')],
            ['status' => $serviceOrder->status],
        );

        if ($serviceOrder->status === 'done' && $serviceOrder->completed_at === null) {
            $serviceOrder->completed_at = now();
            $serviceOrder->recomputeWarrantyEndDate();
        }

        if ($serviceOrder->status === 'done') {
            $this->notifyReadyForPickup($serviceOrder);
        }
    }

    /**
     * Alert admin and owner when a service is ready for pickup.
     */
    private function notifyReadyForPickup(ServiceOrder $serviceOrder): void
    {
        $recipients = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'owner']))
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ServiceReadyForPickupAlert($serviceOrder));
        }
    }

    /**
     * Keep the warranty end date in sync when the warranty duration changes
     * on an order that has already been completed.
     */
    public function saved(ServiceOrder $serviceOrder): void
    {
        if ($serviceOrder->isDirty('warranty_days') && $serviceOrder->completed_at !== null) {
            $serviceOrder->recomputeWarrantyEndDate();
        }
    }

    /**
     * Write a single timeline entry for the given order's status.
     */
    private function record(ServiceOrder $serviceOrder): void
    {
        if (blank($serviceOrder->status)) {
            return;
        }

        $serviceOrder->statusHistories()->create([
            'status' => $serviceOrder->status,
            'user_id' => Auth::id(),
        ]);
    }
}
