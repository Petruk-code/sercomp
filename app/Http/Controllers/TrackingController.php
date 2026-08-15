<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class TrackingController extends Controller
{
    /**
     * Public service order tracking page.
     */
    public function __invoke(Request $request): Response
    {
        $token = $request->string('token')->trim()->toString();
        $query = $request->string('no')->trim()->toString();

        $order = null;
        $timeline = [];

        if ($token !== '') {
            $order = ServiceOrder::query()
                ->where('tracking_token', $token)
                ->first();
        } elseif ($query !== '') {
            $order = ServiceOrder::query()
                ->whereRaw('LOWER(invoice_number) = ?', [mb_strtolower($query)])
                ->first();
        }

        if ($order) {
            $timeline = $this->buildTimeline($order);
        }

        return Inertia::render('tracking/show', [
            'query' => $query,
            'cardUrl' => $order ? $this->cardUrl($order) : null,
            'order' => $order?->only([
                'invoice_number',
                'item_name',
                'serial_number',
                'complaint',
                'status',
                'estimated_completion_at',
                'created_at',
            ]),
            'timeline' => $timeline,
        ]);
    }

    /**
     * Download a portable tracking card PDF (QR + token) for the order.
     */
    public function card(Request $request): HttpResponse
    {
        $token = $request->string('token')->trim()->toString();

        $order = ServiceOrder::query()
            ->where('tracking_token', $token)
            ->first();

        abort_if($order === null, 404);

        $order->load(['customer', 'receiver:id,name', 'technician:id,name']);

        $trackingUrl = route('tracking', ['token' => $order->tracking_token]);

        $pdf = Pdf::loadView('pdf.tracking_card', [
            'order' => $order,
            'statusLabels' => ServiceOrder::STATUS_LABELS,
            'trackingUrl' => $trackingUrl,
            'qrDataUri' => $this->qrDataUri($trackingUrl),
        ])->setPaper([0, 0, 242.65, 153.07]);

        return $pdf->download('kartu-lacak-'.$order->invoice_number.'.pdf');
    }

    /**
     * Build the absolute URL of the tracking card download for an order.
     */
    private function cardUrl(ServiceOrder $order): string
    {
        return route('tracking.card', ['token' => $order->tracking_token]);
    }

    /**
     * Render a QR code as a base64 PNG data URI.
     */
    private function qrDataUri(string $url): string
    {
        return (new Builder(
            writer: new PngWriter,
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 220,
            margin: 10,
        ))->build()->getDataUri();
    }

    /**
     * Build the status timeline, falling back to the current status for
     * orders created before status history was tracked.
     *
     * @return array<int, array{status: string, label: string, created_at: string|null}>
     */
    private function buildTimeline(ServiceOrder $order): array
    {
        $histories = $order->statusHistories()->get();

        $entries = $histories->isNotEmpty()
            ? $histories
            : collect([$order]);

        return $entries
            ->map(fn ($entry): array => [
                'status' => $entry->status,
                'label' => ServiceOrder::STATUS_LABELS[$entry->status] ?? $entry->status,
                'created_at' => $entry->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }
}
