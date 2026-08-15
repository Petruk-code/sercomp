import { Form, Head, Link } from '@inertiajs/react';
import CustomerController from '@/actions/App/Http/Controllers/Master/CustomerController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/master/customers';
import type { Customer } from '@/types';

type Props = {
    customer: Customer | null;
};

export default function CustomerForm({ customer }: Props) {
    return (
        <>
            <Head title={customer ? 'Edit Pelanggan' : 'Tambah Pelanggan'} />

            <PageHeader
                title={customer ? 'Edit Pelanggan' : 'Tambah Pelanggan'}
                description={
                    customer
                        ? `Memperbarui data pelanggan ${customer.name}.`
                        : 'Buat data pelanggan baru.'
                }
            />

            <Form
                {...(customer
                    ? CustomerController.update.form(customer.id)
                    : CustomerController.store.form())}
                className="max-w-xl space-y-6 rounded-xl border p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={customer?.name ?? ''}
                                placeholder="cth. Budi Santoso"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telepon</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    defaultValue={customer?.phone ?? ''}
                                    placeholder="cth. 081234567890"
                                />
                                <InputError message={errors.phone} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={customer?.email ?? ''}
                                    placeholder="cth. budi@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="address">Alamat</Label>
                            <textarea
                                id="address"
                                name="address"
                                defaultValue={customer?.address ?? ''}
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
                                defaultValue={customer?.notes ?? ''}
                                className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                placeholder="Catatan tambahan (opsional)"
                            />
                            <InputError message={errors.notes} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={processing}>
                                {customer
                                    ? 'Simpan Perubahan'
                                    : 'Simpan Pelanggan'}
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

CustomerForm.layout = {
    breadcrumbs: [
        { title: 'Master Data', href: index().url },
        { title: 'Pelanggan', href: index().url },
    ],
};
