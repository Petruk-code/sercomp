<?php

namespace App\Support;

use App\Models\ServiceOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WhatsApp
{
    /**
     * Endpoint Fonnte untuk mengirim pesan WhatsApp.
     */
    private const ENDPOINT = 'https://api.fonnte.com/send';

    /**
     * Build a wa.me deep link for the given phone number and message.
     */
    public static function link(string $phone, string $message): string
    {
        return 'https://wa.me/'.self::normalize($phone).'?text='.rawurlencode($message);
    }

    /**
     * Send a WhatsApp text message through the Fonnte gateway.
     *
     * @return array<string, mixed>
     */
    public static function send(string $phone, string $message): array
    {
        $target = self::normalize($phone);

        $response = Http::asForm()
            ->withHeaders(['Authorization' => config('services.fonnte.api_key')])
            ->post(self::ENDPOINT, [
                'target' => $target,
                'message' => $message,
                'countryCode' => config('services.fonnte.country_code', '62'),
            ]);

        $payload = $response->json() ?? [];

        if ($response->failed() || ($payload['status'] ?? false) !== true) {
            Log::warning('Fonnte send failed', [
                'target' => $target,
                'status' => $response->status(),
                'response' => $payload,
            ]);
        }

        return $payload;
    }

    /**
     * Whether the Fonnte gateway is configured.
     */
    public static function isConfigured(): bool
    {
        return filled(config('services.fonnte.api_key'));
    }

    /**
     * Mask a phone number, keeping only the last four digits visible.
     */
    public static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) <= 4) {
            return $digits;
        }

        return '•••• '.substr($digits, -4);
    }

    /**
     * Normalize an Indonesian phone number to the international (wa.me) format.
     *
     * Handles "0812...", "+62 812...", "62812...", and "812..." variants.
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return '62'.$digits;
    }

    /**
     * Message used to share a service tracking link with a customer.
     */
    public static function trackingMessage(string $customerName, string $invoiceNumber, string $trackingUrl): string
    {
        $greeting = $customerName !== '' ? "Halo {$customerName}, " : '';

        return $greeting."unit Anda dengan nomor servis *{$invoiceNumber}* sedang kami kerjakan. Pantau status pengerjaannya di: {$trackingUrl}";
    }

    /**
     * Absolute tracking URL for the given service order.
     */
    public static function trackingUrl(ServiceOrder $order): string
    {
        return route('tracking', ['token' => $order->tracking_token]);
    }

    /**
     * Full tracking message for a service order's customer.
     */
    public static function trackingMessageFor(ServiceOrder $order): string
    {
        return self::trackingMessage(
            $order->customer->name ?? '',
            $order->invoice_number,
            self::trackingUrl($order),
        );
    }
}
