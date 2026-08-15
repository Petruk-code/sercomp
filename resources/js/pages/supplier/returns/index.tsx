import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { useState } from 'react';
import PurchaseReturnController from '@/actions/App/Http/Controllers/Supplier/PurchaseReturnController';
import { DeleteButton } from '@/components/delete-button';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { ReturnResultBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatCurrency, formatDate } from '@/lib/utils';
import { index as purchasesIndex } from '@/routes/supplier/purchases';
import { index } from '@/routes/supplier/returns';
import type { PaginatedData, PurchaseReturn } from '@/types';

type Props = {
    returns: PaginatedData<PurchaseReturn>;
    filters: {
        search?: string;
        result_type?: string;
        date_from?: string;
        date_to?: string;
    };
    resultTypeLabels: Record<string, string>;
};

export default function ReturnsIndex({
    returns,
    filters,
    resultTypeLabels,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [resultType, setResultType] = useState(filters.result_type ?? 'all');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    function applyFilters() {
        router.get(
            index().url,
            {
                search: search || undefined,
                result_type: resultType === 'all' ? undefined : resultType,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Retur Pembelian" />

            <PageHeader
                title="Retur Pembelian"
                description="Retur barang ke supplier: ganti, refund, atau potong hutang."
            >
                <Button asChild>
                    <Link href={purchasesIndex().url}>
                        <Plus />
                        Buat Retur
                    </Link>
                </Button>
            </PageHeader>

            <div className="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari no. retur atau supplier..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && applyFilters()
                        }
                    />
                </div>
                <div className="flex flex-wrap gap-2">
                    <select
                        value={resultType}
                        onChange={(event) => setResultType(event.target.value)}
                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="all">Semua hasil</option>
                        {Object.entries(resultTypeLabels).map(
                            ([key, label]) => (
                                <option key={key} value={key}>
                                    {label}
                                </option>
                            ),
                        )}
                    </select>
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
                            <th className="px-4 py-3">No. Retur</th>
                            <th className="px-4 py-3">Supplier</th>
                            <th className="px-4 py-3">No. PO</th>
                            <th className="px-4 py-3">Tanggal</th>
                            <th className="px-4 py-3">Hasil</th>
                            <th className="px-4 py-3 text-right">Total</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {returns.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada retur.
                                </td>
                            </tr>
                        )}
                        {returns.data.map((purchaseReturn) => (
                            <tr
                                key={purchaseReturn.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3 font-medium">
                                    {purchaseReturn.return_number}
                                </td>
                                <td className="px-4 py-3">
                                    {purchaseReturn.purchase?.supplier?.name ??
                                        '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {purchaseReturn.purchase?.purchase_number ??
                                        '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {formatDate(purchaseReturn.return_date)}
                                </td>
                                <td className="px-4 py-3">
                                    <ReturnResultBadge
                                        result={purchaseReturn.result_type}
                                        labels={resultTypeLabels}
                                    />
                                </td>
                                <td className="px-4 py-3 text-right">
                                    {formatCurrency(purchaseReturn.total)}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={PurchaseReturnController.show.url(
                                                    purchaseReturn.id,
                                                )}
                                            >
                                                Detail
                                            </Link>
                                        </Button>
                                        {purchaseReturn.result_type !==
                                            'debt_deduction' && (
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
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination data={returns} />
        </>
    );
}

ReturnsIndex.layout = {
    breadcrumbs: [
        { title: 'Retur', href: index().url },
        { title: 'Daftar Retur', href: index().url },
    ],
};
