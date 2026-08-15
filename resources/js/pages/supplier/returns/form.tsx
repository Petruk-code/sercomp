import { Head, Link, router, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index, store } from '@/routes/supplier/returns';
import type { Purchase, PurchaseItem, PurchaseReturnResultType } from '@/types';

type FormLine = {
    purchase_item_id: number;
    name: string;
    available: number;
    quantity: number;
    price: number;
};

type Props = {
    purchase: Purchase & { items: PurchaseItem[] };
    alreadyReturned: Record<string, number>;
    resultTypeLabels: Record<string, string>;
};

export default function PurchaseReturnForm({
    purchase,
    alreadyReturned,
    resultTypeLabels,
}: Props) {
    const lines: FormLine[] = (purchase.items ?? []).map((item) => ({
        purchase_item_id: item.id,
        name: item.name,
        available: item.quantity - (alreadyReturned[item.id] ?? 0),
        quantity: 0,
        price: Number(item.purchase_price),
    }));

    const { data, setData, processing, errors } = useForm({
        return_date: new Date().toISOString().slice(0, 10),
        result_type: 'replacement' as PurchaseReturnResultType,
        reason: '',
        notes: '',
        items: lines,
    });

    const subtotal = data.items.reduce(
        (sum, line) => sum + line.price * line.quantity,
        0,
    );
    const total = subtotal;

    function updateLine(index: number, patch: Partial<FormLine>) {
        setData(
            'items',
            data.items.map((line, i) =>
                i === index ? { ...line, ...patch } : line,
            ),
        );
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const payload = {
            ...data,
            items: data.items
                .filter((line) => line.quantity > 0)
                .map((line) => ({
                    purchase_item_id: line.purchase_item_id,
                    quantity: line.quantity,
                    price: line.price,
                })),
        };

        router.post(store.url(purchase.id), payload, {
            preserveScroll: true,
        });
    }

    const linesWithStock = lines.filter((line) => line.available > 0);

    return (
        <>
            <Head title="Buat Retur" />

            <PageHeader
                title="Buat Retur"
                description={`Retur untuk ${purchase.purchase_number} — ${purchase.supplier?.name ?? 'Tanpa supplier'} (${formatDate(purchase.purchase_date)}).`}
            />

            <form onSubmit={submit} className="space-y-6 rounded-xl border p-6">
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="grid gap-2">
                        <Label htmlFor="return_date">Tanggal Retur</Label>
                        <Input
                            id="return_date"
                            type="date"
                            value={data.return_date}
                            onChange={(event) =>
                                setData('return_date', event.target.value)
                            }
                            required
                        />
                        <InputError message={errors.return_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Hasil Retur</Label>
                        <select
                            value={data.result_type}
                            onChange={(event) =>
                                setData(
                                    'result_type',
                                    event.target
                                        .value as PurchaseReturnResultType,
                                )
                            }
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                        >
                            {Object.entries(resultTypeLabels).map(
                                ([key, label]) => (
                                    <option key={key} value={key}>
                                        {label}
                                    </option>
                                ),
                            )}
                        </select>
                        <InputError message={errors.result_type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="reason">Alasan / Kondisi</Label>
                        <Input
                            id="reason"
                            value={data.reason}
                            onChange={(event) =>
                                setData('reason', event.target.value)
                            }
                            placeholder="mis. rusak saat kirim"
                        />
                        <InputError message={errors.reason} />
                    </div>
                </div>

                <div className="rounded-lg border">
                    <div className="flex items-center justify-between border-b px-4 py-3">
                        <h3 className="font-semibold">Item Retur</h3>
                        <p className="text-sm text-muted-foreground">
                            Isi jumlah retur (0 = tidak diretur).
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-xs text-muted-foreground">
                                    <th className="px-4 py-3">Item</th>
                                    <th className="px-4 py-3 text-right">
                                        Dibeli
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Sisa
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Harga
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Qty Retur
                                    </th>
                                    <th className="px-4 py-3 text-right">
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {linesWithStock.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            Semua item sudah diretur seluruhnya.
                                        </td>
                                    </tr>
                                )}
                                {data.items.map((line, index) => {
                                    if (line.available <= 0) {
                                        return null;
                                    }

                                    return (
                                        <tr
                                            key={line.purchase_item_id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {line.name}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {line.available +
                                                    (alreadyReturned[
                                                        line.purchase_item_id
                                                    ] ?? 0)}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {line.available}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    className="ml-auto w-32"
                                                    value={line.price}
                                                    onChange={(event) =>
                                                        updateLine(index, {
                                                            price: Number(
                                                                event.target
                                                                    .value,
                                                            ),
                                                        })
                                                    }
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    max={line.available}
                                                    step="1"
                                                    className="ml-auto w-24"
                                                    value={line.quantity}
                                                    onChange={(event) =>
                                                        updateLine(index, {
                                                            quantity: Math.min(
                                                                Number(
                                                                    event.target
                                                                        .value,
                                                                ),
                                                                line.available,
                                                            ),
                                                        })
                                                    }
                                                />
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                {formatCurrency(
                                                    line.price * line.quantity,
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    {errors.items && (
                        <p className="px-4 py-2 text-sm text-destructive">
                            {errors.items}
                        </p>
                    )}
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
                            placeholder="Catatan retur (opsional)"
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="w-full max-w-xs space-y-2 text-sm">
                        <div className="flex justify-between border-t pt-2 text-base font-semibold">
                            <dt>Total Retur</dt>
                            <dd>{formatCurrency(total)}</dd>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {data.result_type === 'debt_deduction'
                                ? 'Total ini akan memotong hutang pembelian terkait.'
                                : 'Stock barang akan dikurangi saat retur diproses.'}
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <Button
                        type="submit"
                        disabled={processing || subtotal <= 0}
                    >
                        {processing ? 'Menyimpan...' : 'Simpan Retur'}
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href={index().url}>Batal</Link>
                    </Button>
                </div>
            </form>
        </>
    );
}

PurchaseReturnForm.layout = {
    breadcrumbs: [
        { title: 'Retur', href: index().url },
        { title: 'Buat Retur', href: index().url },
    ],
};
