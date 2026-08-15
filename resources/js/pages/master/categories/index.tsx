import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { useState } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/Master/CategoryController';
import { DeleteButton } from '@/components/delete-button';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { create, index } from '@/routes/master/categories';
import type { Category, PaginatedData } from '@/types';

type Props = {
    categories: PaginatedData<Category>;
    filters: { search?: string; type?: string };
    types: string[];
};

export default function CategoriesIndex({ categories, filters, types }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type ?? 'all');

    function applyFilters() {
        router.get(
            index().url,
            {
                search: search || undefined,
                type: type === 'all' ? undefined : type,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Kategori" />

            <PageHeader
                title="Kategori"
                description="Kelola kategori produk dan jasa servis."
            >
                <Button asChild>
                    <Link href={create().url}>
                        <Plus />
                        Tambah Kategori
                    </Link>
                </Button>
            </PageHeader>

            <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari kategori..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && applyFilters()
                        }
                    />
                </div>
                <Select
                    value={type}
                    onValueChange={(value) => {
                        setType(value);
                    }}
                >
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="Semua tipe" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua tipe</SelectItem>
                        {types.map((typeOption) => (
                            <SelectItem key={typeOption} value={typeOption}>
                                {typeOption === 'product' ? 'Produk' : 'Jasa'}
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
                            <th className="px-4 py-3">Tipe</th>
                            <th className="px-4 py-3">Produk</th>
                            <th className="px-4 py-3">Jasa</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {categories.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada kategori.
                                </td>
                            </tr>
                        )}
                        {categories.data.map((category) => (
                            <tr
                                key={category.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3 font-medium">
                                    {category.name}
                                </td>
                                <td className="px-4 py-3">
                                    <Badge
                                        variant={
                                            category.type === 'product'
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {category.type === 'product'
                                            ? 'Produk'
                                            : 'Jasa'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3">
                                    {category.products_count ?? 0}
                                </td>
                                <td className="px-4 py-3">
                                    {category.service_catalogs_count ?? 0}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={CategoryController.edit.url(
                                                    category.id,
                                                )}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <DeleteButton
                                            action={
                                                CategoryController.destroy.form(
                                                    category.id,
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

            <Pagination data={categories} />
        </>
    );
}

CategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Master Data', href: index().url }],
};
