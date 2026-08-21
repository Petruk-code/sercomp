import { Bluetooth, Printer, Usb } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type { PaperWidth } from '@/lib/escpos';
import {
    bluetoothSupported,
    printViaBluetooth,
    printViaSerial,
    readPaperWidth,
    saveLastTransport,
    savePaperWidth,
    serialSupported,
} from '@/lib/thermal-printer';
import type { ThermalTransport } from '@/lib/thermal-printer';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Susun data ESC/POS sesuai lebar kertas yang dipilih. */
    buildReceipt: (paperWidth: PaperWidth) => Uint8Array;
};

export function ThermalPrintDialog({
    open,
    onOpenChange,
    buildReceipt,
}: Props) {
    const [paperWidth, setPaperWidth] = useState<PaperWidth>(() =>
        readPaperWidth(),
    );
    const [busy, setBusy] = useState<ThermalTransport | null>(null);
    const [error, setError] = useState<string | null>(null);

    const hasBluetooth = bluetoothSupported();
    const hasSerial = serialSupported();

    function changeWidth(width: string) {
        if (width !== '58' && width !== '80') {
            return;
        }

        setPaperWidth(width);
        savePaperWidth(width);
    }

    async function print(transport: ThermalTransport) {
        setError(null);
        setBusy(transport);

        try {
            const data = buildReceipt(paperWidth);

            if (transport === 'bluetooth') {
                await printViaBluetooth(data);
            } else {
                await printViaSerial(data);
            }

            saveLastTransport(transport);
            toast.success('Struk terkirim ke printer.');
            onOpenChange(false);
        } catch (cause) {
            const message =
                cause instanceof Error ? cause.message : 'Gagal mencetak.';

            setError(message);
        } finally {
            setBusy(null);
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cetak ke Printer Thermal</DialogTitle>
                    <DialogDescription>
                        Pilih lebar kertas lalu sambungkan printer. Browser akan
                        meminta izin perangkat saat pertama kali.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label>Lebar kertas</Label>
                        <ToggleGroup
                            type="single"
                            variant="outline"
                            value={paperWidth}
                            onValueChange={changeWidth}
                            className="w-full"
                        >
                            <ToggleGroupItem value="58" className="flex-1">
                                58 mm
                            </ToggleGroupItem>
                            <ToggleGroupItem value="80" className="flex-1">
                                80 mm
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2">
                        <Button
                            variant="outline"
                            disabled={!hasBluetooth || busy !== null}
                            onClick={() => void print('bluetooth')}
                        >
                            <Bluetooth />
                            {busy === 'bluetooth'
                                ? 'Menyambungkan...'
                                : 'Bluetooth'}
                        </Button>
                        <Button
                            variant="outline"
                            disabled={!hasSerial || busy !== null}
                            onClick={() => void print('serial')}
                        >
                            <Usb />
                            {busy === 'serial'
                                ? 'Mencetak...'
                                : 'Kabel (Serial)'}
                        </Button>
                    </div>

                    {!hasBluetooth && !hasSerial && (
                        <p className="text-sm text-muted-foreground">
                            Browser ini tidak mendukung Web Bluetooth/Web
                            Serial. Gunakan Chrome atau Edge terbaru, atau pakai
                            Cetak Nota (PDF).
                        </p>
                    )}

                    {error && (
                        <p className="text-sm text-destructive" role="alert">
                            {error}
                        </p>
                    )}
                </div>

                <DialogFooter>
                    <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Printer className="size-3.5" />
                        Butuh printer thermal ESC/POS (58/80 mm).
                    </p>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
