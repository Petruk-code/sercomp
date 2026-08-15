import { Head, Link, router } from '@inertiajs/react';
import { Plus, Printer, Search } from 'lucide-react';
import { useState } from 'react';
import ProductController from '@/actions/App/Http/Controllers/Master/ProductController';
import { DeleteButton } from '@/components/delete-button';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatCurrency } from '@/lib/utils';
import { barcode, create, index } from '@/routes/master/products';
import type { PaginatedData, Product } from '@/types';

type Props = {
    products: PaginatedData<Product>;
    categories: { id: number; name: string }[];
    filters: { search?: string; category_id?: string; low_stock?: boolean };
};

export default function ProductsIndex({
    products,
    categories,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [categoryId, setCategoryId] = useState(filters.category_id ?? 'all');
    const [lowStock, setLowStock] = useState(filters.low_stock ?? false);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    const pageIds = products.data.map((product) => product.id);
    const allSelected =
        pageIds.length > 0 && pageIds.every((id) => selectedIds.includes(id));

    function toggleAll(checked: boolean) {
        setSelectedIds(
            checked
                ? [...new Set([...selectedIds, ...pageIds])]
                : selectedIds.filter((id) => !pageIds.includes(id)),
        );
    }

    function toggleOne(id: number, checked: boolean) {
        setSelectedIds(
            checked
                ? [...selectedIds, id]
                : selectedIds.filter((selected) => selected !== id),
        );
    }

    function applyFilters() {
        router.get(
            index().url,
            {
                search: search || undefined,
                category_id: categoryId === 'all' ? undefined : categoryId,
                low_stock: lowStock ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Produk" />

            <PageHeader
                title="Produk"
                description="Kelola stok dan harga produk."
            >
                {selectedIds.length > 0 && (
                    <Button variant="outline" asChild>
                        <Link
                            href={`${barcode().url}?ids=${selectedIds.join(',')}`}
                            target="_blank"
                        >
                            <Printer />
                            Cetak Barcode ({selectedIds.length})
                        </Link>
                    </Button>
                )}
                <Button asChild>
                    <Link href={create().url}>
                        <Plus />
                        Tambah Produk
                    </Link>
                </Button>
            </PageHeader>

            <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari produk..."
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
                <label className="flex items-center gap-2 text-sm">
                    <Checkbox
                        checked={lowStock}
                        onCheckedChange={(checked) =>
                            setLowStock(checked === true)
                        }
                    />
                    Stok menipis
                </label>
                <Button variant="secondary" onClick={applyFilters}>
                    Terapkan
                </Button>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="w-10 px-4 py-3">
                                <Checkbox
                                    checked={allSelected}
                                    onCheckedChange={(checked) =>
                                        toggleAll(checked === true)
                                    }
                                    aria-label="Pilih semua"
                                />
                            </th>
                            <th className="px-4 py-3">Nama</th>
                            <th className="px-4 py-3">Kategori</th>
                            <th className="px-4 py-3">SKU</th>
                            <th className="px-4 py-3">Barcode</th>
                            <th className="px-4 py-3">Harga Jual</th>
                            <th className="px-4 py-3">Stok</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {products.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={8}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada produk.
                                </td>
                            </tr>
                        )}
                        {products.data.map((product) => (
                            <tr
                                key={product.id}
                                className="border-b last:border-0"
                            >
                                <td className="w-10 px-4 py-3">
                                    <Checkbox
                                        checked={selectedIds.includes(
                                            product.id,
                                        )}
                                        onCheckedChange={(checked) =>
                                            toggleOne(
                                                product.id,
                                                checked === true,
                                            )
                                        }
                                        aria-label={`Pilih ${product.name}`}
                                    />
                                </td>
                                <td className="px-4 py-3 font-medium">
                                    {product.name}
                                </td>
                                <td className="px-4 py-3">
                                    {product.category?.name ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {product.sku ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {product.barcode ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {formatCurrency(product.selling_price)}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-2">
                                        {product.stock}
                                        {product.stock <= product.min_stock && (
                                            <Badge variant="destructive">
                                                Stok menipis
                                            </Badge>
                                        )}
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={ProductController.edit.url(
                                                    product.id,
                                                )}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={`${barcode().url}?ids=${product.id}`}
                                            >
                                                <Printer />
                                            </Link>
                                        </Button>
                                        <DeleteButton
                                            action={
                                                ProductController.destroy.form(
                                                    product.id,
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

            <Pagination data={products} />
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [
        { title: 'Master Data', href: index().url },
        { title: 'Produk', href: index().url },
    ],
};
