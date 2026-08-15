import { Form, Head, Link } from '@inertiajs/react';
import SupplierController from '@/actions/App/Http/Controllers/Master/SupplierController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/master/suppliers';
import type { Supplier } from '@/types';

type Props = {
    supplier: Supplier | null;
};

export default function SupplierForm({ supplier }: Props) {
    return (
        <>
            <Head title={supplier ? 'Edit Supplier' : 'Tambah Supplier'} />

            <PageHeader
                title={supplier ? 'Edit Supplier' : 'Tambah Supplier'}
                description={
                    supplier
                        ? `Memperbarui data supplier ${supplier.name}.`
                        : 'Buat data supplier baru.'
                }
            />

            <Form
                {...(supplier
                    ? SupplierController.update.form(supplier.id)
                    : SupplierController.store.form())}
                className="max-w-xl space-y-6 rounded-xl border p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={supplier?.name ?? ''}
                                placeholder="cth. PT Distributor Utama"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="contact_person">
                                    Nama Kontak
                                </Label>
                                <Input
                                    id="contact_person"
                                    name="contact_person"
                                    defaultValue={
                                        supplier?.contact_person ?? ''
                                    }
                                    placeholder="cth. Andi Wijaya"
                                />
                                <InputError message={errors.contact_person} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telepon</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    defaultValue={supplier?.phone ?? ''}
                                    placeholder="cth. 081234567890"
                                />
                                <InputError message={errors.phone} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                defaultValue={supplier?.email ?? ''}
                                placeholder="cth. andi@distributor.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="address">Alamat</Label>
                            <textarea
                                id="address"
                                name="address"
                                defaultValue={supplier?.address ?? ''}
                                className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                placeholder="Alamat lengkap (opsional)"
                            />
                            <InputError message={errors.address} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Catatan</Label>
                            <textarea
                                id="notes"
                                name="notes"
                                defaultValue={supplier?.notes ?? ''}
                                className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                placeholder="Catatan tambahan (opsional)"
                            />
                            <InputError message={errors.notes} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={processing}>
                                {supplier
                                    ? 'Simpan Perubahan'
                                    : 'Simpan Supplier'}
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

SupplierForm.layout = {
    breadcrumbs: [
        { title: 'Master Data', href: index().url },
        { title: 'Supplier', href: index().url },
    ],
};
