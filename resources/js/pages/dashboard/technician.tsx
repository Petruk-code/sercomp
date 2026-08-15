import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Hammer, Inbox, Wrench } from 'lucide-react';
import { ServiceStatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/services';
import type { ServiceOrder } from '@/types';

type Props = {
    stats: {
        incoming: number;
        my_active: number;
        in_progress: number;
        done_pending_pickup: number;
    };
    incomingServices: ServiceOrder[];
    myServices: ServiceOrder[];
};

export default function TechnicianDashboard({
    stats,
    incomingServices,
    myServices,
}: Props) {
    const cards = [
        {
            title: 'Servis Masuk',
            value: String(stats.incoming),
            hint: 'Menunggu dikerjakan',
            icon: Inbox,
        },
        {
            title: 'Sedang Dikerjakan',
            value: String(stats.in_progress),
            hint: `${stats.my_active} total aktif milik saya`,
            icon: Hammer,
        },
        {
            title: 'Selesai',
            value: String(stats.done_pending_pickup),
            hint: 'Siap diambil',
            icon: CheckCircle2,
        },
    ];

    return (
        <>
            <Head title="Dashboard Teknisi" />

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
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

            <div className="grid gap-6 lg:grid-cols-2">
                <div className="rounded-xl border">
                    <div className="flex items-center justify-between border-b px-5 py-4">
                        <h3 className="flex items-center gap-2 font-semibold">
                            <Inbox className="size-4" />
                            Servis Masuk
                        </h3>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={index().url}>
                                Lihat semua
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                    <div className="divide-y">
                        {incomingServices.length === 0 && (
                            <p className="px-5 py-8 text-center text-sm text-muted-foreground">
                                Tidak ada servis masuk baru.
                            </p>
                        )}
                        {incomingServices.map((order) => (
                            <ServiceRow key={order.id} order={order} />
                        ))}
                    </div>
                </div>

                <div className="rounded-xl border">
                    <div className="flex items-center justify-between border-b px-5 py-4">
                        <h3 className="flex items-center gap-2 font-semibold">
                            <Wrench className="size-4" />
                            Servis Saya
                        </h3>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={index().url}>
                                Lihat semua
                                <ArrowRight />
                            </Link>
                        </Button>
                    </div>
                    <div className="divide-y">
                        {myServices.length === 0 && (
                            <p className="px-5 py-8 text-center text-sm text-muted-foreground">
                                Belum ada servis yang ditugaskan ke Anda.
                            </p>
                        )}
                        {myServices.map((order) => (
                            <ServiceRow key={order.id} order={order} />
                        ))}
                    </div>
                </div>
            </div>
        </>
    );
}

function ServiceRow({ order }: { order: ServiceOrder }) {
    return (
        <div className="flex items-center justify-between gap-3 px-5 py-3">
            <div className="min-w-0">
                <p className="truncate text-sm font-medium">
                    {order.item_name}
                </p>
                <p className="text-xs text-muted-foreground">
                    {order.invoice_number} · {order.customer?.name ?? '-'} ·{' '}
                    {formatDate(order.created_at)}
                </p>
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <span className="text-xs text-muted-foreground">
                    {formatCurrency(order.total_cost)}
                </span>
                <ServiceStatusBadge status={order.status} />
                <Button variant="outline" size="sm" asChild>
                    <Link href={show(order).url}>Buka</Link>
                </Button>
            </div>
        </div>
    );
}

TechnicianDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard Teknisi', href: dashboard() }],
};
