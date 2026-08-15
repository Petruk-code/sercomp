import { Form, Head, Link } from '@inertiajs/react';
import ServiceOrderController from '@/actions/App/Http/Controllers/Service/ServiceOrderController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/services';
import type { ServiceOrder } from '@/types';

type Props = {
    order: ServiceOrder | null;
    customers: Array<{ id: number; name: string; phone: string | null }>;
    technicians: Array<{ id: number; name: string }>;
    statuses: string[];
    statusLabels: Record<string, string>;
};

export default function ServiceOrderForm({
    order,
    customers,
    technicians,
}: Props) {
    return (
        <>
            <Head title={order ? 'Edit Servis' : 'Servis Baru'} />

            <PageHeader
                title={
                    order
                        ? `Edit Servis ${order.invoice_number}`
                        : 'Servis Baru'
                }
                description="Catat data unit yang masuk servis."
            />

            <Form
                {...(order
                    ? ServiceOrderController.update.form(order.id)
                    : ServiceOrderController.store.form())}
                className="max-w-3xl space-y-6 rounded-xl border p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="customer_id">Pelanggan</Label>
                                <select
                                    id="customer_id"
                                    name="customer_id"
                                    defaultValue={order?.customer_id ?? ''}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                >
                                    <option value="">Tanpa pelanggan</option>
                                    {customers.map((customer) => (
                                        <option
                                            key={customer.id}
                                            value={customer.id}
                                        >
                                            {customer.name}
                                            {customer.phone
                                                ? ` (${customer.phone})`
                                                : ''}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.customer_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="technician_id">Teknisi</Label>
                                <select
                                    id="technician_id"
                                    name="technician_id"
                                    defaultValue={order?.technician_id ?? ''}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                >
                                    <option value="">Belum ditugaskan</option>
                                    {technicians.map((technician) => (
                                        <option
                                            key={technician.id}
                                            value={technician.id}
                                        >
                                            {technician.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.technician_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="item_name">Nama Unit</Label>
                                <Input
                                    id="item_name"
                                    name="item_name"
                                    defaultValue={order?.item_name ?? ''}
                                    placeholder="cth. Laptop ASUS VivoBook 14"
                                    required
                                />
                                <InputError message={errors.item_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="serial_number">
                                    Serial Number
                                </Label>
                                <Input
                                    id="serial_number"
                                    name="serial_number"
                                    defaultValue={order?.serial_number ?? ''}
                                    placeholder="SN unit (opsional)"
                                />
                                <InputError message={errors.serial_number} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="estimated_cost">
                                    Estimasi Biaya (Rp)
                                </Label>
                                <Input
                                    id="estimated_cost"
                                    name="estimated_cost"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    defaultValue={order?.estimated_cost ?? 0}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Perkiraan awal yang disampaikan ke pelanggan
                                    (ditampilkan di pelacakan).
                                </p>
                                <InputError message={errors.estimated_cost} />
                            </div>

                            {order && (
                                <div className="grid gap-2">
                                    <Label htmlFor="labor_cost">
                                        Biaya Jasa Final (Rp)
                                    </Label>
                                    <Input
                                        id="labor_cost"
                                        name="labor_cost"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        defaultValue={order.labor_cost ?? 0}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Biaya jasa yang ditagihkan. Dikonfirmasi
                                        kasir saat pelunasan/pengambilan.
                                    </p>
                                    <InputError message={errors.labor_cost} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="estimated_completion_at">
                                    Estimasi Selesai
                                </Label>
                                <Input
                                    id="estimated_completion_at"
                                    name="estimated_completion_at"
                                    type="datetime-local"
                                    defaultValue={
                                        order?.estimated_completion_at
                                            ? new Date(
                                                  order.estimated_completion_at,
                                              )
                                                  .toISOString()
                                                  .slice(0, 16)
                                            : ''
                                    }
                                />
                                <InputError
                                    message={errors.estimated_completion_at}
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="complaint">Keluhan</Label>
                            <textarea
                                id="complaint"
                                name="complaint"
                                required
                                defaultValue={order?.complaint ?? ''}
                                className="min-h-20 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                placeholder="Deskripsi kerusakan / keluhan pelanggan"
                            />
                            <InputError message={errors.complaint} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="physical_condition">
                                Kondisi Fisik
                            </Label>
                            <textarea
                                id="physical_condition"
                                name="physical_condition"
                                defaultValue={order?.physical_condition ?? ''}
                                className="min-h-20 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                placeholder="Kondisi fisik & kelengkapan barang masuk"
                            />
                            <InputError message={errors.physical_condition} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Catatan</Label>
                            <textarea
                                id="notes"
                                name="notes"
                                defaultValue={order?.notes ?? ''}
                                className="min-h-16 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                placeholder="Catatan internal (opsional)"
                            />
                            <InputError message={errors.notes} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="warranty_days">
                                Garansi (hari)
                            </Label>
                            <Input
                                id="warranty_days"
                                name="warranty_days"
                                type="number"
                                min="0"
                                max="3650"
                                defaultValue={order?.warranty_days ?? ''}
                                placeholder="cth. 90"
                            />
                            <p className="text-xs text-muted-foreground">
                                Masa garansi dihitung sejak servis selesai.
                                Kosongkan jika tanpa garansi.
                            </p>
                            <InputError message={errors.warranty_days} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="warranty_notes">
                                Catatan Garansi / Tindak Lanjut
                            </Label>
                            <textarea
                                id="warranty_notes"
                                name="warranty_notes"
                                defaultValue={order?.warranty_notes ?? ''}
                                className="min-h-16 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                placeholder="Syarat & ketentuan garansi, jadwal tindak lanjut (opsional)"
                            />
                            <InputError message={errors.warranty_notes} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={processing}>
                                {order ? 'Simpan Perubahan' : 'Simpan Servis'}
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={index().url}>Batal</Link>
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

ServiceOrderForm.layout = {
    breadcrumbs: [
        { title: 'Manajemen Servis', href: index().url },
        { title: 'Servis Baru', href: index().url },
    ],
};
