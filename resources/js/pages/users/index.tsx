import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/Users/UserController';
import { DeleteButton } from '@/components/delete-button';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/utils';
import { index as rolesIndex } from '@/routes/roles';
import { index } from '@/routes/users';
import type { PaginatedData, Role, UserWithRole } from '@/types';

type Props = {
    users: PaginatedData<UserWithRole>;
    roles: Role[];
    filters: { search?: string };
};

export default function UsersIndex({ users, roles, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    function applyFilters() {
        router.get(
            index().url,
            { search: search || undefined },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Pengguna" />

            <PageHeader
                title="Pengguna"
                description="Kelola pengguna dan peran mereka."
            />

            <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari pengguna..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && applyFilters()
                        }
                    />
                </div>
                <Button variant="secondary" onClick={applyFilters}>
                    Terapkan
                </Button>
                <Button variant="outline" asChild className="gap-1">
                    <Link href={rolesIndex().url}>
                        <ShieldCheck />
                        Manajemen Role
                    </Link>
                </Button>
                <Button asChild className="gap-1">
                    <Link href={UserController.create.url()}>
                        <Plus />
                        Tambah Pengguna
                    </Link>
                </Button>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">Nama</th>
                            <th className="px-4 py-3">Email</th>
                            <th className="px-4 py-3">Role</th>
                            <th className="px-4 py-3">Terdaftar</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada pengguna.
                                </td>
                            </tr>
                        )}
                        {users.data.map((user) => (
                            <tr
                                key={user.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3 font-medium">
                                    {user.name}
                                </td>
                                <td className="px-4 py-3">{user.email}</td>
                                <td className="px-4 py-3">
                                    <select
                                        defaultValue={user.roles[0]?.name ?? ''}
                                        onChange={(event) =>
                                            router.put(
                                                UserController.updateRole.url(
                                                    user.id,
                                                ),
                                                { role: event.target.value },
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                                    >
                                        {roles.map((role) => (
                                            <option
                                                key={role.id}
                                                value={role.name}
                                            >
                                                {role.label || role.name}
                                            </option>
                                        ))}
                                    </select>
                                </td>
                                <td className="px-4 py-3">
                                    {formatDate(user.created_at)}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={UserController.edit.url(
                                                    user.id,
                                                )}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <DeleteButton
                                            action={
                                                UserController.destroy.form(
                                                    user.id,
                                                ).action
                                            }
                                            message={`Hapus pengguna "${user.name}"? Data yang dihapus tidak dapat dikembalikan.`}
                                        />
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination data={users} />
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: 'Pengguna', href: index().url }],
};
