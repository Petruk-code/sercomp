import { Head, Link, router } from '@inertiajs/react';
import { Plus, Search, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Users/RoleController';
import { DeleteButton } from '@/components/delete-button';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/roles';
import type { PaginatedData, Role } from '@/types';

type RoleWithUsers = Role & {
    label?: string | null;
    users_count: number;
};

type Props = {
    roles: PaginatedData<RoleWithUsers>;
    filters: { search?: string };
};

export default function RolesIndex({ roles, filters }: Props) {
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
            <Head title="Manajemen Role" />

            <PageHeader
                title="Manajemen Role"
                description="Kelola peran dan hak akses pengguna."
            />

            <div className="mb-4 flex flex-col gap-2 sm:flex-row">
                <div className="relative flex-1">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        placeholder="Cari role..."
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
                <Button asChild className="gap-1">
                    <Link href={RoleController.create.url()}>
                        <Plus />
                        Tambah Role
                    </Link>
                </Button>
            </div>

            <div className="rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-xs text-muted-foreground">
                            <th className="px-4 py-3">Role</th>
                            <th className="px-4 py-3">Label</th>
                            <th className="px-4 py-3">Pengguna</th>
                            <th className="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {roles.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={4}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada role.
                                </td>
                            </tr>
                        )}
                        {roles.data.map((role) => (
                            <tr
                                key={role.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-2 font-medium">
                                        <ShieldCheck className="size-4 text-muted-foreground" />
                                        {role.name}
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    {role.label || role.name}
                                </td>
                                <td className="px-4 py-3">
                                    {role.users_count}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <div className="flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={RoleController.edit.url(
                                                    role.id,
                                                )}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <DeleteButton
                                            action={
                                                RoleController.destroy.form(
                                                    role.id,
                                                ).action
                                            }
                                            message={`Hapus role "${role.name}"? Data yang dihapus tidak dapat dikembalikan.`}
                                        />
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination data={roles} />
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [{ title: 'Manajemen Role', href: index().url }],
};
