import { Form, Head, Link } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/Master/ProductController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/master/products';
import type { Product } from '@/types';

type Props = {
    product: Product | null;
    categories: { id: number; name: string }[];
    suppliers: { id: number; name: string }[];
};

export default function ProductForm({ product, categories, suppliers }: Props) {
    return (
        <>
            <Head title={product ? 'Edit Produk' : 'Tambah Produk'} />

            <PageHeader
                title={product ? 'Edit Produk' : 'Tambah Produk'}
                description={
                    product
                        ? `Memperbarui produk ${product.name}.`
                        : 'Buat produk baru untuk stok dan penjualan.'
                }
            />

            <Form
                {...(product
                    ? ProductController.update.form(product.id)
                    : ProductController.store.form())}
                className="max-w-2xl space-y-6 rounded-xl border p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={product?.name ?? ''}
                                placeholder="cth. Laptop Acer Swift 3"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="category_id">Kategori</Label>
                                <select
                                    id="category_id"
                                    name="category_id"
                                    defaultValue={product?.category_id ?? ''}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                >
                                    <option value="">Tanpa kategori</option>
                                    {categories.map((category) => (
                                        <option
                                            key={category.id}
                                            value={category.id}
                                        >
                                            {category.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.category_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="supplier_id">Supplier</Label>
                                <select
                                    id="supplier_id"
                                    name="supplier_id"
                                    defaultValue={product?.supplier_id ?? ''}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                >
                                    <option value="">Tanpa supplier</option>
                                    {suppliers.map((supplier) => (
                                        <option
                                            key={supplier.id}
                                            value={supplier.id}
                                        >
                                            {supplier.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.supplier_id} />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="sku">SKU</Label>
                                <Input
                                    id="sku"
                                    name="sku"
                                    defaultValue={product?.sku ?? ''}
                                    placeholder="cth. ACC-001"
                                />
                                <InputError message={errors.sku} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="barcode">Barcode</Label>
                                <Input
                                    id="barcode"
                                    name="barcode"
                                    defaultValue={product?.barcode ?? ''}
                                    placeholder="cth. 8991234567890"
                                />
                                <InputError message={errors.barcode} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="unit">Satuan</Label>
                                <Input
                                    id="unit"
                                    name="unit"
                                    defaultValue={product?.unit ?? 'pcs'}
                                    placeholder="cth. pcs, unit, set"
                                />
                                <InputError message={errors.unit} />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="purchase_price">
                                    Harga Beli
                                </Label>
                                <Input
                                    id="purchase_price"
                                    name="purchase_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    defaultValue={product?.purchase_price ?? ''}
                                />
                                <InputError message={errors.purchase_price} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="selling_price">
                                    Harga Jual
                                </Label>
                                <Input
                                    id="selling_price"
                                    name="selling_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    defaultValue={product?.selling_price ?? ''}
                                />
                                <InputError message={errors.selling_price} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="stock">Stok</Label>
                                <Input
                                    id="stock"
                                    name="stock"
                                    type="number"
                                    min="0"
                                    defaultValue={product?.stock ?? 0}
                                />
                                <InputError message={errors.stock} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="min_stock">Stok Minimum</Label>
                                <Input
                                    id="min_stock"
                                    name="min_stock"
                                    type="number"
                                    min="0"
                                    defaultValue={product?.min_stock ?? 0}
                                />
                                <InputError message={errors.min_stock} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <textarea
                                id="description"
                                name="description"
                                defaultValue={product?.description ?? ''}
                                className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                placeholder="Deskripsi singkat (opsional)"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={processing}>
                                {product ? 'Simpan Perubahan' : 'Simpan Produk'}
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={index().url}>Batal</Link>
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

ProductForm.layout = {
    breadcrumbs: [
        { title: 'Master Data', href: index().url },
        { title: 'Produk', href: index().url },
    ],
};
