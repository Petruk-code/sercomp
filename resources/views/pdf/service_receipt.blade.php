<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tanda Terima {{ $order->invoice_number }}</title>
    <style>
        @page { margin: 8mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #111; padding: 12px; }
        .center { text-align: center; }
        .header { margin-bottom: 8px; border-bottom: 2px solid #4f46e5; padding-bottom: 6px; }
        .header h1 { font-size: 18px; letter-spacing: 1px; color: #4f46e5; }
        .header p { font-size: 10px; color: #555; margin-top: 2px; letter-spacing: 3px; text-transform: uppercase; }
        .meta { margin-bottom: 6px; }
        .meta table { width: 100%; }
        .meta td { padding: 1px 0; font-size: 9px; vertical-align: top; }
        .meta .label { color: #64748b; width: 100px; text-transform: uppercase; font-size: 8px; letter-spacing: 0.4px; }
        .meta .value { font-weight: 600; }
        .divider { border-top: 2px solid #4f46e5; margin: 6px 0; }
        .note { margin-top: 6px; font-size: 8px; color: #64748b; line-height: 1.5; border: 1px solid #e2e8f0; border-left: 3px solid #4f46e5; padding: 5px 8px; background: #f8fafc; }
        .tracking { margin-top: 8px; }
        .tracking-box { border: 1.5px dashed #4f46e5; padding: 6px; }
        .tracking-box table { width: 100%; }
        .tracking-box .qr { text-align: center; width: 90px; }
        .tracking-box .qr img { width: 72px; }
        .tracking-box .qr-label { font-size: 7px; color: #64748b; margin-top: 1px; }
        .tracking-box .info { padding-left: 8px; }
        .tracking-box .info-title { font-size: 9px; font-weight: bold; color: #4f46e5; }
        .tracking-box .info-url { font-size: 8px; color: #4f46e5; word-break: break-all; margin-top: 2px; }
        .sign { margin-top: 18px; }
        .sign td { font-size: 9px; width: 50%; }
        .sign .sig-left { text-align: left; }
        .sign .sig-right { text-align: right; }
        .sign .name { padding-top: 32px; font-weight: bold; }
        .footer { margin-top: 10px; text-align: center; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header center">
        <h1>{{ config('app.name') }}</h1>
        <p>{{ __('TANDA TERIMA SERVIS') }}</p>
    </div>

    <div class="meta">
        <table>
            <tr><td class="label">No. Servis</td><td class="value">{{ $order->invoice_number }}</td></tr>
            <tr><td class="label">Tanggal Masuk</td><td class="value">{{ $order->created_at->translatedFormat('d F Y H:i') }}</td></tr>
            <tr><td class="label">Estimasi Selesai</td><td class="value">{{ $order->estimated_completion_at?->translatedFormat('d F Y') ?? '-' }}</td></tr>
            <tr><td class="label">Status</td><td class="value">{{ $statusLabels[$order->status] ?? $order->status }}</td></tr>
            <tr><td class="label">Diterima Oleh</td><td class="value">{{ $order->receiver?->name ?? '-' }}</td></tr>
            <tr><td class="label">Teknisi</td><td class="value">{{ $order->technician?->name ?? 'Belum ditugaskan' }}</td></tr>
        </table>
    </div>

    <div class="divider"></div>

    <div class="meta">
        <table>
            <tr><td class="label">Pelanggan</td><td class="value">{{ $order->customer?->name ?? '-' }}</td></tr>
            <tr><td class="label">No. HP</td><td class="value">{{ $order->customer?->phone ?? '-' }}</td></tr>
            <tr><td class="label">Unit</td><td class="value">{{ $order->item_name }}</td></tr>
            <tr><td class="label">Serial Number</td><td class="value">{{ $order->serial_number ?? '-' }}</td></tr>
            <tr><td class="label">Keluhan</td><td class="value">{{ $order->complaint }}</td></tr>
            @if ($order->physical_condition)
                <tr><td class="label">Kondisi Fisik</td><td class="value">{{ $order->physical_condition }}</td></tr>
            @endif
            @if ($order->notes)
                <tr><td class="label">Catatan</td><td class="value">{{ $order->notes }}</td></tr>
            @endif
            <tr><td class="label">Estimasi Biaya</td><td class="value">Rp {{ number_format($order->estimated_cost, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    <div class="note">
        Perhatian: data pelanggan wajib sesuai dengan kartu identitas (KTP/SIM) yang ditunjukkan saat penyerahan unit.
        Tanda terima ini adalah bukti resmi penerimaan unit; mohon simpan baik-baik dan bawa saat pengambilan unit.
    </div>

    <div class="tracking">
        <div class="tracking-box">
            <table>
                <tr>
                    <td class="qr">
                        <img src="{{ $qrDataUri }}" alt="QR Lacak Servis">
                        <div class="qr-label">Scan untuk lacak status</div>
                    </td>
                    <td class="info">
                        <div class="info-title">Lacak Status Servis</div>
                        <div>Pindai kode QR di atas atau kunjungi tautan berikut menggunakan nomor servis:</div>
                        <div class="info-url">{{ $trackingUrl }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="sign">
        <table width="100%">
            <tr>
                <td class="sig-left">Diterima oleh,</td>
                <td class="sig-right">Pelanggan,</td>
            </tr>
            <tr>
                <td class="sig-left name">{{ $order->receiver?->name ?? '-' }}</td>
                <td class="sig-right name">{{ $order->customer?->name ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ config('app.name') }} — HP Service &amp; POS
    </div>
</body>
</html>
