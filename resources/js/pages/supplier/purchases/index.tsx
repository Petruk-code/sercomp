import { Head, Link, router } from '@inertiajs/react';
import { CalendarClock, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import PurchaseController from '@/actions/App/Http/Controllers/Supplier/PurchaseController';
import { DeleteButton } from '@/components/delete-button';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { PaymentStatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index } from '@/routes/supplier/purchases';
import type { PaginatedData, Purchase } from '@/types';

type Props = {
    purchases: PaginatedData<Purchase>;
    filters: {
        search?: string;
        payment_status?: string;
        overdue?: string;
        date_from?: string;
        date_to?: string;
    };
    paymentStatusLabels: Record<string, string>;
};

function isOverduePurchase(purchase: Purchase): boolean {
    return (
        purchase.payment_status !== 'paid' &&
        purchase.due_date !== null &&
        new Date(`${purchase.due_date}T23:59:59`).getTime() < Date.now()
    );
}

export default function PurchasesIndex({
    purchases,
    filters,
    paymentStatusLabels,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [paymentStatus, setPaymentStatus] = useState(
        filters.payment_status ?? 'all',
    );
    const [overdue, setOverdue] = useState((filters.overdue ?? '') === '1');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    function applyFilters() {
        router.get(
            index().url,
            {
                search: search || undefined,
                payment_status:
                    paymentStatus === 'all' ? undefined : paymentStatus,
                overdue: overdue ? '1' : undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Pembelian Supplier" />

            <PageHeader
                title="Pembelian Supplier"
                description="Pembelian (PO), tempo, dan pembayaran cicilan ke supplier."
            >
                <Button asChild>
                    <Link href={PurchaseController.create.url()}>
                        <Plus />
                        Catat Pembelian
                    </Link>
                </Button>
            </PageHeader>

            <div className="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari no. PO atau supplier..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && applyFilters()
                        }
                    />
                </div>
                <div className="flex flex-wrap gap-2">
                    <select
                        value={paymentStatus}
                        onChange={(event) =>
                            setPaymentStatus(event.target.value)
                        }
                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="all">Semua status</option>
                        {Object.entries(paymentStatusLabels).map(
                            ([key, label]) => (
                                <option key={key} value={key}>
                                    {label}
                                </option>
                            ),
                        )}
                    </select>
                    <Button
                        variant={overdue ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => {
                            setOverdue((current) => !current);
                        }}
                    >
                        <CalendarClock />
                        Jatuh Tempo
                    </Button>
                    <Input
                        type="date"
                        className="h-9 w-auto"
                        value={dateFrom}
                        onChange={(event) => setDateFrom(event.target.value)}
                    />
                    <Input
                        type="date"
                        className="h-9 w-auto"
                        value={dateTo}
                        onChange={(event) => setDateTo(event.target.value)}
                    />
                    <Button variant="secondary" onClick={applyFilters}>
                        Terapkan
                    </Button>
                </div>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">No. PO</th>
                            <th className="px-4 py-3">Supplier</th>
                            <th className="px-4 py-3">Tanggal</th>
                            <th className="px-4 py-3">Jatuh Tempo</th>
                            <th className="px-4 py-3 text-right">Total</th>
                            <th className="px-4 py-3 text-right">Sisa</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {purchases.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={8}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada pembelian.
                                </td>
                            </tr>
                        )}
                        {purchases.data.map((purchase) => {
                            const remaining = Math.max(
                                Number(purchase.total) -
                                    Number(purchase.paid_amount),
                                0,
                            );
                            const isOverdue = isOverduePurchase(purchase);

                            return (
                                <tr
                                    key={purchase.id}
                                    className={`border-b last:border-0 ${isOverdue ? 'bg-destructive/5' : ''}`}
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {purchase.purchase_number}
                                    </td>
                                    <td className="px-4 py-3">
                                        {purchase.supplier?.name ?? '-'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatDate(purchase.purchase_date)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {purchase.due_date ? (
                                            <span
                                                className={
                                                    isOverdue
                                                        ? 'font-medium text-destructive'
                                                        : ''
                                                }
                                            >
                                                {formatDate(purchase.due_date)}
                                            </span>
                                        ) : (
                                            '-'
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {formatCurrency(purchase.total)}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {remaining > 0
                                            ? formatCurrency(remaining)
                                            : '-'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <PaymentStatusBadge
                                            status={purchase.payment_status}
                                            labels={paymentStatusLabels}
                                        />
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={PurchaseController.show.url(
                                                        purchase.id,
                                                    )}
                                                >
                                                    Detail
                                                </Link>
                                            </Button>
                                            <DeleteButton
                                                action={
                                                    PurchaseController.destroy.form(
                                                        purchase.id,
                                                    ).action
                                                }
                                                message="Item stok hasil pembelian ini akan dikurangi kembali."
                                            />
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            <Pagination data={purchases} />
        </>
    );
}

PurchasesIndex.layout = {
    breadcrumbs: [
        { title: 'Pembelian', href: index().url },
        { title: 'Daftar Pembelian', href: index().url },
    ],
};
