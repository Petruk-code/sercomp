<?php

namespace App\Notifications;

use App\Contracts\HasDatabasePayload;
use App\Models\ServiceOrder;
use Illuminate\Notifications\Notification;

class ServiceReadyForPickupAlert extends Notification implements HasDatabasePayload
{
    public function __construct(public ServiceOrder $service) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Servis siap diambil',
            'message' => $this->service->invoice_number.' — '.$this->service->item_name.' sudah selesai, siap diambil pelanggan.',
            'service' => [
                'id' => $this->service->id,
                'invoice_number' => $this->service->invoice_number,
                'item_name' => $this->service->item_name,
                'customer_name' => $this->service->customer->name ?? null,
            ],
            'action_url' => route('services.show', $this->service),
        ];
    }
}
