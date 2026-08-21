import { Form, Head, Link } from '@inertiajs/react';
import StoreController from '@/actions/App/Http/Controllers/StoreController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/stores';
import type { Store } from '@/types';

type Props = {
    store: Pick<Store, 'id' | 'name' | 'address' | 'phone' | 'is_active'> | null;
};

export default function StoreForm({ store }: Props) {
    return (
        <>
            <Head title={store ? 'Edit Cabang' : 'Tambah Cabang'} />

            <PageHeader
                title={store ? 'Edit Cabang' : 'Tambah Cabang'}
                description={
                    store
                        ? `Memperbarui cabang ${store.name}.`
                        : 'Buat cabang baru. Data tiap cabang terpisah dari cabang lain.'
                }
            />

            <Form
                {...(store
                    ? StoreController.update.form(store.id)
                    : StoreController.store.form())}
                className="max-w-xl space-y-6 rounded-xl border p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama cabang</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={store?.name ?? ''}
                                placeholder="cth. Cabang Dago"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="address">Alamat</Label>
                            <Input
                                id="address"
                                name="address"
                                defaultValue={store?.address ?? ''}
                                placeholder="cth. Jl. Ir. H. Juanda No. 123, Bandung"
                            />
                            <InputError message={errors.address} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">No. Telepon</Label>
                            <Input
                                id="phone"
                                name="phone"
                                defaultValue={store?.phone ?? ''}
                                placeholder="cth. 0812-3456-7890"
                            />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="is_active">Status cabang</Label>
                            <select
                                id="is_active"
                                name="is_active"
                                defaultValue={
                                    store ? (store.is_active ? '1' : '0') : '1'
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                            >
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                            <InputError message={errors.is_active} />
                        </div>

                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Simpan
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={index().url}>Batal</Link>
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}
