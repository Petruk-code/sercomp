import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { useState } from 'react';
import ServiceCatalogController from '@/actions/App/Http/Controllers/Master/ServiceCatalogController';
import { DeleteButton } from '@/components/delete-button';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatCurrency } from '@/lib/utils';
import { create, index } from '@/routes/master/services';
import type { PaginatedData, ServiceCatalog } from '@/types';

type Props = {
    services: PaginatedData<ServiceCatalog>;
    categories: { id: number; name: string }[];
    filters: { search?: string; category_id?: string };
};

export default function ServicesIndex({
    services,
    categories,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [categoryId, setCategoryId] = useState(filters.category_id ?? 'all');

    function applyFilters() {
        router.get(
            index().url,
            {
                search: search || undefined,
                category_id: categoryId === 'all' ? undefined : categoryId,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Jasa Servis" />

            <PageHeader
                title="Jasa Servis"
                description="Kelola katalog jasa servis."
            >
                <Button asChild>
                    <Link href={create().url}>
                        <Plus />
                        Tambah Jasa
                    </Link>
                </Button>
            </PageHeader>

            <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari jasa..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && applyFilters()
                        }
                    />
                </div>
                <Select value={categoryId} onValueChange={setCategoryId}>
                    <SelectTrigger className="w-44">
                        <SelectValue placeholder="Semua kategori" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua kategori</SelectItem>
                        {categories.map((category) => (
                            <SelectItem
                                key={category.id}
                                value={String(category.id)}
                            >
                                {category.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Button variant="secondary" onClick={applyFilters}>
                    Terapkan
                </Button>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">Nama</th>
                            <th className="px-4 py-3">Kategori</th>
                            <th className="px-4 py-3">Estimasi</th>
                            <th className="px-4 py-3">Biaya</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {services.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada jasa servis.
                                </td>
                            </tr>
                        )}
                        {services.data.map((service) => (
                            <tr
                                key={service.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3 font-medium">
                                    {service.name}
                                </td>
                                <td className="px-4 py-3">
                                    {service.category?.name ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {service.estimated_minutes
                                        ? `${service.estimated_minutes} mnt`
                                        : '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {formatCurrency(service.price)}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={ServiceCatalogController.edit.url(
                                                    service.id,
                                                )}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <DeleteButton
                                            action={
                                                ServiceCatalogController.destroy.form(
                                                    service.id,
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

            <Pagination data={services} />
        </>
    );
}

ServicesIndex.layout = {
    breadcrumbs: [
        { title: 'Master Data', href: index().url },
        { title: 'Jasa Servis', href: index().url },
    ],
};
