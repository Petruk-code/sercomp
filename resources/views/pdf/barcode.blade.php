<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Barcode Produk</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #111; }
        .label {
            display: inline-block;
            width: 46%;
            margin: 1.5%;
            padding: 10px;
            border: 1px dashed #999;
            text-align: center;
            vertical-align: top;
        }
        .name { font-weight: bold; font-size: 11px; margin-bottom: 4px; word-break: break-word; }
        .sku { color: #555; font-size: 9px; margin-bottom: 6px; }
        .barcode img { max-width: 100%; height: 55px; }
        .code { margin-top: 4px; letter-spacing: 2px; font-size: 12px; }
        .price { margin-top: 4px; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    @php($generator = new \Picqer\Barcode\BarcodeGeneratorPNG())
    @foreach ($products as $product)
        <div class="label">
            <div class="name">{{ $product->name }}</div>
            @if ($product->sku)
                <div class="sku">SKU: {{ $product->sku }}</div>
            @endif
            <div class="barcode">
                <img src="data:image/png;base64,{{ base64_encode($generator->getBarcode($product->barcode ?: $product->sku ?: $product->id, $generator::TYPE_CODE_128)) }}" alt="barcode">
            </div>
            <div class="code">{{ $product->barcode ?: $product->sku ?: $product->id }}</div>
            <div class="price">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</div>
        </div>
    @endforeach
</body>
</html>
