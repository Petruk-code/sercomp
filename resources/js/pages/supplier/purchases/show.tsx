import { Head, Link, useForm } from '@inertiajs/react';
import { HandCoins, Pencil, Undo2 } from 'lucide-react';
import { useState } from 'react';
import PurchaseController from '@/actions/App/Http/Controllers/Supplier/PurchaseController';
import PurchaseReturnController from '@/actions/App/Http/Controllers/Supplier/PurchaseReturnController';
import { PaymentStatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index } from '@/routes/supplier/purchases';
import type { PaymentMethod, Purchase } from '@/types';

type Props = {
    purchase: Purchase;
    paymentMethodLabels: Record<string, string>;
    paymentStatusLabels: Record<string, string>;
};

export default function PurchaseShow({
    purchase,
    paymentMethodLabels,
    paymentStatusLabels,
}: Props) {
    const [open, setOpen] = useState(false);

    const remaining = Math.max(
        Number(purchase.total) - Number(purchase.paid_amount),
        0,
    );

    const { data, setData, post, processing, errors, reset } = useForm({
        amount: remaining,
        payment_method: 'cash' as PaymentMethod,
        paid_at: new Date().toISOString().slice(0, 16),
        notes: '',
    });

    function submitPayment(event: React.FormEvent) {
        event.preventDefault();

        post(PurchaseController.pay.url(purchase.id), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    }

    return (
        <>
            <Head title={purchase.purchase_number} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">
                        {purchase.purchase_number}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {formatDate(purchase.purchase_date)} ·{' '}
                        {purchase.supplier?.name ?? 'Tanpa supplier'}
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    {(purchase.items?.length ?? 0) > 0 && (
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={PurchaseReturnController.create.url(
                                    purchase.id,
                                )}
                            >
                                <Undo2 />
                                Retur
                            </Link>
                        </Button>
                    )}
                    <Button variant="outline" size="sm" asChild>
                        <Link href={PurchaseController.edit.url(purchase.id)}>
                            <Pencil />
                            Edit
                        </Link>
                    </Button>
                    {purchase.payment_status !== 'paid' && remaining > 0 && (
                        <Dialog open={open} onOpenChange={setOpen}>
                            <DialogTrigger asChild>
                                <Button size="sm">
                                    <HandCoins />
                                    Bayar
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>
                                        Pembayaran {purchase.purchase_number}
                                    </DialogTitle>
                                    <DialogDescription>
                                        Sisa hutang: {formatCurrency(remaining)}
                                    </DialogDescription>
                                </DialogHeader>
                                <form
                                    onSubmit={submitPayment}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="amount">
                                            Jumlah (Rp)
                                        </Label>
                                        <Input
                                            id="amount"
                                            type="number"
                                            min="0.01"
                                            max={remaining}
                                            step="0.01"
                                            value={data.amount}
                                            onChange={(event) =>
                                                setData(
                                                    'amount',
                                                    Number(event.target.value),
                                                )
                                            }
                                        />
                                        {errors.amount && (
                                            <p className="text-sm text-destructive">
                                                {errors.amount}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="payment_method">
                                            Metode Pembayaran
                                        </Label>
                                        <select
                                            id="payment_method"
                                            value={data.payment_method}
                                            onChange={(event) =>
                                                setData(
                                                    'payment_method',
                                                    event.target
                                                        .value as PaymentMethod,
                                                )
                                            }
                                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                                        >
                                            {Object.entries(
                                                paymentMethodLabels,
                                            ).map(([key, label]) => (
                                                <option key={key} value={key}>
                                                    {label}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.payment_method && (
                                            <p className="text-sm text-destructive">
                                                {errors.payment_method}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="paid_at">
                                            Waktu Bayar
                                        </Label>
                                        <Input
                                            id="paid_at"
                                            type="datetime-local"
                                            value={data.paid_at}
                                            onChange={(event) =>
                                                setData(
                                                    'paid_at',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="notes">Catatan</Label>
                                        <Input
                                            id="notes"
                                            value={data.notes}
                                            onChange={(event) =>
                                                setData(
                                                    'notes',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Opsional"
                                        />
                                    </div>

                                    <DialogFooter>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Menyimpan...'
                                                : 'Simpan Pembayaran'}
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    )}
                </div>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <div className="rounded-xl border lg:col-span-2">
                    <div className="border-b px-5 py-4">
                        <h3 className="font-semibold">Item Pembelian</h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-xs text-muted-foreground">
                                    <th className="px-5 py-3">Item</th>
                                    <th className="px-5 py-3 text-right">
                                        Qty
                                    </th>
                                    <th className="px-5 py-3 text-right">
                                        Harga Beli
                                    </th>
                                    <th className="px-5 py-3 text-right">
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {(purchase.items ?? []).map((item) => (
                                    <tr
                                        key={item.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-5 py-3 font-medium">
                                            {item.name}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            {item.quantity}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            {formatCurrency(
                                                item.purchase_price,
                                            )}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            {formatCurrency(item.subtotal)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="rounded-xl border">
                        <div className="border-b px-5 py-4">
                            <h3 className="font-semibold">
                                Informasi Pembelian
                            </h3>
                        </div>
                        <dl className="space-y-2 p-5 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Supplier
                                </dt>
                                <dd>{purchase.supplier?.name ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Penerima
                                </dt>
                                <dd>{purchase.receiver?.name ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Tanggal
                                </dt>
                                <dd>{formatDate(purchase.purchase_date)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Jatuh Tempo
                                </dt>
                                <dd>
                                    {purchase.due_date
                                        ? formatDate(purchase.due_date)
                                        : '-'}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between">
                                <dt className="text-muted-foreground">
                                    Status
                                </dt>
                                <dd>
                                    <PaymentStatusBadge
                                        status={purchase.payment_status}
                                        labels={paymentStatusLabels}
                                    />
                                </dd>
                            </div>
                            {purchase.notes && (
                                <div className="pt-2">
                                    <dt className="text-muted-foreground">
                                        Catatan
                                    </dt>
                                    <dd className="mt-1">{purchase.notes}</dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    <div className="rounded-xl border">
                        <div className="border-b px-5 py-4">
                            <h3 className="font-semibold">
                                Rincian Pembayaran
                            </h3>
                        </div>
                        <dl className="space-y-2 p-5 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Subtotal
                                </dt>
                                <dd>{formatCurrency(purchase.subtotal)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Diskon
                                </dt>
                                <dd>-{formatCurrency(purchase.discount)}</dd>
                            </div>
                            <div className="flex justify-between border-t pt-2 text-base font-semibold">
                                <dt>Total</dt>
                                <dd>{formatCurrency(purchase.total)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Dibayar
                                </dt>
                                <dd>{formatCurrency(purchase.paid_amount)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Sisa Hutang
                                </dt>
                                <dd className="font-semibold">
                                    {formatCurrency(remaining)}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-xl border">
                        <div className="border-b px-5 py-4">
                            <h3 className="font-semibold">
                                Riwayat Pembayaran
                            </h3>
                        </div>
                        {(purchase.payments ?? []).length === 0 ? (
                            <p className="p-5 text-sm text-muted-foreground">
                                Belum ada pembayaran.
                            </p>
                        ) : (
                            <ul className="divide-y p-2 text-sm">
                                {(purchase.payments ?? []).map((payment) => (
                                    <li
                                        key={payment.id}
                                        className="flex items-center justify-between gap-3 px-3 py-2"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {formatCurrency(payment.amount)}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDate(payment.paid_at)} ·{' '}
                                                {paymentMethodLabels[
                                                    payment.payment_method
                                                ] ?? payment.payment_method}
                                                {payment.notes
                                                    ? ` · ${payment.notes}`
                                                    : ''}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

PurchaseShow.layout = {
    breadcrumbs: [
        { title: 'Daftar Pembelian', href: index().url },
        { title: 'Detail Pembelian', href: index().url },
    ],
};
