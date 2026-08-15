import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import {
    PaymentStatusBadge,
    ServiceStatusBadge,
} from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { services as servicesRoute } from '@/routes/reports';
import { exportMethod as exportServices } from '@/routes/reports/services';
import type { PaginatedData, ServiceOrder } from '@/types';

type Props = {
    orders: PaginatedData<ServiceOrder>;
    summary: {
        total_orders: number;
        billed_cost: string | number;
        completed_revenue: string | number;
    };
    statusCounts: Record<string, number>;
    statusLabels: Record<string, string>;
    paymentStatusLabels: Record<string, string>;
    filters: { date_from: string; date_to: string };
};

export default function ServicesReport({
    orders,
    summary,
    statusCounts,
    statusLabels,
    paymentStatusLabels,
    filters,
}: Props) {
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    function applyFilters() {
        router.get(
            servicesRoute().url,
            { date_from: dateFrom, date_to: dateTo },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Laporan Servis" />

            <PageHeader
                title="Laporan Servis"
                description="Rekapitulasi servis masuk dan pendapatan servis."
            >
                <Button variant="outline" asChild>
                    <a
                        href={
                            exportServices({
                                query: {
                                    date_from: dateFrom,
                                    date_to: dateTo,
                                },
                            }).url
                        }
                    >
                        <Download />
                        Export CSV
                    </a>
                </Button>
            </PageHeader>

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

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Total Servis Masuk
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {summary.total_orders}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Total Tagihan
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {formatCurrency(summary.billed_cost)}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Pendapatan Selesai
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {formatCurrency(summary.completed_revenue)}
                    </CardContent>
                </Card>
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                {Object.entries(statusLabels).map(([status, label]) => (
                    <Card key={status} className="min-w-28">
                        <CardHeader className="pb-1">
                            <CardTitle className="text-xs text-muted-foreground">
                                {label}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-xl font-semibold">
                            {statusCounts[status] ?? 0}
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">No. Servis</th>
                            <th className="px-4 py-3">Unit</th>
                            <th className="px-4 py-3">Pelanggan</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Pembayaran</th>
                            <th className="px-4 py-3 text-right">Total</th>
                            <th className="px-4 py-3">Masuk</th>
                        </tr>
                    </thead>
                    <tbody>
                        {orders.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Tidak ada servis pada rentang tanggal ini.
                                </td>
                            </tr>
                        )}
                        {orders.data.map((order) => (
                            <tr
                                key={order.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3 font-medium">
                                    {order.invoice_number}
                                </td>
                                <td className="px-4 py-3">{order.item_name}</td>
                                <td className="px-4 py-3">
                                    {order.customer?.name ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    <ServiceStatusBadge status={order.status} />
                                </td>
                                <td className="px-4 py-3">
                                    {order.sale ? (
                                        <PaymentStatusBadge
                                            status={order.sale.payment_status}
                                            labels={paymentStatusLabels}
                                        />
                                    ) : (
                                        <span className="text-xs text-muted-foreground">
                                            -
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    {formatCurrency(order.total_cost)}
                                </td>
                                <td className="px-4 py-3">
                                    {formatDate(order.created_at)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination data={orders} />
        </>
    );
}

ServicesReport.layout = {
    breadcrumbs: [{ title: 'Laporan Servis', href: servicesRoute().url }],
};
