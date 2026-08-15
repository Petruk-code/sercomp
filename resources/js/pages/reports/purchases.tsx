import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { PaymentStatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { purchases as purchasesRoute } from '@/routes/reports';
import type { PaginatedData, Purchase } from '@/types';

type Props = {
    purchases: PaginatedData<Purchase>;
    summary: {
        total_purchases: number;
        total_value: string | number;
        total_paid: string | number;
        outstanding: string | number;
    };
    suppliers: Array<{ id: number; name: string }>;
    paymentStatusLabels: Record<string, string>;
    filters: {
        date_from: string;
        date_to: string;
        supplier_id: number | null;
        payment_status: string | null;
    };
};

export default function PurchasesReport({
    purchases,
    summary,
    suppliers,
    paymentStatusLabels,
    filters,
}: Props) {
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [supplierId, setSupplierId] = useState(filters.supplier_id ?? 'all');
    const [paymentStatus, setPaymentStatus] = useState(
        filters.payment_status ?? 'all',
    );

    function applyFilters() {
        router.get(
            purchasesRoute().url,
            {
                date_from: dateFrom,
                date_to: dateTo,
                supplier_id:
                    supplierId === 'all' ? undefined : Number(supplierId),
                payment_status:
                    paymentStatus === 'all' ? undefined : paymentStatus,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Laporan Pembelian" />

            <PageHeader
                title="Laporan Pembelian"
                description="Rekapitulasi pembelian (PO) dari supplier per periode."
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
                <select
                    value={supplierId}
                    onChange={(event) => setSupplierId(event.target.value)}
                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                >
                    <option value="all">Semua supplier</option>
                    {suppliers.map((supplier) => (
                        <option key={supplier.id} value={supplier.id}>
                            {supplier.name}
                        </option>
                    ))}
                </select>
                <select
                    value={paymentStatus}
                    onChange={(event) => setPaymentStatus(event.target.value)}
                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                >
                    <option value="all">Semua status</option>
                    {Object.entries(paymentStatusLabels).map(([key, label]) => (
                        <option key={key} value={key}>
                            {label}
                        </option>
                    ))}
                </select>
                <Button variant="secondary" onClick={applyFilters}>
                    Terapkan
                </Button>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Total Pembelian
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {summary.total_purchases}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Nilai Pembelian
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {formatCurrency(summary.total_value)}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Terbayar
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {formatCurrency(summary.total_paid)}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Sisa Hutang
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold text-destructive">
                        {formatCurrency(summary.outstanding)}
                    </CardContent>
                </Card>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">No. PO</th>
                            <th className="px-4 py-3">Supplier</th>
                            <th className="px-4 py-3">Tanggal</th>
                            <th className="px-4 py-3">Jatuh Tempo</th>
                            <th className="px-4 py-3 text-right">Total</th>
                            <th className="px-4 py-3 text-right">Dibayar</th>
                            <th className="px-4 py-3 text-right">Sisa</th>
                            <th className="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {purchases.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={8}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Tidak ada pembelian pada rentang tanggal
                                    ini.
                                </td>
                            </tr>
                        )}
                        {purchases.data.map((purchase) => {
                            const remaining = Math.max(
                                Number(purchase.total) -
                                    Number(purchase.paid_amount),
                                0,
                            );

                            return (
                                <tr
                                    key={purchase.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {purchase.purchase_number}
                                    </td>
                                    <td className="px-4 py-3">
                                        {purchase.supplier?.name ?? '-'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatDate(purchase.purchase_date)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {purchase.due_date
                                            ? formatDate(purchase.due_date)
                                            : '-'}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {formatCurrency(purchase.total)}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {formatCurrency(purchase.paid_amount)}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {remaining > 0
                                            ? formatCurrency(remaining)
                                            : '-'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <PaymentStatusBadge
                                            status={purchase.payment_status}
                                            labels={paymentStatusLabels}
                                        />
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            <Pagination data={purchases} />
        </>
    );
}

PurchasesReport.layout = {
    breadcrumbs: [{ title: 'Laporan Pembelian', href: purchasesRoute().url }],
};
