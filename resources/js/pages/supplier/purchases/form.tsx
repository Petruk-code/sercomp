import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import PurchaseController from '@/actions/App/Http/Controllers/Supplier/PurchaseController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/lib/utils';
import { index } from '@/routes/supplier/purchases';
import type { Product, Purchase, Supplier } from '@/types';

type FormItem = {
    product_id: number | null;
    name: string;
    quantity: number;
    purchase_price: number;
};

type Props = {
    purchase: (Purchase & { items: Purchase['items'] }) | null;
    suppliers: Supplier[];
    products: Product[];
};

export default function PurchaseForm({ purchase, suppliers, products }: Props) {
    const initialItems: FormItem[] =
        purchase?.items?.map((item) => ({
            product_id: item.product_id,
            name: item.name,
            quantity: item.quantity,
            purchase_price: Number(item.purchase_price),
        })) ?? [];

    const { data, setData, processing, errors } = useForm({
        supplier_id: purchase?.supplier_id?.toString() ?? '',
        purchase_date:
            purchase?.purchase_date ?? new Date().toISOString().slice(0, 10),
        due_date: purchase?.due_date ?? '',
        discount: Number(purchase?.discount ?? 0),
        notes: purchase?.notes ?? '',
        items: initialItems,
    });

    const subtotal = data.items.reduce(
        (sum, item) => sum + item.purchase_price * item.quantity,
        0,
    );
    const total = Math.max(subtotal - data.discount, 0);

    function addItem() {
        setData('items', [
            ...data.items,
            { product_id: null, name: '', quantity: 1, purchase_price: 0 },
        ]);
    }

    function updateItem(index: number, patch: Partial<FormItem>) {
        setData(
            'items',
            data.items.map((item, i) =>
                i === index ? { ...item, ...patch } : item,
            ),
        );
    }

    function removeItem(index: number) {
        setData(
            'items',
            data.items.filter((_, i) => i !== index),
        );
    }

    function pickProduct(index: number, productId: string) {
        const id = productId === '' ? null : Number(productId);
        const product = id === null ? null : products.find((p) => p.id === id);

        updateItem(index, {
            product_id: id,
            name: product?.name ?? '',
            purchase_price: product ? Number(product.purchase_price) : 0,
        });
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const payload = {
            ...data,
            supplier_id: Number(data.supplier_id),
        };

        const options = {
            preserveScroll: true,
        };

        if (purchase) {
            router.put(
                PurchaseController.update.url(purchase.id),
                payload,
                options,
            );
        } else {
            router.post(PurchaseController.store.url(), payload, options);
        }
    }

    return (
        <>
            <Head title={purchase ? 'Edit Pembelian' : 'Catat Pembelian'} />

            <PageHeader
                title={purchase ? 'Edit Pembelian' : 'Catat Pembelian'}
                description={
                    purchase
                        ? `Memperbarui ${purchase.purchase_number}.`
                        : 'Catat pembelian barang dari supplier.'
                }
            />

            <form onSubmit={submit} className="space-y-6 rounded-xl border p-6">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="grid gap-2">
                        <Label htmlFor="supplier_id">Supplier</Label>
                        <select
                            id="supplier_id"
                            value={data.supplier_id}
                            onChange={(event) =>
                                setData('supplier_id', event.target.value)
                            }
                            required
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            <option value="">Pilih supplier...</option>
                            {suppliers.map((supplier) => (
                                <option key={supplier.id} value={supplier.id}>
                                    {supplier.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.supplier_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="purchase_date">Tanggal Pembelian</Label>
                        <Input
                            id="purchase_date"
                            type="date"
                            value={data.purchase_date}
                            onChange={(event) =>
                                setData('purchase_date', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.purchase_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="due_date">Jatuh Tempo (tempo)</Label>
                        <Input
                            id="due_date"
                            type="date"
                            value={data.due_date}
                            onChange={(event) =>
                                setData('due_date', event.target.value)
                            }
                        />
                        <InputError message={errors.due_date} />
                    </div>
                </div>

                <div className="rounded-lg border">
                    <div className="flex items-center justify-between border-b px-4 py-3">
                        <h3 className="font-semibold">Item Pembelian</h3>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addItem}
                        >
                            <Plus />
                            Tambah Item
                        </Button>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-xs text-muted-foreground">
                                    <th className="px-4 py-3">Produk</th>
                                    <th className="px-4 py-3">Qty</th>
                                    <th className="px-4 py-3 text-right">
                                        Harga Beli
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Subtotal
                                    </th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {data.items.map((item, index) => (
                                    <tr
                                        key={index}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            <select
                                                value={item.product_id ?? ''}
                                                onChange={(event) =>
                                                    pickProduct(
                                                        index,
                                                        event.target.value,
                                                    )
                                                }
                                                className="h-9 w-full min-w-40 rounded-md border border-input bg-transparent px-3 text-sm"
                                            >
                                                <option value="">
                                                    Umum (ketik nama)
                                                </option>
                                                {products.map((product) => (
                                                    <option
                                                        key={product.id}
                                                        value={product.id}
                                                    >
                                                        {product.name} · stok{' '}
                                                        {product.stock}
                                                    </option>
                                                ))}
                                            </select>
                                            <Input
                                                className="mt-2"
                                                value={item.name}
                                                placeholder="Nama barang"
                                                onChange={(event) =>
                                                    updateItem(index, {
                                                        name: event.target
                                                            .value,
                                                    })
                                                }
                                                required={
                                                    item.product_id === null
                                                }
                                            />
                                            {errors[`items.${index}.name`] && (
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.name`
                                                        ]
                                                    }
                                                />
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Input
                                                type="number"
                                                min="1"
                                                step="1"
                                                className="w-24"
                                                value={item.quantity}
                                                onChange={(event) =>
                                                    updateItem(index, {
                                                        quantity: Number(
                                                            event.target.value,
                                                        ),
                                                    })
                                                }
                                            />
                                        </td>
                                        <td className="px-4 py-3">
                                            <Input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                className="w-32"
                                                value={item.purchase_price}
                                                onChange={(event) =>
                                                    updateItem(index, {
                                                        purchase_price: Number(
                                                            event.target.value,
                                                        ),
                                                    })
                                                }
                                            />
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatCurrency(
                                                item.purchase_price *
                                                    item.quantity,
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive"
                                                onClick={() =>
                                                    removeItem(index)
                                                }
                                                disabled={
                                                    data.items.length === 1
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                                {data.items.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            Belum ada item.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="grid max-w-sm flex-1 gap-2">
                        <Label htmlFor="notes">Catatan</Label>
                        <textarea
                            id="notes"
                            value={data.notes}
                            onChange={(event) =>
                                setData('notes', event.target.value)
                            }
                            className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                            placeholder="Catatan pembelian (opsional)"
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="w-full max-w-xs space-y-2 text-sm">
                        <div className="flex justify-between">
                            <dt className="text-muted-foreground">Subtotal</dt>
                            <dd>{formatCurrency(subtotal)}</dd>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="discount">Diskon (Rp)</Label>
                            <Input
                                id="discount"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.discount}
                                onChange={(event) =>
                                    setData(
                                        'discount',
                                        Number(event.target.value),
                                    )
                                }
                            />
                            <InputError message={errors.discount} />
                        </div>
                        <div className="flex justify-between border-t pt-2 text-base font-semibold">
                            <dt>Total</dt>
                            <dd>{formatCurrency(total)}</dd>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <Button type="submit" disabled={processing}>
                        {processing
                            ? 'Menyimpan...'
                            : purchase
                              ? 'Simpan Perubahan'
                              : 'Simpan Pembelian'}
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href={index().url}>Batal</Link>
                    </Button>
                </div>
            </form>
        </>
    );
}

PurchaseForm.layout = {
    breadcrumbs: [
        { title: 'Pembelian', href: index().url },
        { title: 'Daftar Pembelian', href: index().url },
    ],
};
