import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { sales as salesRoute } from '@/routes/reports';
import type { PaginatedData, Sale } from '@/types';

type Props = {
    sales: PaginatedData<Sale>;
    summary: {
        total_transactions: number;
        total_revenue: string | number;
        retail_revenue: string | number;
        service_revenue: string | number;
    };
    filters: { date_from: string; date_to: string };
};

export default function SalesReport({ sales, summary, filters }: Props) {
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    function applyFilters() {
        router.get(
            salesRoute().url,
            { date_from: dateFrom, date_to: dateTo },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Laporan Penjualan" />

            <PageHeader
                title="Laporan Penjualan"
                description="Rekapitulasi pendapatan penjualan retail dan servis."
            />

            <div className="mb-4 flex flex-wrap items-end gap-2">
                <div className="grid gap-1">
                    <label
                        className="text-xs text-muted-foreground"
                        htmlFor="date_from"
                    >
                        Dari
                    </label>
                    <Input
                        id="date_from"
                        type="date"
                        className="h-9 w-auto"
                        value={dateFrom}
                        onChange={(event) => setDateFrom(event.target.value)}
                    />
                </div>
                <div className="grid gap-1">
                    <label
                        className="text-xs text-muted-foreground"
                        htmlFor="date_to"
                    >
                        Sampai
                    </label>
                    <Input
                        id="date_to"
                        type="date"
                        className="h-9 w-auto"
                        value={dateTo}
                        onChange={(event) => setDateTo(event.target.value)}
                    />
                </div>
                <Button variant="secondary" onClick={applyFilters}>
                    Terapkan
                </Button>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Total Transaksi
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {summary.total_transactions}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Pendapatan Total
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {formatCurrency(summary.total_revenue)}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Retail
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {formatCurrency(summary.retail_revenue)}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Servis
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {formatCurrency(summary.service_revenue)}
                    </CardContent>
                </Card>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">No. Nota</th>
                            <th className="px-4 py-3">Jenis</th>
                            <th className="px-4 py-3">Pelanggan</th>
                            <th className="px-4 py-3 text-right">Total</th>
                            <th className="px-4 py-3">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        {sales.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Tidak ada transaksi pada rentang tanggal
                                    ini.
                                </td>
                            </tr>
                        )}
                        {sales.data.map((sale) => (
                            <tr
                                key={sale.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3 font-medium">
                                    {sale.invoice_number}
                                </td>
                                <td className="px-4 py-3">
                                    {sale.type === 'retail'
                                        ? 'Retail'
                                        : 'Servis'}
                                </td>
                                <td className="px-4 py-3">
                                    {sale.customer?.name ?? '-'}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    {formatCurrency(sale.total)}
                                </td>
                                <td className="px-4 py-3">
                                    {formatDate(sale.created_at)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination data={sales} />
        </>
    );
}

SalesReport.layout = {
    breadcrumbs: [{ title: 'Laporan Penjualan', href: salesRoute().url }],
};
