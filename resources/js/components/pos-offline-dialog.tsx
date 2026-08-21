import { CheckCircle2, CloudOff, RotateCcw, Trash2, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { QueuedSale } from '@/lib/offline-sales';
import { formatCurrency } from '@/lib/utils';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    items: QueuedSale[];
    syncing: boolean;
    onSyncAll: () => void;
    onRetry: (reference: string) => void;
    onDiscard: (reference: string) => void;
};

function formatTime(iso: string): string {
    return new Date(iso).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function PosOfflineDialog({
    open,
    onOpenChange,
    items,
    syncing,
    onSyncAll,
    onRetry,
    onDiscard,
}: Props) {
    const pendingCount = items.filter((item) => item.status === 'pending').length;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Antrean Transaksi Offline</DialogTitle>
                    <DialogDescription>
                        Transaksi tersimpan di perangkat ini dan dikirim otomatis
                        saat koneksi kembali. Pastikan data sudah benar sebelum
                        dibuang.
                    </DialogDescription>
                </DialogHeader>

                <div className="max-h-80 space-y-3 overflow-y-auto">
                    {items.length === 0 && (
                        <p className="py-6 text-center text-sm text-muted-foreground">
                            Tidak ada transaksi dalam antrean.
                        </p>
                    )}
                    {items.map((item) => (
                        <div
                            key={item.reference}
                            className="rounded-lg border p-3 text-sm"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <span className="flex min-w-0 items-center gap-1.5 font-medium">
                                    {item.status === 'pending' ? (
                                        <CloudOff className="size-4 shrink-0 text-amber-500" />
                                    ) : (
                                        <XCircle className="size-4 shrink-0 text-destructive" />
                                    )}
                                    <span className="truncate">
                                        {item.payload.items
                                            .map((line) => line.name)
                                            .join(', ')}
                                    </span>
                                </span>
                                <span className="shrink-0 font-semibold">
                                    {formatCurrency(item.total)}
                                </span>
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {formatTime(item.queuedAt)} ·{' '}
                                {item.payload.items.reduce(
                                    (sum, line) => sum + line.quantity,
                                    0,
                                )}{' '}
                                item
                            </p>
                            {item.error && (
                                <p className="mt-1 text-xs text-destructive">
                                    {item.error}
                                </p>
                            )}
                            <div className="mt-2 flex justify-end gap-1">
                                {item.status === 'failed' && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={syncing}
                                        onClick={() => onRetry(item.reference)}
                                    >
                                        <RotateCcw />
                                        Coba Lagi
                                    </Button>
                                )}
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-destructive"
                                    disabled={syncing}
                                    onClick={() => onDiscard(item.reference)}
                                >
                                    <Trash2 />
                                    Buang
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>

                <DialogFooter>
                    <Button
                        onClick={onSyncAll}
                        disabled={
                            syncing || !navigator.onLine || pendingCount === 0
                        }
                    >
                        <CheckCircle2 />
                        {syncing
                            ? 'Menyinkronkan...'
                            : `Sinkronkan (${pendingCount})`}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
