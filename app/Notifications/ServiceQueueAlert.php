<?php

namespace App\Notifications;

use App\Contracts\HasDatabasePayload;
use App\Models\ServiceOrder;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ServiceQueueAlert extends Notification implements HasDatabasePayload
{
    /**
     * @param  Collection<int, ServiceOrder>  $services
     */
    public function __construct(public Collection $services) {}

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
            'title' => 'Antrian servis',
            'message' => $this->services->count().' servis menunggu penanganan: '.$this->services
                ->take(3)
                ->map(fn ($service) => $service->invoice_number)
                ->implode(', ')
                .($this->services->count() > 3 ? '…' : ''),
            'action_url' => route('services.index'),
            'services' => $this->services
                ->map(fn ($service) => [
                    'id' => $service->id,
                    'invoice_number' => $service->invoice_number,
                    'item_name' => $service->item_name,
                    'status' => $service->status,
                ])
                ->all(),
        ];
    }
}
