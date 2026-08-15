import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Lock, RefreshCw, TriangleAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Akses Ditolak',
        description: 'Anda tidak memiliki hak akses untuk membuka halaman ini.',
    },
    404: {
        title: 'Halaman Tidak Ditemukan',
        description: 'Halaman yang Anda cari tidak ada atau telah dipindahkan.',
    },
    419: {
        title: 'Sesi Berakhir',
        description: 'Sesi Anda telah berakhir. Silakan muat ulang halaman.',
    },
    429: {
        title: 'Terlalu Banyak Permintaan',
        description:
            'Anda melakukan terlalu banyak permintaan. Silakan coba lagi.',
    },
    500: {
        title: 'Terjadi Kesalahan',
        description: 'Terjadi kesalahan pada server. Silakan coba lagi.',
    },
    503: {
        title: 'Layanan Tidak Tersedia',
        description:
            'Layanan sedang dalam pemeliharaan. Silakan coba lagi nanti.',
    },
};

export default function ErrorPage({ status }: { status: number }) {
    const { url } = usePage();

    const message = messages[status] ?? messages[500];

    const Icon =
        status === 403 ? Lock : status === 404 ? TriangleAlert : RefreshCw;

    return (
        <>
            <Head title={`${status}`} />

            <div className="flex min-h-screen items-center justify-center bg-background p-4">
                <Card className="max-w-md text-center">
                    <CardHeader>
                        <div className="mx-auto mb-2 flex size-16 items-center justify-center rounded-full bg-muted">
                            <Icon className="size-8 text-muted-foreground" />
                        </div>
                        <CardTitle className="text-5xl font-bold">
                            {status}
                        </CardTitle>
                        <CardTitle className="text-xl">
                            {message.title}
                        </CardTitle>
                        <CardDescription>{message.description}</CardDescription>
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground">
                        <p className="break-all">{url}</p>
                    </CardContent>
                    <CardFooter className="justify-center">
                        <Button asChild>
                            <Link href="/">
                                <ArrowLeft />
                                Kembali ke Beranda
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </>
    );
}
