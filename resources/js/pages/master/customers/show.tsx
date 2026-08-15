import { Head, Link } from '@inertiajs/react';
import { MapPin, Phone, Pencil, ShoppingCart, Wrench } from 'lucide-react';
import CustomerController from '@/actions/App/Http/Controllers/Master/CustomerController';
import ServiceOrderController from '@/actions/App/Http/Controllers/Service/ServiceOrderController';
import { Pagination } from '@/components/pagination';
import { ServiceStatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index } from '@/routes/master/customers';
import type { Customer, PaginatedData, ServiceOrder } from '@/types';

type Props = {
    customer: Customer;
    serviceOrders: PaginatedData<ServiceOrder>;
    totalPaid: number;
    lastServiceAt: string | null;
    statusLabels: Record<string, string>;
};

export default function CustomerShow({
    customer,
    serviceOrders,
    totalPaid,
    lastServiceAt,
}: Props) {
    return (
        <>
            <Head title={customer.name} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">
                        {customer.name}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Pelanggan sejak {formatDate(customer.created_at)}
                    </p>
                </div>
                <Button variant="outline" size="sm" asChild>
                    <Link href={CustomerController.edit.url(customer.id)}>
                        <Pencil />
                        Edit Pelanggan
                    </Link>
                </Button>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <div className="space-y-6">
                    <div className="rounded-xl border">
                        <div className="border-b px-5 py-4">
                            <h3 className="font-semibold">Kontak</h3>
                        </div>
                        <dl className="space-y-3 p-5 text-sm">
                            <div>
                                <dt className="text-muted-foreground">
                                    Nomor HP
                                </dt>
                                <dd className="font-medium">
                                    {customer.phone ?? '-'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Email</dt>
                                <dd className="font-medium">
                                    {customer.email ?? '-'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Alamat
                                </dt>
                                <dd className="flex items-start gap-1.5">
                                    <MapPin className="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                                    {customer.address ?? '-'}
                                </dd>
                            </div>
                            {customer.notes && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        Catatan
                                    </dt>
                                    <dd>{customer.notes}</dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="rounded-xl border p-5">
                            <ShoppingCart className="size-4 text-muted-foreground" />
                            <p className="mt-2 text-2xl font-semibold">
                                {customer.sales_count ?? 0}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Transaksi
                            </p>
                        </div>
                        <div className="rounded-xl border p-5">
                            <Wrench className="size-4 text-muted-foreground" />
                            <p className="mt-2 text-2xl font-semibold">
                                {customer.service_orders_count ?? 0}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Servis
                            </p>
                        </div>
                    </div>

                    <div className="rounded-xl border p-5 text-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">
                                Total dibayarkan
                            </span>
                            <span className="font-semibold">
                                {formatCurrency(totalPaid)}
                            </span>
                        </div>
                        <div className="mt-2 flex items-center justify-between">
                            <span className="text-muted-foreground">
                                Servis terakhir
                            </span>
                            <span className="font-medium">
                                {lastServiceAt
                                    ? formatDate(lastServiceAt)
                                    : '-'}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="lg:col-span-2">
                    <div className="rounded-xl border">
                        <div className="flex items-center justify-between border-b px-5 py-4">
                            <h3 className="flex items-center gap-2 font-semibold">
                                <Wrench className="size-4" />
                                Riwayat Servis
                            </h3>
                            <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                <Phone className="size-3.5" />
                                {customer.phone ?? '-'}
                            </span>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs text-muted-foreground">
                                        <th className="px-5 py-3">
                                            No. Servis
                                        </th>
                                        <th className="px-5 py-3">Unit</th>
                                        <th className="px-5 py-3">Status</th>
                                        <th className="px-5 py-3">Teknisi</th>
                                        <th className="px-5 py-3 text-right">
                                            Total
                                        </th>
                                        <th className="px-5 py-3 text-right">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {serviceOrders.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="px-5 py-8 text-center text-muted-foreground"
                                            >
                                                Belum ada riwayat servis.
                                            </td>
                                        </tr>
                                    )}
                                    {serviceOrders.data.map((order) => (
                                        <tr
                                            key={order.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-5 py-3 font-medium">
                                                {order.invoice_number}
                                            </td>
                                            <td className="px-5 py-3">
                                                {order.item_name}
                                            </td>
                                            <td className="px-5 py-3">
                                                <ServiceStatusBadge
                                                    status={order.status}
                                                />
                                            </td>
                                            <td className="px-5 py-3">
                                                {order.technician?.name ?? '-'}
                                            </td>
                                            <td className="px-5 py-3 text-right">
                                                {formatCurrency(
                                                    order.total_cost,
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={ServiceOrderController.show.url(
                                                            order.id,
                                                        )}
                                                    >
                                                        Detail
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4">
                        <Pagination data={serviceOrders} />
                    </div>
                </div>
            </div>
        </>
    );
}

CustomerShow.layout = {
    breadcrumbs: [
        { title: 'Master Data', href: index().url },
        { title: 'Pelanggan', href: index().url },
        { title: 'Profil Pelanggan', href: index().url },
    ],
};
