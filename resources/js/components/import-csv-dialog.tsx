import { useForm } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
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

type Props = {
    action: string;
    title?: string;
    description?: string;
    buttonLabel?: string;
    variant?: 'outline' | 'secondary';
};

export function ImportCsvDialog({
    action,
    title = 'Import CSV',
    description = 'Unggah file CSV. Baris yang sudah ada akan diperbarui, sisanya ditambahkan. Gunakan hasil Export CSV sebagai template.',
    buttonLabel = 'Import CSV',
    variant = 'outline',
}: Props) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, reset } = useForm<{
        file: File | null;
    }>({
        file: null,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post(action, {
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant={variant}>
                    <Upload />
                    {buttonLabel}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit}>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="csv-file">File CSV</Label>
                            <Input
                                id="csv-file"
                                type="file"
                                accept=".csv,.txt"
                                onChange={(event) =>
                                    setData(
                                        'file',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="submit"
                            disabled={processing || !data.file}
                        >
                            {processing ? 'Mengunggah...' : 'Import'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
