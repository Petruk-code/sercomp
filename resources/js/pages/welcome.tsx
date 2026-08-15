import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Boxes,
    Check,
    CircleDot,
    Moon,
    Package,
    QrCode,
    ScanLine,
    ShoppingCart,
    Sparkles,
    Sun,
    Users,
    Wrench,
} from 'lucide-react';
import { useSyncExternalStore } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useAppearance } from '@/hooks/use-appearance';
import { login, tracking } from '@/routes';

const features = [
    {
        icon: Wrench,
        title: 'Manajemen Servis',
        description:
            'Catat servis masuk, diagnosa, tugaskan teknisi, hingga serah terima unit dengan riwayat status yang rapi.',
    },
    {
        icon: ScanLine,
        title: 'Pelacakan untuk Pelanggan',
        description:
            'Pelanggan memantau progres servis dengan memindai QR pada nota atau melalui halaman lacak publik.',
    },
    {
        icon: ShoppingCart,
        title: 'Kasir / POS',
        description:
            'Transaksi penjualan produk dan checkout servis cepat dengan dukungan berbagai metode pembayaran.',
    },
    {
        icon: Boxes,
        title: 'Manajemen Stok',
        description:
            'Pantau stok produk, peringatan stok menipis, serta pencatatan pemakaian sparepart setiap servis.',
    },
    {
        icon: Users,
        title: 'Peran & Hak Akses',
        description:
            'Pisahkan peran admin, owner, kasir, dan teknisi dengan kontrol akses berbasis peran yang jelas.',
    },
    {
        icon: BarChart3,
        title: 'Laporan Bisnis',
        description:
            'Laporan penjualan, servis, dan stok membantu mengambil keputusan berdasarkan data.',
    },
];

const steps = [
    {
        icon: CircleDot,
        title: 'Catat Servis',
        description:
            'Input data unit, diagnosa awal, dan estimasi selesai langsung tercatat rapi di sistem.',
    },
    {
        icon: Wrench,
        title: 'Kerjakan & Perbarui',
        description:
            'Teknisi memperbarui status pengerjaan dari diagnosa hingga siap diambil.',
    },
    {
        icon: QrCode,
        title: 'Lacak via QR',
        description:
            'Pelanggan memindai QR di nota untuk melihat status tanpa perlu menelepon ke toko.',
    },
];

const techStack = [
    { name: 'PHP 8.4', detail: 'Backend' },
    { name: 'Laravel 13', detail: 'Framework' },
    { name: 'React', detail: 'Frontend' },
    { name: 'Inertia.js', detail: 'SPA Integration' },
    { name: 'TypeScript', detail: 'Frontend' },
    { name: 'Tailwind CSS', detail: 'Styling' },
    { name: 'MySQL', detail: 'Database' },
];

const queuePreview = [
    { invoice: 'SRV-240801-004', item: 'Laptop ASUS', status: 'Proses' },
    { invoice: 'SRV-240801-005', item: 'iPhone 13', status: 'Selesai' },
    { invoice: 'SRV-240801-006', item: 'Printer Canon', status: 'Diagnosa' },
];

const chartHeights = [40, 62, 48, 78, 56, 88, 70];

function queueBadgeVariant(
    status: string,
): 'default' | 'secondary' | 'outline' {
    if (status === 'Proses') {
        return 'default';
    }

    return status === 'Selesai' ? 'secondary' : 'outline';
}

function DashboardPreview() {
    return (
        <div className="relative mx-auto mt-16 max-w-4xl">
            <div
                aria-hidden
                className="absolute -inset-x-8 -top-6 -bottom-10 -z-10 rounded-[2rem] bg-linear-to-r from-indigo-600/15 to-violet-600/15 blur-2xl dark:from-indigo-400/20 dark:to-violet-400/20"
            />
            <Card className="overflow-hidden shadow-2xl">
                <div className="flex items-center justify-between border-b bg-muted/50 px-4 py-2.5">
                    <div className="flex items-center gap-1.5">
                        <span className="size-2.5 rounded-full bg-destructive/70" />
                        <span className="size-2.5 rounded-full bg-amber-400/80" />
                        <span className="size-2.5 rounded-full bg-emerald-400/80" />
                    </div>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <Package className="size-3.5" />
                        sercomp.app/tracking
                    </div>
                    <div className="flex size-2.5 items-center justify-center rounded-full bg-primary text-primary-foreground">
                        <Check className="size-2" />
                    </div>
                </div>

                <div className="grid gap-4 p-4 sm:grid-cols-3">
                    {[
                        { label: 'Servis Aktif', value: '12' },
                        { label: 'Selesai Hari Ini', value: '8' },
                        { label: 'Penjualan Hari Ini', value: 'Rp 4,8 jt' },
                    ].map((stat) => (
                        <div
                            key={stat.label}
                            className="rounded-xl border bg-background p-4"
                        >
                            <p className="text-xs text-muted-foreground">
                                {stat.label}
                            </p>
                            <p className="mt-1 text-2xl font-bold tracking-tight">
                                {stat.value}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="grid gap-4 p-4 sm:grid-cols-2">
                    <div className="rounded-xl border bg-background p-4">
                        <p className="text-xs font-medium text-muted-foreground">
                            Tren Servis Minggu Ini
                        </p>
                        <div className="mt-4 flex h-28 items-end gap-2">
                            {chartHeights.map((height, index) => (
                                <div
                                    key={index}
                                    className={`flex-1 rounded-t-md ${
                                        index === chartHeights.length - 1
                                            ? 'bg-primary'
                                            : 'bg-primary/15'
                                    }`}
                                    style={{ height: `${height}%` }}
                                />
                            ))}
                        </div>
                    </div>

                    <div className="rounded-xl border bg-background p-4">
                        <p className="text-xs font-medium text-muted-foreground">
                            Antrian Servis
                        </p>
                        <div className="mt-3 space-y-2">
                            {queuePreview.map((entry) => (
                                <div
                                    key={entry.invoice}
                                    className="flex items-center justify-between gap-2 rounded-lg bg-muted/60 px-3 py-2"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {entry.item}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {entry.invoice}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={queueBadgeVariant(
                                            entry.status,
                                        )}
                                    >
                                        {entry.status}
                                    </Badge>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    );
}

export default function Welcome() {
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const isDark = resolvedAppearance === 'dark';
    const isMounted = useSyncExternalStore(
        () => () => {},
        () => true,
        () => false,
    );

    return (
        <>
            <Head title="SerComp - Solusi Manajemen Servis & Toko" />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="sticky top-0 z-10 border-b bg-background/80 backdrop-blur">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-3">
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
                            <Button
                                variant="ghost"
                                asChild
                                className="hidden sm:inline-flex"
                            >
                                <Link href={login()}>Masuk</Link>
                            </Button>
                            <Button asChild>
                                <Link href={tracking()}>
                                    Lacak Servis
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </header>

                <main className="flex-1">
                    <section className="relative overflow-hidden">
                        <div
                            aria-hidden
                            className="pointer-events-none absolute inset-0"
                        >
                            <div className="absolute -top-40 left-1/2 h-[420px] w-[820px] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl dark:bg-primary/15" />
                            <div className="absolute top-48 -left-40 h-80 w-80 rounded-full bg-violet-600/10 blur-3xl dark:bg-violet-400/10" />
                            <div className="absolute top-72 -right-40 h-80 w-80 rounded-full bg-indigo-600/10 blur-3xl dark:bg-indigo-400/10" />
                        </div>

                        <div className="mx-auto max-w-6xl px-4 pt-20 pb-4 text-center sm:pt-28">
                            <Badge
                                variant="secondary"
                                className="mb-6 gap-1.5 px-3 py-1"
                            >
                                <Sparkles className="size-3.5 text-primary" />
                                Aplikasi internal manajemen servis & penjualan
                            </Badge>
                            <h1 className="mx-auto max-w-3xl text-4xl font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                                Kelola Servis, Kasir & Stok dalam{' '}
                                <span className="bg-linear-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent dark:from-indigo-400 dark:to-violet-400">
                                    Satu Aplikasi
                                </span>
                            </h1>
                            <p className="mx-auto mt-5 max-w-2xl text-balance text-muted-foreground sm:text-lg">
                                SerComp membantu Anda mencatat servis dari masuk
                                hingga serah terima, mengelola penjualan di
                                kasir, menjaga stok sparepart, dan memberikan
                                pelanggan cara mudah melacak status unitnya.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                                <Button size="lg" asChild>
                                    <Link href={tracking()}>
                                        <ScanLine />
                                        Lacak Status Servis
                                    </Link>
                                </Button>
                                <Button size="lg" variant="outline" asChild>
                                    <a href="#fitur">Lihat Fitur</a>
                                </Button>
                            </div>

                            <DashboardPreview />
                        </div>
                    </section>

                    <section id="cara-kerja" className="py-16 sm:py-20">
                        <div className="mx-auto max-w-6xl px-4">
                            <div className="mx-auto mb-12 max-w-2xl text-center">
                                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                    Cara Kerja SerComp
                                </h2>
                                <p className="mt-3 text-muted-foreground">
                                    Dari unit masuk hingga pelanggan mengambil
                                    kembali unitnya — semua terhubung.
                                </p>
                            </div>
                            <div className="grid gap-4 md:grid-cols-3">
                                {steps.map((step, index) => (
                                    <Card key={step.title} className="relative">
                                        <CardHeader>
                                            <div className="mb-3 flex items-center gap-3">
                                                <span className="flex size-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                                    <step.icon className="size-5" />
                                                </span>
                                                <span className="text-sm font-semibold text-muted-foreground">
                                                    Langkah {index + 1}
                                                </span>
                                            </div>
                                            <CardTitle className="text-lg">
                                                {step.title}
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <CardDescription className="leading-relaxed">
                                                {step.description}
                                            </CardDescription>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section
                        id="fitur"
                        className="border-y bg-muted/40 py-16 sm:py-20"
                    >
                        <div className="mx-auto max-w-6xl px-4">
                            <div className="mx-auto mb-12 max-w-2xl text-center">
                                <Badge variant="outline" className="mb-4">
                                    Fitur Lengkap
                                </Badge>
                                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                    Semua Kebutuhan Operasional Harian
                                </h2>
                                <p className="mt-3 text-muted-foreground">
                                    Satu aplikasi untuk servis, kasir, stok, dan
                                    laporan bisnis toko Anda.
                                </p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {features.map((feature) => (
                                    <Card
                                        key={feature.title}
                                        className="transition duration-200 hover:-translate-y-0.5 hover:shadow-md"
                                    >
                                        <CardHeader>
                                            <span className="mb-2 flex size-10 items-center justify-center rounded-lg bg-linear-to-br from-primary/15 to-violet-600/15 text-primary">
                                                <feature.icon className="size-5" />
                                            </span>
                                            <CardTitle className="text-base">
                                                {feature.title}
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <CardDescription className="text-sm leading-relaxed">
                                                {feature.description}
                                            </CardDescription>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="py-16 sm:py-20">
                        <div className="mx-auto max-w-6xl px-4 text-center">
                            <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                Dibangun dengan Teknologi Modern
                            </h2>
                            <p className="mx-auto mt-3 max-w-2xl text-muted-foreground">
                                Arsitektur web modern yang cepat, aman, dan
                                mudah dikembangkan.
                            </p>
                            <div className="mx-auto mt-10 flex max-w-3xl flex-wrap items-center justify-center gap-3">
                                {techStack.map((tech) => (
                                    <Badge
                                        key={tech.name}
                                        variant="outline"
                                        className="px-4 py-2 text-sm"
                                    >
                                        {tech.name}
                                        <span className="ml-2 text-muted-foreground">
                                            {tech.detail}
                                        </span>
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="px-4 pb-16 sm:pb-20">
                        <div className="mx-auto max-w-6xl">
                            <div className="relative overflow-hidden rounded-3xl bg-linear-to-r from-indigo-600 to-violet-600 px-6 py-14 text-center text-white shadow-lg dark:from-indigo-500 dark:to-violet-600">
                                <div
                                    aria-hidden
                                    className="pointer-events-none absolute -top-24 -right-24 size-64 rounded-full bg-white/10 blur-3xl"
                                />
                                <div
                                    aria-hidden
                                    className="pointer-events-none absolute -bottom-24 -left-24 size-64 rounded-full bg-white/10 blur-3xl"
                                />
                                <div className="relative">
                                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                        Punya Unit Sedang Diservis?
                                    </h2>
                                    <p className="mx-auto mt-3 max-w-xl text-white/80">
                                        Masukkan nomor servis pada nota Anda
                                        untuk melihat status pengerjaan secara
                                        langsung.
                                    </p>
                                    <Button
                                        size="lg"
                                        variant="secondary"
                                        className="mt-6"
                                        asChild
                                    >
                                        <Link href={tracking()}>
                                            Lacak Servis Sekarang
                                            <ArrowRight />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t py-6">
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-3 px-4 sm:flex-row">
                        <div className="flex items-center gap-3">
                            <span className="flex size-7 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <AppLogoIcon className="size-4 fill-current" />
                            </span>
                            <p className="flex items-center gap-2 text-sm font-semibold">
                                SerComp
                            </p>
                            <div className="hidden items-center gap-3 text-sm text-muted-foreground sm:flex">
                                <Link
                                    href={tracking()}
                                    className="transition hover:text-foreground"
                                >
                                    Lacak Servis
                                </Link>
                                <a
                                    href="#fitur"
                                    className="transition hover:text-foreground"
                                >
                                    Fitur
                                </a>
                            </div>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            &copy; {new Date().getFullYear()} SerComp. Semua hak
                            dilindungi.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
