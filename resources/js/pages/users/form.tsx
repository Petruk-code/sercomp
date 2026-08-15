import { Form, Head, Link } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/Users/UserController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/users';
import type { Role, User } from '@/types';

type Props = {
    user: Pick<User, 'id' | 'name' | 'email'> | null;
    currentRole?: string | null;
    roles: Role[];
};

export default function UserForm({ user, currentRole, roles }: Props) {
    return (
        <>
            <Head title={user ? 'Edit Pengguna' : 'Tambah Pengguna'} />

            <PageHeader
                title={user ? 'Edit Pengguna' : 'Tambah Pengguna'}
                description={
                    user
                        ? `Memperbarui pengguna ${user.name}.`
                        : 'Buat akun pengguna baru.'
                }
            />

            <Form
                {...(user
                    ? UserController.update.form(user.id)
                    : UserController.store.form())}
                className="max-w-xl space-y-6 rounded-xl border p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={user?.name ?? ''}
                                placeholder="cth. Budi Santoso"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                defaultValue={user?.email ?? ''}
                                placeholder="cth. budi@sercomp.test"
                                required
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="role">Role</Label>
                            <select
                                id="role"
                                name="role"
                                defaultValue={user ? (currentRole ?? '') : ''}
                                required
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                            >
                                {!user && (
                                    <option value="">Pilih role...</option>
                                )}
                                {roles.map((role) => (
                                    <option key={role.id} value={role.name}>
                                        {role.label || role.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.role} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                Password
                                {user && ' (kosongkan jika tidak diubah)'}
                            </Label>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                autoComplete="new-password"
                                required={!user}
                                placeholder="••••••••"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Konfirmasi Password
                            </Label>
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                required={!user}
                                placeholder="••••••••"
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={processing}>
                                {user ? 'Simpan Perubahan' : 'Simpan Pengguna'}
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

UserForm.layout = {
    breadcrumbs: [
        { title: 'Pengguna', href: index().url },
        { title: 'Form Pengguna' },
    ],
};
