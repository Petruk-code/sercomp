import { Head, Link, router } from '@inertiajs/react';
import { Printer, Search } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCurrency } from '@/lib/utils';
import { barcode } from '@/routes/master/products';
import { stock as stockRoute } from '@/routes/reports';
import type { PaginatedData, Product } from '@/types';

type Props = {
    products: PaginatedData<Product>;
    summary: {
        total_products: number;
        total_stock: number;
        low_stock_products: number;
        stock_value: string | number;
    };
    filters: { search?: string; low_stock?: boolean };
};

export default function StockReport({ products, summary, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [lowStock, setLowStock] = useState(Boolean(filters.low_stock));

    function applyFilters() {
        router.get(
            stockRoute().url,
            {
                search: search || undefined,
                low_stock: lowStock ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    const selectedIds = products.data
        .filter((product) => product.barcode || product.sku)
        .map((product) => product.id)
        .join(',');

    return (
        <>
            <Head title="Laporan Stok" />

            <PageHeader
                title="Laporan Stok"
                description="Pantau ketersediaan dan nilai stok produk."
            >
                {selectedIds && (
                    <Button variant="outline" asChild>
                        <Link
                            href={`${barcode().url}?ids=${selectedIds}`}
                            target="_blank"
                        >
                            <Printer />
                            Cetak Barcode
                        </Link>
                    </Button>
                )}
            </PageHeader>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Total Produk
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {summary.total_products}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Total Stok
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {summary.total_stock}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Stok Menipis
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {summary.low_stock_products}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Nilai Stok (Modal)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {formatCurrency(summary.stock_value)}
                    </CardContent>
                </Card>
            </div>

            <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari produk..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && applyFilters()
                        }
                    />
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={lowStock}
                        onChange={(event) => setLowStock(event.target.checked)}
                        className="size-4 rounded border-input"
                    />
                    Hanya stok menipis
                </label>
                <Button variant="secondary" onClick={applyFilters}>
                    Terapkan
                </Button>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">Produk</th>
                            <th className="px-4 py-3">Kategori</th>
                            <th className="px-4 py-3 text-right">Stok</th>
                            <th className="px-4 py-3 text-right">Min. Stok</th>
                            <th className="px-4 py-3 text-right">
                                Harga Modal
                            </th>
                            <th className="px-4 py-3 text-right">Nilai Stok</th>
                            <th className="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {products.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Tidak ada produk.
                                </td>
                            </tr>
                        )}
                        {products.data.map((product) => {
                            const isLow =
                                product.min_stock > 0 &&
                                product.stock <= product.min_stock;

                            return (
                                <tr
                                    key={product.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {product.name}
                                    </td>
                                    <td className="px-4 py-3">
                                        {product.category?.name ?? '-'}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {product.stock}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {product.min_stock}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {formatCurrency(product.purchase_price)}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {formatCurrency(
                                            Number(product.stock) *
                                                Number(product.purchase_price),
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        {isLow ? (
                                            <Badge variant="destructive">
                                                Stok menipis
                                            </Badge>
                                        ) : (
                                            <Badge variant="outline">
                                                Aman
                                            </Badge>
                                        )}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            <Pagination data={products} />
        </>
    );
}

StockReport.layout = {
    breadcrumbs: [{ title: 'Laporan Stok', href: stockRoute().url }],
};
