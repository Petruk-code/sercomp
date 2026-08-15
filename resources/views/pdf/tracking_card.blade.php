<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Lacak {{ $order->invoice_number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #111; padding: 8pt 11pt; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 4px; margin-bottom: 6px; }
        .header h1 { font-size: 14px; letter-spacing: 1px; color: #4f46e5; }
        .header .sub { font-size: 6px; color: #64748b; letter-spacing: 2px; text-transform: uppercase; margin-top: 1px; }
        table.body { width: 100%; table-layout: fixed; }
        .info { padding-right: 9px; vertical-align: top; }
        .info .invoice { font-size: 10.5px; font-weight: bold; color: #111; }
        .info .item { font-size: 8px; color: #334155; margin-top: 3px; }
        .info .status { margin-top: 4px; }
        .info .status .pill { display: inline-block; border: 1px solid #4f46e5; color: #4f46e5; font-size: 6.5px; padding: 1px 6px; border-radius: 8px; }
        .info .hint { font-size: 6.5px; color: #64748b; margin-top: 6px; line-height: 1.4; }
        .qr-cell { width: 96px; text-align: center; vertical-align: middle; }
        .qr-cell img { width: 92px; height: 92px; }
        .qr-cell .url { font-size: 5.5px; color: #4f46e5; word-break: break-all; margin-top: 2px; line-height: 1.3; }
        .footer { border-top: 1px solid #e2e8f0; margin-top: 6px; padding-top: 3px; text-align: center; font-size: 6px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <div class="sub">{{ __('KARTU LACAK SERVIS') }}</div>
    </div>

    <table class="body">
        <tr>
            <td class="info">
                <div class="invoice">{{ $order->invoice_number }}</div>
                <div class="item">{{ $order->item_name }}</div>
                @if ($order->serial_number)
                    <div class="item">SN: {{ $order->serial_number }}</div>
                @endif
                <div class="status"><span class="pill">{{ $statusLabels[$order->status] ?? $order->status }}</span></div>
                <div class="hint">
                    Pindai QR atau buka tautan di samping untuk
                    memantau status servis Anda.
                </div>
            </td>
            <td class="qr-cell">
                <img src="{{ $qrDataUri }}" alt="QR Lacak Servis">
                <div class="url">{{ $trackingUrl }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Simpan kartu ini sampai servis selesai — unduh ulang kapan pun dari halaman lacak.
    </div>
</body>
</html>
