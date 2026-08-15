import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    CalendarClock,
    Cpu,
    Download,
    History,
    MessageSquareText,
    Moon,
    Search,
    Sun,
    XCircle,
} from 'lucide-react';
import { useSyncExternalStore } from 'react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { ServiceStatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useAppearance } from '@/hooks/use-appearance';
import { formatDate } from '@/lib/utils';
import { dashboard, tracking } from '@/routes';
import type { ServiceOrderStatus } from '@/types';

type Props = {
    query: string;
    cardUrl: string | null;
    order: {
        invoice_number: string;
        item_name: string;
        serial_number: string | null;
        complaint: string;
        status: ServiceOrderStatus;
        estimated_completion_at: string | null;
        created_at: string;
    } | null;
    timeline: {
        status: ServiceOrderStatus;
        label: string;
        created_at: string;
    }[];
};

export default function TrackingShow({
    query,
    cardUrl,
    order,
    timeline,
}: Props) {
    const [value, setValue] = useState(query);
    const { auth } = usePage().props;
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';
    const isMounted = useSyncExternalStore(
        () => () => {},
        () => true,
        () => false,
    );

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        router.get(tracking().url, { no: value || undefined });
    }

    return (
        <>
            <Head title="Lacak Servis" />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="sticky top-0 z-10 border-b bg-background/80 backdrop-blur">
                    <div className="mx-auto flex w-full max-w-3xl items-center justify-between px-4 py-3">
                        <Link
                            href="/"
                            className="flex items-center gap-2 font-semibold"
                        >
                            <span className="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-5 fill-current" />
                            </span>
                            SerComp
                        </Link>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Ganti tema"
                                onClick={() =>
                                    updateAppearance(isDark ? 'light' : 'dark')
                                }
                            >
                                {isMounted &&
                                    (isDark ? (
                                        <Sun className="size-4" />
                                    ) : (
                                        <Moon className="size-4" />
                                    ))}
                            </Button>
                            {auth.user && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </header>

                <main className="flex-1">
                    <section className="relative overflow-hidden">
                        <div
                            aria-hidden
                            className="pointer-events-none absolute inset-0"
                        >
                            <div className="absolute -top-40 left-1/2 h-[380px] w-[720px] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl dark:bg-primary/15" />
                            <div className="absolute top-20 -right-32 h-72 w-72 rounded-full bg-violet-600/10 blur-3xl dark:bg-violet-400/10" />
                            <div className="absolute top-40 -left-32 h-72 w-72 rounded-full bg-indigo-600/10 blur-3xl dark:bg-indigo-400/10" />
                        </div>

                        <div className="mx-auto max-w-3xl px-4 pt-14 pb-10 sm:pt-20">
                            <h1 className="text-center text-3xl font-bold tracking-tight text-balance sm:text-4xl">
                                Lacak{' '}
                                <span className="bg-linear-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent dark:from-indigo-400 dark:to-violet-400">
                                    Status Servis
                                </span>
                            </h1>
                            <p className="mx-auto mt-3 max-w-xl text-center text-muted-foreground">
                                Masukkan nomor servis pada tanda terima Anda
                                untuk melihat perkembangan pengerjaan secara
                                langsung.
                            </p>

                            <form
                                onSubmit={handleSubmit}
                                className="mx-auto mt-8 max-w-xl"
                            >
                                <div className="flex flex-col gap-2 rounded-2xl border bg-card p-2 shadow-lg shadow-indigo-600/5 sm:flex-row dark:shadow-primary/10">
                                    <Input
                                        className="flex-1 border-0 shadow-none focus-visible:ring-0"
                                        placeholder="Contoh: SRV-20260812-0001"
                                        value={value}
                                        onChange={(event) =>
                                            setValue(event.target.value)
                                        }
                                    />
                                    <Button type="submit" size="lg">
                                        <Search />
                                        Lacak
                                    </Button>
                                </div>
                            </form>
                            <p className="mt-3 text-center text-xs text-muted-foreground">
                                Tidak perlu login — cukup nomor servis pada
                                tanda terima Anda.
                            </p>
                        </div>
                    </section>

                    <section className="mx-auto max-w-3xl px-4 pb-16">
                        {order === null ? (
                            query !== '' && (
                                <Card>
                                    <CardContent className="flex flex-col items-center gap-4 py-14 text-center">
                                        <span className="flex size-14 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                                            <XCircle className="size-7" />
                                        </span>
                                        <div>
                                            <p className="text-lg font-semibold">
                                                Servis tidak ditemukan
                                            </p>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                Pastikan nomor servis pada tanda
                                                terima sudah benar.
                                            </p>
                                        </div>
                                        <Button variant="outline" asChild>
                                            <Link href="/">
                                                Kembali ke Beranda
                                            </Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            )
                        ) : (
                            <div className="space-y-5">
                                <Card className="overflow-hidden">
                                    <div className="h-1.5 bg-linear-to-r from-indigo-600 to-violet-600" />
                                    <CardHeader>
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <CardTitle>
                                                    {order.item_name}
                                                </CardTitle>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    No. Servis:{' '}
                                                    {order.invoice_number}
                                                </p>
                                            </div>
                                            <div className="flex flex-col items-end gap-2">
                                                <ServiceStatusBadge
                                                    status={order.status}
                                                />
                                                {cardUrl && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={cardUrl}
                                                            target="_blank"
                                                        >
                                                            <Download className="size-4" />
                                                            Unduh Kartu Lacak
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="space-y-3 text-sm">
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <div className="flex items-center gap-3 rounded-lg bg-muted/50 px-3 py-2">
                                                <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                                    <Cpu className="size-4" />
                                                </span>
                                                <div className="min-w-0">
                                                    <p className="text-xs text-muted-foreground">
                                                        Serial Number
                                                    </p>
                                                    <p className="truncate font-medium">
                                                        {order.serial_number ??
                                                            '-'}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-3 rounded-lg bg-muted/50 px-3 py-2">
                                                <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                                    <CalendarClock className="size-4" />
                                                </span>
                                                <div>
                                                    <p className="text-xs text-muted-foreground">
                                                        Diterima
                                                    </p>
                                                    <p className="font-medium">
                                                        {formatDate(
                                                            order.created_at,
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3 rounded-lg bg-muted/50 px-3 py-2">
                                            <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                                <MessageSquareText className="size-4" />
                                            </span>
                                            <div className="min-w-0">
                                                <p className="text-xs text-muted-foreground">
                                                    Keluhan
                                                </p>
                                                <p className="font-medium">
                                                    {order.complaint}
                                                </p>
                                            </div>
                                        </div>
                                        {order.estimated_completion_at && (
                                            <div className="flex items-center gap-3 rounded-lg border border-primary/20 bg-primary/5 px-3 py-2">
                                                <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                                    <CalendarCheck className="size-4" />
                                                </span>
                                                <div>
                                                    <p className="text-xs text-primary">
                                                        Estimasi selesai
                                                    </p>
                                                    <p className="font-medium">
                                                        {formatDate(
                                                            order.estimated_completion_at,
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <History className="size-4 text-primary" />
                                            Riwayat Pengerjaan
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <Timeline entries={timeline} />
                                    </CardContent>
                                </Card>
                            </div>
                        )}
                    </section>
                </main>

                <footer className="border-t py-5">
                    <div className="mx-auto flex w-full max-w-3xl flex-col items-center gap-2 px-4 text-center sm:flex-row sm:justify-between">
                        <p className="flex items-center gap-2 text-sm font-semibold">
                            <span className="flex size-6 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-3.5 fill-current" />
                            </span>
                            SerComp
                        </p>
                        <p className="text-xs text-muted-foreground">
                            &copy; {new Date().getFullYear()} SerComp. Semua hak
                            dilindungi.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}

const dotVariants: Record<
    ServiceOrderStatus,
    { dot: string; ring: string; text: string }
> = {
    pending: {
        dot: 'bg-slate-500',
        ring: 'ring-slate-500',
        text: 'text-slate-600',
    },
    diagnosed: {
        dot: 'bg-violet-500',
        ring: 'ring-violet-500',
        text: 'text-violet-600',
    },
    in_progress: {
        dot: 'bg-blue-500',
        ring: 'ring-blue-500',
        text: 'text-blue-600',
    },
    done: {
        dot: 'bg-emerald-500',
        ring: 'ring-emerald-500',
        text: 'text-emerald-600',
    },
    taken: { dot: 'bg-teal-500', ring: 'ring-teal-500', text: 'text-teal-600' },
    cancelled: {
        dot: 'bg-red-500',
        ring: 'ring-red-500',
        text: 'text-red-600',
    },
};

function Timeline({ entries }: { entries: Props['timeline'] }) {
    const lastIndex = entries.length - 1;

    return (
        <ol>
            {entries.map((entry, index) => {
                const variant = dotVariants[entry.status];
                const isLast = index === lastIndex;

                return (
                    <li
                        key={`${entry.status}-${entry.created_at}-${index}`}
                        className="relative flex gap-4 pb-4 last:pb-0"
                    >
                        {!isLast && (
                            <span
                                className="absolute top-6 bottom-0 left-[11px] w-px bg-linear-to-b from-border to-border/40"
                                aria-hidden
                            />
                        )}
                        <span
                            className={`mt-1 size-5 shrink-0 rounded-full ring-4 ${variant.dot} ${variant.ring} ring-offset-2 ring-offset-background`}
                            aria-hidden
                        />
                        <div
                            className={`flex flex-1 items-start justify-between gap-3 rounded-lg px-3 py-2 ${
                                isLast
                                    ? 'bg-primary/5 ring-1 ring-primary/20'
                                    : ''
                            }`}
                        >
                            <div>
                                <p
                                    className={`text-sm font-medium ${variant.text}`}
                                >
                                    {entry.label}
                                </p>
                                {isLast && (
                                    <p className="text-xs font-medium text-primary">
                                        Status saat ini
                                    </p>
                                )}
                            </div>
                            <span className="shrink-0 text-xs text-muted-foreground">
                                {formatDate(entry.created_at)}
                            </span>
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
