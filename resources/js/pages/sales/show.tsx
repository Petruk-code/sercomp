import { Head, Link } from '@inertiajs/react';
import { Printer } from 'lucide-react';
import { useState } from 'react';
import { PaymentStatusBadge } from '@/components/status-badge';
import { ThermalPrintDialog } from '@/components/thermal-print-dialog';
import { Button } from '@/components/ui/button';
import { buildSaleReceipt } from '@/lib/thermal-receipt';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index, print } from '@/routes/sales';
import type { Sale } from '@/types';

type Props = {
    sale: Sale;
    paymentMethodLabels: Record<string, string>;
    paymentStatusLabels: Record<string, string>;
};

export default function SaleShow({
    sale,
    paymentMethodLabels,
    paymentStatusLabels,
}: Props) {
    const [thermalOpen, setThermalOpen] = useState(false);

    return (
        <>
            <Head title={sale.invoice_number} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">
                        {sale.invoice_number}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {formatDate(sale.created_at)} ·{' '}
                        {sale.type === 'retail'
                            ? 'Penjualan Retail'
                            : 'Pelunasan Servis'}
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    {sale.serviceOrder && (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/services/${sale.service_order_id}`}>
                                {sale.serviceOrder.invoice_number}
                            </Link>
                        </Button>
                    )}
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setThermalOpen(true)}
                    >
                        <Printer />
                        Cetak Thermal
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={print.url(sale.id)} target="_blank">
                            <Printer />
                            Cetak Nota
                        </Link>
                    </Button>
                </div>
            </div>

            <ThermalPrintDialog
                open={thermalOpen}
                onOpenChange={setThermalOpen}
                buildReceipt={(paperWidth) =>
                    buildSaleReceipt(
                        sale,
                        { paymentMethodLabels, paymentStatusLabels },
                        paperWidth,
                    )
                }
            />

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <div className="rounded-xl border lg:col-span-2">
                    <div className="border-b px-5 py-4">
                        <h3 className="font-semibold">Item Transaksi</h3>
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
                                {(sale.items ?? []).map((item) => (
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
                            <h3 className="font-semibold">
                                Informasi Transaksi
                            </h3>
                        </div>
                        <dl className="space-y-2 p-5 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Pelanggan
                                </dt>
                                <dd>{sale.customer?.name ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">Kasir</dt>
                                <dd>{sale.cashier?.name ?? '-'}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Metode
                                </dt>
                                <dd>
                                    {paymentMethodLabels[sale.payment_method] ??
                                        sale.payment_method}
                                </dd>
                            </div>
                            <div className="flex items-center justify-between">
                                <dt className="text-muted-foreground">
                                    Status
                                </dt>
                                <dd>
                                    <PaymentStatusBadge
                                        status={sale.payment_status}
                                        labels={paymentStatusLabels}
                                    />
                                </dd>
                            </div>
                            {sale.notes && (
                                <div className="pt-2">
                                    <dt className="text-muted-foreground">
                                        Catatan
                                    </dt>
                                    <dd className="mt-1">{sale.notes}</dd>
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
                                <dd>{formatCurrency(sale.subtotal)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Diskon
                                </dt>
                                <dd>-{formatCurrency(sale.discount)}</dd>
                            </div>
                            <div className="flex justify-between border-t pt-2 text-base font-semibold">
                                <dt>Total</dt>
                                <dd>{formatCurrency(sale.total)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Dibayar
                                </dt>
                                <dd>{formatCurrency(sale.paid_amount)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Kembalian
                                </dt>
                                <dd>
                                    {formatCurrency(
                                        Math.max(
                                            Number(sale.paid_amount) -
                                                Number(sale.total),
                                            0,
                                        ),
                                    )}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </>
    );
}

SaleShow.layout = {
    breadcrumbs: [
        { title: 'Riwayat Transaksi', href: index().url },
        { title: 'Detail Nota', href: index().url },
    ],
};
