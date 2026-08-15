import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    PackageOpen,
    Receipt,
    Users,
    Wallet,
    Wrench,
} from 'lucide-react';
import { DashboardTrends } from '@/components/dashboard-trends';
import type { DashboardTrends as DashboardTrendsData } from '@/components/dashboard-trends';
import { ServiceStatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as productsIndex } from '@/routes/master/products';
import { index as salesIndex } from '@/routes/sales';
import { index as servicesIndex } from '@/routes/services';
import type { Product, Sale, ServiceOrder } from '@/types';

type Props = {
    stats: {
        sales_today: string | number;
        sales_today_count: number;
        services_active: number;
        services_today: number;
        services_done_pending_pickup: number;
        low_stock_products: number;
        total_products: number;
        total_customers: number;
        debt_outstanding: string | number;
        debt_overdue: number;
    };
    recentServiceOrders: ServiceOrder[];
    recentSales: Sale[];
    lowStockProducts: Product[];
    serviceQueue: ServiceOrder[];
    trends: DashboardTrendsData;
};

function waitingLabel(createdAt: string): string {
    const days = Math.floor(
        (Date.now() - Date.parse(createdAt)) / (24 * 60 * 60 * 1000),
    );

    if (days <= 0) {
        return 'hari ini';
    }

    return `${days} hari`;
}

export default function Dashboard({
    stats,
    recentServiceOrders,
    recentSales,
    lowStockProducts,
    serviceQueue,
    trends,
}: Props) {
    const cards = [
        {
            title: 'Penjualan Hari Ini',
            value: formatCurrency(stats.sales_today),
            hint: `${stats.sales_today_count} transaksi`,
            icon: Receipt,
        },
        {
            title: 'Servis Berjalan',
            value: String(stats.services_active),
            hint: `${stats.services_done_pending_pickup} siap diambil`,
            icon: Wrench,
        },
        {
            title: 'Produk',
            value: String(stats.total_products),
            hint: `${stats.low_stock_products} stok menipis`,
            icon: PackageOpen,
        },
        {
            title: 'Pelanggan',
            value: String(stats.total_customers),
            hint: `${stats.services_today} servis hari ini`,
            icon: Users,
        },
        {
            title: 'Hutang Berjalan',
            value: formatCurrency(stats.debt_outstanding),
            hint: `${stats.debt_overdue} transaksi jatuh tempo`,
            icon: Wallet,
        },
    ];

    return (
        <>
            <Head title="Dashboard" />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {cards.map((card) => (
                    <Card key={card.title}>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm text-muted-foreground">
                                {card.title}
                            </CardTitle>
                            <card.icon className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold">
                                {card.value}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {card.hint}
                            </p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="mb-6">
                <DashboardTrends trends={trends} />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <div className="rounded-xl border">
                    <div className="flex items-center justify-between border-b px-5 py-4">
                        <h3 className="font-semibold">Servis Terbaru</h3>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={servicesIndex.url()}>
                                Lihat semua
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                    <div className="divide-y">
                        {recentServiceOrders.length === 0 && (
                            <p className="px-5 py-8 text-center text-sm text-muted-foreground">
                                Belum ada servis.
                            </p>
                        )}
                        {recentServiceOrders.map((order) => (
                            <div
                                key={order.id}
                                className="flex items-center justify-between gap-3 px-5 py-3"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {order.item_name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {order.invoice_number} ·{' '}
                                        {order.customer?.name ?? '-'}
                                    </p>
                                </div>
                                <div className="flex shrink-0 items-center gap-2">
                                    <span className="text-xs text-muted-foreground">
                                        {formatCurrency(order.total_cost)}
                                    </span>
                                    <ServiceStatusBadge status={order.status} />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="rounded-xl border">
                    <div className="flex items-center justify-between border-b px-5 py-4">
                        <h3 className="font-semibold">Transaksi Terbaru</h3>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={salesIndex.url()}>
                                Lihat semua
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                    <div className="divide-y">
                        {recentSales.length === 0 && (
                            <p className="px-5 py-8 text-center text-sm text-muted-foreground">
                                Belum ada transaksi.
                            </p>
                        )}
                        {recentSales.map((sale) => (
                            <div
                                key={sale.id}
                                className="flex items-center justify-between gap-3 px-5 py-3"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {sale.invoice_number}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {sale.customer?.name ?? 'Umum'} ·{' '}
                                        {formatDate(sale.created_at)}
                                    </p>
                                </div>
                                <span className="shrink-0 text-sm font-medium">
                                    {formatCurrency(sale.total)}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="rounded-xl border">
                    <div className="flex items-center justify-between border-b px-5 py-4">
                        <h3 className="font-semibold">Stok Menipis</h3>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={productsIndex.url()}>
                                Lihat semua
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                    <div className="divide-y">
                        {lowStockProducts.length === 0 && (
                            <p className="px-5 py-8 text-center text-sm text-muted-foreground">
                                Semua stok aman.
                            </p>
                        )}
                        {lowStockProducts.map((product) => (
                            <div
                                key={product.id}
                                className="flex items-center justify-between gap-3 px-5 py-3"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {product.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        min. {product.min_stock} {product.unit}
                                    </p>
                                </div>
                                <span
                                    className={`shrink-0 text-sm font-semibold ${
                                        product.stock <= 0
                                            ? 'text-destructive'
                                            : 'text-amber-600 dark:text-amber-400'
                                    }`}
                                >
                                    {product.stock} {product.unit}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="rounded-xl border">
                    <div className="flex items-center justify-between border-b px-5 py-4">
                        <h3 className="font-semibold">Antrian Servis</h3>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={servicesIndex.url()}>
                                Lihat semua
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                    <div className="divide-y">
                        {serviceQueue.length === 0 && (
                            <p className="px-5 py-8 text-center text-sm text-muted-foreground">
                                Antrian kosong.
                            </p>
                        )}
                        {serviceQueue.map((order) => (
                            <div
                                key={order.id}
                                className="flex items-center justify-between gap-3 px-5 py-3"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {order.item_name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {order.invoice_number} ·{' '}
                                        {order.customer?.name ?? '-'}
                                    </p>
                                </div>
                                <div className="flex shrink-0 items-center gap-2">
                                    <span className="text-xs text-muted-foreground">
                                        {waitingLabel(order.created_at)}
                                    </span>
                                    <ServiceStatusBadge status={order.status} />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
