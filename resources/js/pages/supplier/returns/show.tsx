import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import PurchaseReturnController from '@/actions/App/Http/Controllers/Supplier/PurchaseReturnController';
import { DeleteButton } from '@/components/delete-button';
import { ReturnResultBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index } from '@/routes/supplier/returns';
import type { PurchaseReturn } from '@/types';

type Props = {
    purchaseReturn: PurchaseReturn;
    resultTypeLabels: Record<string, string>;
};

export default function PurchaseReturnShow({
    purchaseReturn,
    resultTypeLabels,
}: Props) {
    return (
        <>
            <Head title={purchaseReturn.return_number} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">
                        {purchaseReturn.return_number}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {formatDate(purchaseReturn.return_date)} ·{' '}
                        {purchaseReturn.purchase?.supplier?.name ?? '-'}
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={index().url}>
                            <ArrowLeft />
                            Daftar Retur
                        </Link>
                    </Button>
                    {purchaseReturn.result_type !== 'debt_deduction' && (
                        <DeleteButton
                            action={
                                PurchaseReturnController.destroy.form(
                                    purchaseReturn.id,
                                ).action
                            }
                            message="Stok barang hasil retur ini akan dikembalikan ke gudang."
                        />
                    )}
                </div>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <div className="rounded-xl border lg:col-span-2">
                    <div className="border-b px-5 py-4">
                        <h3 className="font-semibold">Item Retur</h3>
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
                                        Harga
                                    </th>
                                    <th className="px-5 py-3 text-right">
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {(purchaseReturn.items ?? []).map((item) => (
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
                                            {formatCurrency(item.price)}
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
                            <h3 className="font-semibold">Informasi Retur</h3>
                        </div>
                        <dl className="space-y-2 p-5 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    No. PO
                                </dt>
                                <dd>
                                    {purchaseReturn.purchase?.purchase_number ??
                                        '-'}
                                </dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Supplier
                                </dt>
                                <dd>
                                    {purchaseReturn.purchase?.supplier?.name ??
                                        '-'}
                                </dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Tanggal
                                </dt>
                                <dd>
                                    {formatDate(purchaseReturn.return_date)}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between">
                                <dt className="text-muted-foreground">Hasil</dt>
                                <dd>
                                    <ReturnResultBadge
                                        result={purchaseReturn.result_type}
                                        labels={resultTypeLabels}
                                    />
                                </dd>
                            </div>
                            {purchaseReturn.reason && (
                                <div className="pt-2">
                                    <dt className="text-muted-foreground">
                                        Alasan / Kondisi
                                    </dt>
                                    <dd className="mt-1">
                                        {purchaseReturn.reason}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    <div className="rounded-xl border">
                        <div className="border-b px-5 py-4">
                            <h3 className="font-semibold">Total Retur</h3>
                        </div>
                        <dl className="space-y-2 p-5 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Nilai Retur
                                </dt>
                                <dd className="font-semibold">
                                    {formatCurrency(purchaseReturn.total)}
                                </dd>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {purchaseReturn.result_type === 'debt_deduction'
                                    ? 'Nilai ini telah memotong total pembelian terkait.'
                                    : 'Stok barang telah dikurangi saat retur diproses.'}
                            </p>
                        </dl>
                    </div>

                    {purchaseReturn.notes && (
                        <div className="rounded-xl border">
                            <div className="border-b px-5 py-4">
                                <h3 className="font-semibold">Catatan</h3>
                            </div>
                            <p className="p-5 text-sm">
                                {purchaseReturn.notes}
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

PurchaseReturnShow.layout = {
    breadcrumbs: [
        { title: 'Retur', href: index().url },
        { title: 'Detail Retur', href: index().url },
    ],
};
