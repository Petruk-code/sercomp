import { Head, Link, router } from '@inertiajs/react';
import { Download, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import CustomerController from '@/actions/App/Http/Controllers/Master/CustomerController';
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
} from '@/routes/master/customers';
import type { Customer, PaginatedData } from '@/types';

type Props = {
    customers: PaginatedData<Customer>;
    filters: { search?: string };
};

export default function CustomersIndex({ customers, filters }: Props) {
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
            <Head title="Pelanggan" />

            <PageHeader title="Pelanggan" description="Kelola data pelanggan.">
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
                        Tambah Pelanggan
                    </Link>
                </Button>
            </PageHeader>

            <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari pelanggan..."
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
                            <th className="px-4 py-3">Telepon</th>
                            <th className="px-4 py-3">Email</th>
                            <th className="px-4 py-3">Servis</th>
                            <th className="px-4 py-3">Transaksi</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {customers.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={6}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada pelanggan.
                                </td>
                            </tr>
                        )}
                        {customers.data.map((customer) => (
                            <tr
                                key={customer.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3 font-medium">
                                    {customer.name}
                                </td>
                                <td className="px-4 py-3">
                                    {customer.phone ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {customer.email ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {customer.service_orders_count ?? 0}
                                </td>
                                <td className="px-4 py-3">
                                    {customer.sales_count ?? 0}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={CustomerController.show.url(
                                                    customer.id,
                                                )}
                                            >
                                                Detail
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={CustomerController.edit.url(
                                                    customer.id,
                                                )}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <DeleteButton
                                            action={
                                                CustomerController.destroy.form(
                                                    customer.id,
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

            <Pagination data={customers} />
        </>
    );
}

CustomersIndex.layout = {
    breadcrumbs: [
        { title: 'Master Data', href: index().url },
        { title: 'Pelanggan', href: index().url },
    ],
};
