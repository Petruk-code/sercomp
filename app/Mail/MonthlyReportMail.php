<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{store: string, count: int, total: float}>  $salesPerStore
     * @param  array<int, array{supplier: string, due_date: string, outstanding: float}>  $overdueDebts
     * @param  array<int, array{name: string, store: string, stock: int, min_stock: int}>  $lowStockProducts
     */
    public function __construct(
        public string $periodLabel,
        public float $salesTotal,
        public int $salesCount,
        public array $salesPerStore,
        public int $servicesIn,
        public int $servicesDone,
        public array $overdueDebts,
        public float $overdueDebtsTotal,
        public array $lowStockProducts,
        public int $lowStockCount,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Laporan Bulanan :app — :period', ['app' => config('app.name'), 'period' => $this->periodLabel]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.monthly-report',
        );
    }
}
