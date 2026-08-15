<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nota {{ $sale->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111; padding: 24px; }
        .center { text-align: center; }
        .header { margin-bottom: 14px; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        .header h1 { font-size: 20px; letter-spacing: 1px; color: #4f46e5; }
        .header p { font-size: 10px; color: #555; margin-top: 3px; letter-spacing: 3px; text-transform: uppercase; }
        .meta { margin-bottom: 12px; }
        .meta table { width: 100%; }
        .meta td { padding: 2px 0; font-size: 10px; }
        .meta .label { color: #64748b; width: 120px; text-transform: uppercase; font-size: 9px; letter-spacing: 0.4px; }
        .meta .value { font-weight: 600; }
        table.items { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
        table.items th { text-align: left; font-size: 9px; text-transform: uppercase; color: #4f46e5; border-bottom: 2px solid #4f46e5; padding: 5px 0; }
        table.items td { padding: 5px 0; border-bottom: 1px dotted #d0d5dd; vertical-align: top; }
        .right { text-align: right; }
        table.totals { width: 100%; margin-top: 8px; }
        table.totals td { padding: 3px 0; }
        table.totals tr.total td { font-size: 13px; font-weight: bold; color: #4f46e5; border-top: 2px solid #4f46e5; padding-top: 6px; }
        .payment { margin-top: 14px; }
        .payment td { padding: 3px 0; }
        .payment .label { color: #64748b; text-transform: uppercase; font-size: 9px; letter-spacing: 0.4px; }
        .footer { margin-top: 26px; text-align: center; font-size: 9px; color: #64748b; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="header center">
        <h1>{{ config('app.name') }}</h1>
        <p>{{ __('NOTA PENJUALAN') }}</p>
    </div>

    <div class="meta">
        <table>
            <tr><td class="label">No. Nota</td><td class="value">{{ $sale->invoice_number }}</td></tr>
            <tr><td class="label">Tanggal</td><td class="value">{{ $sale->created_at->translatedFormat('d F Y H:i') }}</td></tr>
            <tr><td class="label">Kasir</td><td class="value">{{ $sale->cashier?->name }}</td></tr>
            <tr><td class="label">Pelanggan</td><td class="value">{{ $sale->customer?->name ?? '-' }}</td></tr>
            <tr><td class="label">Jenis</td><td class="value">{{ $sale->type === 'service' ? 'Servis' : 'Penjualan Retail' }}</td></tr>
            @if ($sale->serviceOrder)
                <tr><td class="label">No. Servis</td><td class="value">{{ $sale->serviceOrder->invoice_number }}</td></tr>
            @endif
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Harga</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</td></tr>
        <tr><td>Diskon</td><td class="right">Rp {{ number_format($sale->discount, 0, ',', '.') }}</td></tr>
        <tr class="total"><td>TOTAL</td><td class="right">Rp {{ number_format($sale->total, 0, ',', '.') }}</td></tr>
    </table>

    <table class="payment">
        <tr><td class="label">Metode</td><td class="right value">{{ $paymentMethodLabels[$sale->payment_method] ?? $sale->payment_method }}</td></tr>
        <tr><td class="label">Status</td><td class="right value">{{ $paymentStatusLabels[$sale->payment_status] ?? $sale->payment_status }}</td></tr>
        <tr><td class="label">Dibayar</td><td class="right value">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Kembalian</td><td class="right value">Rp {{ number_format($sale->changeAmount(), 0, ',', '.') }}</td></tr>
    </table>

    <div class="footer">
        Terima kasih atas kunjungan Anda.<br>
        Barang yang sudah dibeli tidak dapat dikembalikan kecuali ada kesepakatan.
    </div>
</body>
</html>
