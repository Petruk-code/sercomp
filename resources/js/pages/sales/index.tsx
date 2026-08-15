import { Head, Link, router } from '@inertiajs/react';
import { Printer, Search } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { PaymentStatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index, print } from '@/routes/sales';
import type { PaginatedData, Sale } from '@/types';

type Props = {
    sales: PaginatedData<Sale>;
    filters: {
        search?: string;
        type?: string;
        payment_status?: string;
        date_from?: string;
        date_to?: string;
    };
    paymentStatusLabels: Record<string, string>;
};

export default function SalesIndex({
    sales,
    filters,
    paymentStatusLabels,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? 'all');
    const [paymentStatus, setPaymentStatus] = useState(
        filters.payment_status ?? 'all',
    );
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    function applyFilters() {
        router.get(
            index().url,
            {
                search: search || undefined,
                type: type === 'all' ? undefined : type,
                payment_status:
                    paymentStatus === 'all' ? undefined : paymentStatus,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Riwayat Transaksi" />

            <PageHeader
                title="Riwayat Transaksi"
                description="Daftar seluruh penjualan retail dan pelunasan servis."
            />

            <div className="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari no. nota atau pelanggan..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && applyFilters()
                        }
                    />
                </div>
                <div className="flex flex-wrap gap-2">
                    <select
                        value={type}
                        onChange={(event) => setType(event.target.value)}
                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="all">Semua jenis</option>
                        <option value="retail">Retail</option>
                        <option value="service">Servis</option>
                    </select>
                    <select
                        value={paymentStatus}
                        onChange={(event) =>
                            setPaymentStatus(event.target.value)
                        }
                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="all">Semua status</option>
                        {Object.entries(paymentStatusLabels).map(
                            ([key, label]) => (
                                <option key={key} value={key}>
                                    {label}
                                </option>
                            ),
                        )}
                    </select>
                    <Input
                        type="date"
                        className="h-9 w-auto"
                        value={dateFrom}
                        onChange={(event) => setDateFrom(event.target.value)}
                    />
                    <Input
                        type="date"
                        className="h-9 w-auto"
                        value={dateTo}
                        onChange={(event) => setDateTo(event.target.value)}
                    />
                    <Button variant="secondary" onClick={applyFilters}>
                        Terapkan
                    </Button>
                </div>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">No. Nota</th>
                            <th className="px-4 py-3">Jenis</th>
                            <th className="px-4 py-3">Pelanggan</th>
                            <th className="px-4 py-3">Metode</th>
                            <th className="px-4 py-3 text-right">Total</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Waktu</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {sales.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={8}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada transaksi.
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
                                    <Badge
                                        variant={
                                            sale.type === 'retail'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {sale.type === 'retail'
                                            ? 'Retail'
                                            : 'Servis'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    {sale.customer?.name ?? '-'}
                                    {sale.serviceOrder && (
                                        <span className="block text-xs text-muted-foreground">
                                            {sale.serviceOrder.invoice_number}
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    {sale.payment_method === 'cash'
                                        ? 'Tunai'
                                        : sale.payment_method === 'transfer'
                                          ? 'Transfer'
                                          : 'QRIS'}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    {formatCurrency(sale.total)}
                                </td>
                                <td className="px-4 py-3">
                                    <PaymentStatusBadge
                                        status={sale.payment_status}
                                        labels={paymentStatusLabels}
                                    />
                                </td>
                                <td className="px-4 py-3">
                                    {formatDate(sale.created_at)}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={`/sales/${sale.id}`}>
                                                Detail
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={print.url(sale.id)}
                                                target="_blank"
                                            >
                                                <Printer />
                                            </Link>
                                        </Button>
                                    </div>
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

SalesIndex.layout = {
    breadcrumbs: [{ title: 'Riwayat Transaksi', href: index().url }],
};
