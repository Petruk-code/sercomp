import { Head, Link, router } from '@inertiajs/react';
import { Download, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import SupplierController from '@/actions/App/Http/Controllers/Master/SupplierController';
import { DeleteButton } from '@/components/delete-button';
import { ImportCsvDialog } from '@/components/import-csv-dialog';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    create,
    exportMethod,
    importMethod,
    index,
} from '@/routes/master/suppliers';
import type { PaginatedData, Supplier } from '@/types';

type Props = {
    suppliers: PaginatedData<Supplier>;
    filters: { search?: string };
};

export default function SuppliersIndex({ suppliers, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    function applyFilters() {
        router.get(
            index().url,
            { search: search || undefined },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Supplier" />

            <PageHeader title="Supplier" description="Kelola data supplier.">
                <Button variant="outline" asChild>
                    <a
                        href={
                            exportMethod({
                                query: { search: search || undefined },
                            }).url
                        }
                    >
                        <Download />
                        Export CSV
                    </a>
                </Button>
                <ImportCsvDialog action={importMethod().url} />
                <Button asChild>
                    <Link href={create().url}>
                        <Plus />
                        Tambah Supplier
                    </Link>
                </Button>
            </PageHeader>

            <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari supplier..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && applyFilters()
                        }
                    />
                </div>
                <Button variant="secondary" onClick={applyFilters}>
                    Terapkan
                </Button>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">Nama</th>
                            <th className="px-4 py-3">Kontak</th>
                            <th className="px-4 py-3">Telepon</th>
                            <th className="px-4 py-3">Email</th>
                            <th className="px-4 py-3">Produk</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {suppliers.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={6}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada supplier.
                                </td>
                            </tr>
                        )}
                        {suppliers.data.map((supplier) => (
                            <tr
                                key={supplier.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3 font-medium">
                                    {supplier.name}
                                </td>
                                <td className="px-4 py-3">
                                    {supplier.contact_person ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {supplier.phone ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {supplier.email ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {supplier.products_count ?? 0}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={SupplierController.edit.url(
                                                    supplier.id,
                                                )}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <DeleteButton
                                            action={
                                                SupplierController.destroy.form(
                                                    supplier.id,
                                                ).action
                                            }
                                        />
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination data={suppliers} />
        </>
    );
}

SuppliersIndex.layout = {
    breadcrumbs: [
        { title: 'Master Data', href: index().url },
        { title: 'Supplier', href: index().url },
    ],
};
