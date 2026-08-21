import { Head, Link } from '@inertiajs/react';
import { Building2, MapPin, Pencil, Phone, Plus } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create } from '@/routes/stores';
import type { Store } from '@/types';

type Props = {
    stores: (Store & { users_count: number; products_count: number })[];
    activeStoreId: number | null;
};

export default function StoresIndex({ stores, activeStoreId }: Props) {
    return (
        <>
            <Head title="Cabang" />

            <PageHeader
                title="Cabang"
                description="Kelola daftar cabang toko. Data stok, pelanggan, dan transaksi terpisah untuk setiap cabang."
            />

            <div className="mb-4 flex justify-end">
                <Button variant="secondary" asChild>
                    <Link href={create().url}>
                        <Plus />
                        Tambah Cabang
                    </Link>
                </Button>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="w-10 px-4 py-3"></th>
                            <th className="px-4 py-3">Nama</th>
                            <th className="px-4 py-3">Kontak</th>
                            <th className="px-4 py-3 text-center">Pengguna</th>
                            <th className="px-4 py-3 text-center">Produk</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {stores.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada cabang.
                                </td>
                            </tr>
                        )}
                        {stores.map((store) => (
                            <tr
                                key={store.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3">
                                    {store.id === activeStoreId && (
                                        <Building2 className="size-4 text-primary" />
                                    )}
                                </td>
                                <td className="px-4 py-3 font-medium">
                                    {store.name}
                                    {store.address && (
                                        <span className="mt-0.5 flex items-center gap-1 text-xs font-normal text-muted-foreground">
                                            <MapPin className="size-3" />
                                            {store.address}
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    {store.phone ? (
                                        <span className="flex items-center gap-1">
                                            <Phone className="size-3.5 text-muted-foreground" />
                                            {store.phone}
                                        </span>
                                    ) : (
                                        '-'
                                    )}
                                </td>
                                <td className="px-4 py-3 text-center">
                                    {store.users_count}
                                </td>
                                <td className="px-4 py-3 text-center">
                                    {store.products_count}
                                </td>
                                <td className="px-4 py-3">
                                    {store.is_active ? (
                                        <Badge variant="secondary">Aktif</Badge>
                                    ) : (
                                        <Badge variant="outline">
                                            Nonaktif
                                        </Badge>
                                    )}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link href={`/stores/${store.id}/edit`}>
                                            <Pencil />
                                            Edit
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}
