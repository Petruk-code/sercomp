import { Head, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';
import { debts as debtsRoute } from '@/routes/reports';
import { exportMethod as exportDebts } from '@/routes/reports/debts';
import type { Supplier } from '@/types';

type DebtRow = {
    id: number | null;
    name: string;
    open_count: number;
    current: number;
    overdue_0_30: number;
    overdue_31_60: number;
    overdue_60: number;
    outstanding: number;
};

type Props = {
    rows: DebtRow[];
    summary: {
        outstanding: string | number;
        overdue: string | number;
        suppliers: number;
    };
    suppliers: Supplier[];
    filters: { supplier_id: number | null };
};

export default function DebtsReport({
    rows,
    summary,
    suppliers,
    filters,
}: Props) {
    const [supplierId, setSupplierId] = useState(filters.supplier_id ?? 'all');

    function applyFilters() {
        router.get(
            debtsRoute().url,
            {
                supplier_id:
                    supplierId === 'all' ? undefined : Number(supplierId),
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Mutasi Hutang Supplier" />

            <PageHeader
                title="Mutasi Hutang Supplier"
                description="Sisa hutang per supplier dikelompokkan menurut umur jatuh tempo."
            >
                <Button variant="outline" asChild>
                    <a
                        href={
                            exportDebts({
                                query: {
                                    supplier_id:
                                        supplierId === 'all'
                                            ? undefined
                                            : Number(supplierId),
                                },
                            }).url
                        }
                    >
                        <Download />
                        Export CSV
                    </a>
                </Button>
            </PageHeader>

            <div className="mb-4 flex flex-wrap items-end gap-2">
                <select
                    value={supplierId}
                    onChange={(event) => setSupplierId(event.target.value)}
                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                >
                    <option value="all">Semua supplier</option>
                    {suppliers.map((supplier) => (
                        <option key={supplier.id} value={supplier.id}>
                            {supplier.name}
                        </option>
                    ))}
                </select>
                <Button variant="secondary" onClick={applyFilters}>
                    Terapkan
                </Button>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Total Hutang Berjalan
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {formatCurrency(summary.outstanding)}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Hutang Jatuh Tempo
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold text-destructive">
                        {formatCurrency(summary.overdue)}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm text-muted-foreground">
                            Supplier Berhutang
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">
                        {summary.suppliers}
                    </CardContent>
                </Card>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">Supplier</th>
                            <th className="px-4 py-3 text-center">
                                PO Terbuka
                            </th>
                            <th className="px-4 py-3 text-right">
                                Belum Jatuh Tempo
                            </th>
                            <th className="px-4 py-3 text-right">0-30 Hari</th>
                            <th className="px-4 py-3 text-right">31-60 Hari</th>
                            <th className="px-4 py-3 text-right">
                                &gt;60 Hari
                            </th>
                            <th className="px-4 py-3 text-right">
                                Total Hutang
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Tidak ada supplier dengan hutang terbuka.
                                </td>
                            </tr>
                        )}
                        {rows.map((row) => (
                            <tr
                                key={row.id ?? 'unknown'}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3 font-medium">
                                    {row.name}
                                </td>
                                <td className="px-4 py-3 text-center">
                                    <Badge variant="secondary">
                                        {row.open_count}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3 text-right">
                                    {row.current > 0
                                        ? formatCurrency(row.current)
                                        : '-'}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    {row.overdue_0_30 > 0
                                        ? formatCurrency(row.overdue_0_30)
                                        : '-'}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    {row.overdue_31_60 > 0
                                        ? formatCurrency(row.overdue_31_60)
                                        : '-'}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    {row.overdue_60 > 0
                                        ? formatCurrency(row.overdue_60)
                                        : '-'}
                                </td>
                                <td className="px-4 py-3 text-right font-medium">
                                    {formatCurrency(row.outstanding)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}

DebtsReport.layout = {
    breadcrumbs: [{ title: 'Mutasi Hutang Supplier', href: debtsRoute().url }],
};
