import { Form, Head, Link } from '@inertiajs/react';
import {
    Download,
    MessageCircle,
    Printer,
    ShoppingCart,
    Wrench,
} from 'lucide-react';
import { useState } from 'react';
import SaleController from '@/actions/App/Http/Controllers/Sales/SaleController';
import ServiceOrderController from '@/actions/App/Http/Controllers/Service/ServiceOrderController';
import { DeleteButton } from '@/components/delete-button';
import InputError from '@/components/input-error';
import {
    PaymentStatusBadge,
    ServiceStatusBadge,
    WarrantyBadge,
} from '@/components/status-badge';
import { ThermalPrintDialog } from '@/components/thermal-print-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { buildServiceReceipt } from '@/lib/thermal-receipt';
import { formatCurrency, formatDate } from '@/lib/utils';
import { print as printRoute } from '@/routes/sales';
import { index } from '@/routes/services';
import type {
    Product,
    ServiceOrder,
    ServiceOrderItem,
    ServiceOrderStatus,
} from '@/types';

type Props = {
    order: ServiceOrder;
    cardUrl: string;
    products: Product[];
    statuses: ServiceOrderStatus[];
    statusLabels: Record<string, string>;
    paymentStatusLabels: Record<string, string>;
};

const partsTotal = (items: ServiceOrderItem[] = []): number =>
    items.reduce((sum, item) => sum + Number(item.subtotal), 0);

export default function ServiceOrderShow({
    order,
    cardUrl,
    products,
    statuses,
    statusLabels,
    paymentStatusLabels,
}: Props) {
    const itemsTotal = partsTotal(order.items);
    const sale = order.sale;
    const showCheckoutForm = order.status === 'done' && !sale;
    const showPayRemaining = Boolean(sale && sale.payment_status !== 'paid');

    const [laborCost, setLaborCost] = useState(Number(order.labor_cost));
    const [discount, setDiscount] = useState(0);
    const [thermalOpen, setThermalOpen] = useState(false);

    const checkoutTotal = Math.max(laborCost + itemsTotal - discount, 0);

    const [productId, setProductId] = useState('');
    const [itemName, setItemName] = useState('');
    const [quantity, setQuantity] = useState(1);
    const [price, setPrice] = useState(0);

    function selectProduct(id: string) {
        setProductId(id);

        const product = products.find((item) => item.id === Number(id));

        if (product) {
            setItemName(product.name);
            setPrice(Number(product.selling_price));
        }
    }

    return (
        <>
            <Head title={order.invoice_number} />

            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-xl font-semibold tracking-tight">
                        {order.invoice_number}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Masuk {formatDate(order.created_at)} · {order.item_name}
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <ServiceStatusBadge status={order.status} />
                    {order.customer?.phone && (
                        <Form
                            action={
                                ServiceOrderController.sendWhatsapp.form(
                                    order.id,
                                ).action
                            }
                            method="post"
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    size="sm"
                                    disabled={processing}
                                >
                                    <MessageCircle />
                                    {processing
                                        ? 'Mengirim...'
                                        : 'Kirim WhatsApp'}
                                </Button>
                            )}
                        </Form>
                    )}
                    <Button variant="outline" size="sm" asChild>
                        <Link href={cardUrl} target="_blank">
                            <Download />
                            Kartu Lacak (PDF)
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => setThermalOpen(true)}
                    >
                        <Printer />
                        Cetak Thermal
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link
                            href={ServiceOrderController.print.url(order.id)}
                            target="_blank"
                        >
                            <Printer />
                            Cetak Tanda Terima
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                        <Link href={ServiceOrderController.edit.url(order.id)}>
                            Edit
                        </Link>
                    </Button>
                    <DeleteButton
                        action={
                            ServiceOrderController.destroy.form(order.id).action
                        }
                    />
                </div>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <div className="rounded-xl border">
                        <div className="border-b px-5 py-4">
                            <h3 className="font-semibold">Informasi Servis</h3>
                        </div>
                        <dl className="grid gap-x-6 gap-y-3 p-5 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground">
                                    Pelanggan
                                </dt>
                                <dd className="font-medium">
                                    {order.customer?.name ?? '-'}
                                    {order.customer?.phone && (
                                        <span className="block text-xs font-normal text-muted-foreground">
                                            {order.customer.phone}
                                        </span>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Teknisi
                                </dt>
                                <dd className="font-medium">
                                    {order.technician?.name ??
                                        'Belum ditugaskan'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Serial Number
                                </dt>
                                <dd className="font-medium">
                                    {order.serial_number ?? '-'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Estimasi Selesai
                                </dt>
                                <dd className="font-medium">
                                    {order.estimated_completion_at
                                        ? formatDate(
                                              order.estimated_completion_at,
                                          )
                                        : '-'}
                                </dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-muted-foreground">
                                    Keluhan
                                </dt>
                                <dd className="mt-1">{order.complaint}</dd>
                            </div>
                            {order.physical_condition && (
                                <div className="sm:col-span-2">
                                    <dt className="text-muted-foreground">
                                        Kondisi Fisik
                                    </dt>
                                    <dd className="mt-1">
                                        {order.physical_condition}
                                    </dd>
                                </div>
                            )}
                            {order.notes && (
                                <div className="sm:col-span-2">
                                    <dt className="text-muted-foreground">
                                        Catatan
                                    </dt>
                                    <dd className="mt-1">{order.notes}</dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    <div className="rounded-xl border">
                        <div className="flex items-center justify-between border-b px-5 py-4">
                            <h3 className="flex items-center gap-2 font-semibold">
                                <Wrench className="size-4" />
                                Sparepart Terpakai
                            </h3>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs text-muted-foreground">
                                        <th className="px-5 py-3">Nama</th>
                                        <th className="px-5 py-3 text-right">
                                            Qty
                                        </th>
                                        <th className="px-5 py-3 text-right">
                                            Harga
                                        </th>
                                        <th className="px-5 py-3 text-right">
                                            Subtotal
                                        </th>
                                        <th className="px-5 py-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {order.items && order.items.length > 0 ? (
                                        order.items.map((item) => (
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
                                                    {formatCurrency(
                                                        item.subtotal,
                                                    )}
                                                </td>
                                                <td className="px-5 py-3 text-right">
                                                    {!sale && (
                                                        <Form
                                                            action={
                                                                ServiceOrderController.removeItem.form(
                                                                    [
                                                                        order.id,
                                                                        item.id,
                                                                    ],
                                                                ).action
                                                            }
                                                            method="post"
                                                        >
                                                            {({
                                                                processing,
                                                            }) => (
                                                                <Button
                                                                    type="submit"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                >
                                                                    Hapus
                                                                </Button>
                                                            )}
                                                        </Form>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={5}
                                                className="px-5 py-8 text-center text-muted-foreground"
                                            >
                                                Belum ada sparepart.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {!sale &&
                            !['taken', 'cancelled'].includes(order.status) && (
                                <div className="border-t p-5">
                                    <h4 className="mb-3 text-sm font-semibold">
                                        Tambah Sparepart
                                    </h4>
                                    <Form
                                        {...ServiceOrderController.addItem.form(
                                            order.id,
                                        )}
                                        resetOnSuccess
                                    >
                                        {({ errors, processing }) => (
                                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="product_id">
                                                        Produk
                                                    </Label>
                                                    <select
                                                        id="product_id"
                                                        name="product_id"
                                                        value={productId}
                                                        onChange={(event) =>
                                                            selectProduct(
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                                    >
                                                        <option value="">
                                                            Pilih produk...
                                                        </option>
                                                        {products.map(
                                                            (product) => (
                                                                <option
                                                                    key={
                                                                        product.id
                                                                    }
                                                                    value={
                                                                        product.id
                                                                    }
                                                                >
                                                                    {
                                                                        product.name
                                                                    }{' '}
                                                                    (stok{' '}
                                                                    {
                                                                        product.stock
                                                                    }
                                                                    )
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="name">
                                                        Nama
                                                    </Label>
                                                    <Input
                                                        id="name"
                                                        name="name"
                                                        value={itemName}
                                                        onChange={(event) =>
                                                            setItemName(
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        placeholder="Nama sparepart"
                                                    />
                                                    <InputError
                                                        message={errors.name}
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="quantity">
                                                        Qty
                                                    </Label>
                                                    <Input
                                                        id="quantity"
                                                        name="quantity"
                                                        type="number"
                                                        min="1"
                                                        value={quantity}
                                                        onChange={(event) =>
                                                            setQuantity(
                                                                Number(
                                                                    event.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.quantity
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="price">
                                                        Harga (Rp)
                                                    </Label>
                                                    <Input
                                                        id="price"
                                                        name="price"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={price}
                                                        onChange={(event) =>
                                                            setPrice(
                                                                Number(
                                                                    event.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={errors.price}
                                                    />
                                                </div>
                                                <div className="sm:col-span-2 lg:col-span-4">
                                                    <Button
                                                        type="submit"
                                                        size="sm"
                                                        disabled={processing}
                                                    >
                                                        Tambahkan
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                    </Form>
                                </div>
                            )}
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="rounded-xl border">
                        <div className="border-b px-5 py-4">
                            <h3 className="font-semibold">Status Servis</h3>
                        </div>
                        <div className="p-5">
                            <Form
                                {...ServiceOrderController.updateStatus.form(
                                    order.id,
                                )}
                            >
                                {({ processing }) => (
                                    <div className="space-y-3">
                                        <select
                                            name="status"
                                            defaultValue={order.status}
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                        >
                                            {statuses.map((item) => (
                                                <option key={item} value={item}>
                                                    {statusLabels[item]}
                                                </option>
                                            ))}
                                        </select>
                                        <Button
                                            type="submit"
                                            size="sm"
                                            className="w-full"
                                            disabled={processing}
                                        >
                                            Update Status
                                        </Button>
                                    </div>
                                )}
                            </Form>
                        </div>
                    </div>

                    <div className="rounded-xl border">
                        <div className="border-b px-5 py-4">
                            <h3 className="font-semibold">Rincian Biaya</h3>
                        </div>
                        <dl className="space-y-2 p-5 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Estimasi (perkiraan)
                                </dt>
                                <dd>{formatCurrency(order.estimated_cost)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Biaya Jasa (final)
                                </dt>
                                <dd>{formatCurrency(order.labor_cost)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Sparepart
                                </dt>
                                <dd>{formatCurrency(itemsTotal)}</dd>
                            </div>
                            <div className="flex justify-between border-t pt-2 font-semibold">
                                <dt>Total</dt>
                                <dd>{formatCurrency(order.total_cost)}</dd>
                            </div>
                        </dl>
                    </div>

                    <div className="rounded-xl border">
                        <div className="border-b px-5 py-4">
                            <h3 className="font-semibold">Garansi Servis</h3>
                        </div>
                        <dl className="space-y-2 p-5 text-sm">
                            <div className="flex items-center justify-between">
                                <dt className="text-muted-foreground">
                                    Status
                                </dt>
                                <dd>
                                    <WarrantyBadge
                                        warrantyDays={order.warranty_days}
                                        warrantyEndDate={
                                            order.warranty_end_date
                                        }
                                    />
                                </dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Durasi
                                </dt>
                                <dd className="font-medium">
                                    {order.warranty_days
                                        ? `${order.warranty_days} hari`
                                        : '-'}
                                </dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">
                                    Berakhir
                                </dt>
                                <dd className="font-medium">
                                    {order.warranty_end_date
                                        ? new Date(
                                              order.warranty_end_date,
                                          ).toLocaleDateString('id-ID', {
                                              day: 'numeric',
                                              month: 'short',
                                              year: 'numeric',
                                          })
                                        : '-'}
                                </dd>
                            </div>
                            {order.warranty_notes && (
                                <div className="border-t pt-2">
                                    <dt className="text-muted-foreground">
                                        Catatan
                                    </dt>
                                    <dd className="mt-1">
                                        {order.warranty_notes}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    {(showCheckoutForm || showPayRemaining) && (
                        <div className="rounded-xl border">
                            <div className="border-b px-5 py-4">
                                <h3 className="flex items-center gap-2 font-semibold">
                                    <ShoppingCart className="size-4" />
                                    Pelunasan &amp; Serah Terima
                                </h3>
                            </div>
                            <div className="p-5">
                                {showCheckoutForm ? (
                                    <Form
                                        {...SaleController.checkout.form(
                                            order.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ errors, processing }) => (
                                            <div className="space-y-3">
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="labor_cost">
                                                        Biaya Jasa Final (Rp)
                                                    </Label>
                                                    <Input
                                                        id="labor_cost"
                                                        name="labor_cost"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={laborCost}
                                                        onChange={(event) =>
                                                            setLaborCost(
                                                                Number(
                                                                    event.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                    />
                                                    <p className="text-xs text-muted-foreground">
                                                        Tagihan yang tampil di
                                                        riwayat = biaya jasa
                                                        final + sparepart.
                                                    </p>
                                                    <InputError
                                                        message={
                                                            errors.labor_cost
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="payment_method">
                                                        Metode
                                                    </Label>
                                                    <select
                                                        id="payment_method"
                                                        name="payment_method"
                                                        defaultValue="cash"
                                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                                    >
                                                        <option value="cash">
                                                            Tunai
                                                        </option>
                                                        <option value="transfer">
                                                            Transfer
                                                        </option>
                                                        <option value="qris">
                                                            QRIS
                                                        </option>
                                                    </select>
                                                    <InputError
                                                        message={
                                                            errors.payment_method
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="discount">
                                                        Diskon (Rp)
                                                    </Label>
                                                    <Input
                                                        id="discount"
                                                        name="discount"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={discount}
                                                        onChange={(event) =>
                                                            setDiscount(
                                                                Number(
                                                                    event.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.discount
                                                        }
                                                    />
                                                </div>
                                                <div className="flex justify-between border-t pt-2 text-sm">
                                                    <span className="text-muted-foreground">
                                                        Total Tagihan
                                                    </span>
                                                    <span className="font-semibold">
                                                        {formatCurrency(
                                                            checkoutTotal,
                                                        )}
                                                    </span>
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor="paid_amount">
                                                        Dibayar (Rp)
                                                    </Label>
                                                    <Input
                                                        id="paid_amount"
                                                        name="paid_amount"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        key={`paid-${order.id}-${checkoutTotal}`}
                                                        defaultValue={
                                                            checkoutTotal
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.paid_amount
                                                        }
                                                    />
                                                </div>
                                                <Button
                                                    type="submit"
                                                    className="w-full"
                                                    disabled={processing}
                                                >
                                                    Buat Nota &amp; Tandai
                                                    Diambil
                                                </Button>
                                            </div>
                                        )}
                                    </Form>
                                ) : sale ? (
                                    <Form
                                        {...SaleController.completePayment.form(
                                            sale.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ errors, processing }) => {
                                            const remaining = Math.max(
                                                Number(sale.total) -
                                                    Number(sale.paid_amount),
                                                0,
                                            );

                                            return (
                                                <div className="space-y-3">
                                                    <div className="space-y-2 text-sm">
                                                        <div className="flex justify-between">
                                                            <span className="text-muted-foreground">
                                                                Total Tagihan
                                                            </span>
                                                            <span className="font-medium">
                                                                {formatCurrency(
                                                                    sale.total,
                                                                )}
                                                            </span>
                                                        </div>
                                                        <div className="flex justify-between">
                                                            <span className="text-muted-foreground">
                                                                Sudah Dibayar
                                                            </span>
                                                            <span>
                                                                {formatCurrency(
                                                                    sale.paid_amount,
                                                                )}
                                                            </span>
                                                        </div>
                                                        <div className="flex justify-between border-t pt-2 font-semibold">
                                                            <span>Sisa</span>
                                                            <span>
                                                                {formatCurrency(
                                                                    remaining,
                                                                )}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor="paid_amount">
                                                            Bayar Sekarang (Rp)
                                                        </Label>
                                                        <Input
                                                            id="paid_amount"
                                                            name="paid_amount"
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            defaultValue={
                                                                remaining
                                                            }
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.paid_amount
                                                            }
                                                        />
                                                    </div>
                                                    <Button
                                                        type="submit"
                                                        className="w-full"
                                                        disabled={processing}
                                                    >
                                                        Bayar Sisa &amp; Tandai
                                                        Diambil
                                                    </Button>
                                                </div>
                                            );
                                        }}
                                    </Form>
                                ) : null}
                            </div>
                        </div>
                    )}

                    {order.sale && (
                        <div className="rounded-xl border">
                            <div className="border-b px-5 py-4">
                                <h3 className="font-semibold">Transaksi</h3>
                            </div>
                            <div className="space-y-3 p-5 text-sm">
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">
                                        No. Nota
                                    </span>
                                    <span className="font-medium">
                                        {order.sale.invoice_number}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">
                                        Status
                                    </span>
                                    <PaymentStatusBadge
                                        status={order.sale.payment_status}
                                        labels={paymentStatusLabels}
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        size="sm"
                                        className="flex-1"
                                        asChild
                                    >
                                        <Link href={`/sales/${order.sale.id}`}>
                                            Lihat Nota
                                        </Link>
                                    </Button>
                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href={printRoute.url(order.sale.id)}
                                            target="_blank"
                                        >
                                            <Printer />
                                            Cetak
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}

                    <ThermalPrintDialog
                        open={thermalOpen}
                        onOpenChange={setThermalOpen}
                        buildReceipt={(paperWidth) =>
                            buildServiceReceipt(
                                order,
                                {
                                    statusLabels,
                                    trackingUrl: `${window.location.origin}/tracking?no=${order.invoice_number}`,
                                },
                                paperWidth,
                            )
                        }
                    />
                </div>
            </div>
        </>
    );
}

ServiceOrderShow.layout = {
    breadcrumbs: [
        { title: 'Manajemen Servis', href: index().url },
        { title: 'Detail Servis', href: index().url },
    ],
};
