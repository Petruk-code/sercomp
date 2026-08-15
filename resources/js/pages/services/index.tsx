import { Head, Link, router } from '@inertiajs/react';
import { Plus, Printer, Search } from 'lucide-react';
import { useState } from 'react';
import ServiceOrderController from '@/actions/App/Http/Controllers/Service/ServiceOrderController';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { ServiceStatusBadge, WarrantyBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { create, index } from '@/routes/services';
import type { PaginatedData, ServiceOrder, ServiceOrderStatus } from '@/types';

type Props = {
    orders: PaginatedData<ServiceOrder>;
    filters: { search?: string; status?: string; warranty?: string };
    statuses: ServiceOrderStatus[];
    statusLabels: Record<string, string>;
};

export default function ServicesIndex({
    orders,
    filters,
    statuses,
    statusLabels,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [warranty, setWarranty] = useState(filters.warranty ?? 'all');

    function applyFilters() {
        router.get(
            index().url,
            {
                search: search || undefined,
                status: status === 'all' ? undefined : status,
                warranty: warranty === 'all' ? undefined : warranty,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Manajemen Servis" />

            <PageHeader
                title="Manajemen Servis"
                description="Pantau dan kelola semua unit yang masuk servis."
            >
                <Button asChild>
                    <Link href={create().url}>
                        <Plus />
                        Servis Baru
                    </Link>
                </Button>
            </PageHeader>

            <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari no. servis, unit, pelanggan..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && applyFilters()
                        }
                    />
                </div>
                <select
                    value={status}
                    onChange={(event) => setStatus(event.target.value)}
                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                >
                    <option value="all">Semua status</option>
                    {statuses.map((item) => (
                        <option key={item} value={item}>
                            {statusLabels[item]}
                        </option>
                    ))}
                </select>
                <select
                    value={warranty}
                    onChange={(event) => setWarranty(event.target.value)}
                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                >
                    <option value="all">Semua garansi</option>
                    <option value="active">Garansi aktif</option>
                    <option value="expired">Garansi habis</option>
                    <option value="none">Tanpa garansi</option>
                </select>
                <Button variant="secondary" onClick={applyFilters}>
                    Terapkan
                </Button>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">No. Servis</th>
                            <th className="px-4 py-3">Unit</th>
                            <th className="px-4 py-3">Pelanggan</th>
                            <th className="px-4 py-3">Teknisi</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Garansi</th>
                            <th className="px-4 py-3 text-right">Total</th>
                            <th className="px-4 py-3">Masuk</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {orders.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={9}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada servis.
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
                                <td className="px-4 py-3">
                                    {order.item_name}
                                    {order.serial_number && (
                                        <span className="block text-xs text-muted-foreground">
                                            SN: {order.serial_number}
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    {order.customer?.name ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {order.technician?.name ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    <ServiceStatusBadge status={order.status} />
                                </td>
                                <td className="px-4 py-3">
                                    <WarrantyBadge
                                        warrantyDays={order.warranty_days}
                                        warrantyEndDate={
                                            order.warranty_end_date
                                        }
                                    />
                                </td>
                                <td className="px-4 py-3 text-right">
                                    {formatCurrency(order.total_cost)}
                                </td>
                                <td className="px-4 py-3">
                                    {formatDate(order.created_at)}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={ServiceOrderController.print.url(
                                                    order.id,
                                                )}
                                                target="_blank"
                                            >
                                                <Printer />
                                                Cetak
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`/services/${order.id}`}
                                            >
                                                Detail
                                            </Link>
                                        </Button>
                                    </div>
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

ServicesIndex.layout = {
    breadcrumbs: [{ title: 'Manajemen Servis', href: index().url }],
};
