import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/utils';
import { index } from '@/routes/audit-logs';
import type { AuditLog, PaginatedData } from '@/types';

type Props = {
    logs: PaginatedData<AuditLog>;
    filters: {
        search?: string;
        action?: string;
        date_from?: string;
        date_to?: string;
    };
    actionLabels: Record<string, string>;
};

function ValueChips({
    values,
}: {
    values: Record<string, string | number | null> | null;
}) {
    if (!values || Object.keys(values).length === 0) {
        return <span className="text-muted-foreground">-</span>;
    }

    return (
        <div className="flex flex-wrap gap-1">
            {Object.entries(values).map(([key, value]) => (
                <span
                    key={key}
                    className="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs"
                >
                    {key}: {value ?? 'null'}
                </span>
            ))}
        </div>
    );
}

export default function AuditLogsIndex({ logs, filters, actionLabels }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [action, setAction] = useState(filters.action ?? 'all');
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    function applyFilters() {
        router.get(
            index().url,
            {
                search: search || undefined,
                action: action === 'all' ? undefined : action,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Audit Log" />

            <PageHeader
                title="Audit Log"
                description="Catatan siapa mengubah apa pada aksi sensitif (pembayaran, retur, status servis, stok, dan 2FA)."
            />

            <div className="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari keterangan..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && applyFilters()
                        }
                    />
                </div>
                <div className="flex flex-wrap gap-2">
                    <select
                        value={action}
                        onChange={(event) => setAction(event.target.value)}
                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="all">Semua aksi</option>
                        {Object.entries(actionLabels).map(([key, label]) => (
                            <option key={key} value={key}>
                                {label}
                            </option>
                        ))}
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
                            <th className="px-4 py-3">Waktu</th>
                            <th className="px-4 py-3">Pengguna</th>
                            <th className="px-4 py-3">Aksi</th>
                            <th className="px-4 py-3">Keterangan</th>
                            <th className="px-4 py-3">Perubahan</th>
                            <th className="px-4 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={6}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada catatan audit.
                                </td>
                            </tr>
                        )}
                        {logs.data.map((log) => (
                            <tr
                                key={log.id}
                                className="border-b align-top last:border-0"
                            >
                                <td className="px-4 py-3 whitespace-nowrap">
                                    {formatDate(log.created_at)}
                                </td>
                                <td className="px-4 py-3 font-medium whitespace-nowrap">
                                    {log.user?.name ?? 'Sistem'}
                                </td>
                                <td className="px-4 py-3 whitespace-nowrap">
                                    <span className="rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                        {actionLabels[log.action] ?? log.action}
                                    </span>
                                </td>
                                <td className="px-4 py-3">{log.description}</td>
                                <td className="px-4 py-3">
                                    <ValueChips values={log.new_values} />
                                    {log.old_values &&
                                        Object.keys(log.old_values).length >
                                            0 && (
                                            <div className="mt-1 text-xs text-muted-foreground">
                                                <span>sebelum: </span>
                                                <ValueChips
                                                    values={log.old_values}
                                                />
                                            </div>
                                        )}
                                </td>
                                <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                                    {log.ip_address ?? '-'}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination data={logs} />
        </>
    );
}

AuditLogsIndex.layout = {
    breadcrumbs: [{ title: 'Audit Log', href: index().url }],
};
