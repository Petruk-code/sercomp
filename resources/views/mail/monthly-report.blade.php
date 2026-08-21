<x-mail::message>
# Laporan Bulanan — {{ $periodLabel }}

Ringkasan performa semua cabang untuk periode **{{ $periodLabel }}**.

## Penjualan

<x-mail::table>
| Cabang | Transaksi | Total |
| --- | --- | --- |
@foreach ($salesPerStore as $row)
| {{ $row['store'] }} | {{ number_format($row['count'], 0, ',', '.') }} | Rp{{ number_format($row['total'], 0, ',', '.') }} |
@endforeach
| **Total** | **{{ number_format($salesCount, 0, ',', '.') }}** | **Rp{{ number_format($salesTotal, 0, ',', '.') }}** |
</x-mail::table>

## Servis

- Servis masuk: **{{ $servicesIn }}**
- Servis selesai: **{{ $servicesDone }}**

## Hutang Jatuh Tempo

@if (count($overdueDebts) > 0)
<x-mail::table>
| Supplier | Jatuh Tempo | Sisa |
| --- | --- | --- |
@foreach ($overdueDebts as $debt)
| {{ $debt['supplier'] }} | {{ $debt['due_date'] }} | Rp{{ number_format($debt['outstanding'], 0, ',', '.') }} |
@endforeach
| **Total** | | **Rp{{ number_format($overdueDebtsTotal, 0, ',', '.') }}** |
</x-mail::table>
@else
Tidak ada hutang supplier yang jatuh tempo.
@endif

## Stok Menipis

@if ($lowStockCount > 0)
@if ($lowStockCount > count($lowStockProducts))
Menampilkan {{ count($lowStockProducts) }} dari {{ $lowStockCount }} produk.
@endif

<x-mail::table>
| Produk | Cabang | Stok | Min. |
| --- | --- | --- | --- |
@foreach ($lowStockProducts as $product)
| {{ $product['name'] }} | {{ $product['store'] }} | {{ $product['stock'] }} | {{ $product['min_stock'] }} |
@endforeach
</x-mail::table>
@else
Semua stok aman.
@endif

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
