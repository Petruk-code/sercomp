import { Form, Head, Link } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/Users/RoleController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/roles';
import type { Role } from '@/types';

type Props = {
    role: (Role & { label?: string | null }) | null;
};

export default function RoleForm({ role }: Props) {
    const isBuiltIn =
        role?.name != null &&
        ['admin', 'owner', 'cashier', 'technician'].includes(role.name);

    return (
        <>
            <Head title={role ? 'Edit Role' : 'Tambah Role'} />

            <PageHeader
                title={role ? 'Edit Role' : 'Tambah Role'}
                description={
                    role
                        ? `Memperbarui role ${role.name}.`
                        : 'Buat role baru untuk mengelompokkan pengguna.'
                }
            />

            <Form
                {...(role
                    ? RoleController.update.form(role.id)
                    : RoleController.store.form())}
                className="max-w-xl space-y-6 rounded-xl border p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Role</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={role?.name ?? ''}
                                placeholder="cth. supervisor"
                                disabled={isBuiltIn}
                                required
                            />
                            {isBuiltIn && (
                                <p className="text-xs text-muted-foreground">
                                    Nama role bawaan tidak dapat diubah.
                                </p>
                            )}
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="label">Label Tampilan</Label>
                            <Input
                                id="label"
                                name="label"
                                defaultValue={role?.label ?? ''}
                                placeholder="cth. Supervisor"
                            />
                            <p className="text-xs text-muted-foreground">
                                Nama yang ditampilkan di antarmuka (opsional).
                            </p>
                            <InputError message={errors.label} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={processing}>
                                {role ? 'Simpan Perubahan' : 'Simpan Role'}
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

RoleForm.layout = {
    breadcrumbs: [
        { title: 'Manajemen Role', href: index().url },
        { title: 'Form Role' },
    ],
};
