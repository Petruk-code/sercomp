import { Form, Head, Link } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Master/CategoryController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/master/categories';
import type { Category } from '@/types';

type Props = {
    category: Category | null;
    types: string[];
};

export default function CategoryForm({ category, types }: Props) {
    return (
        <>
            <Head title={category ? 'Edit Kategori' : 'Tambah Kategori'} />

            <PageHeader
                title={category ? 'Edit Kategori' : 'Tambah Kategori'}
                description={
                    category
                        ? `Memperbarui kategori ${category.name}.`
                        : 'Buat kategori produk atau jasa baru.'
                }
            />

            <Form
                {...(category
                    ? CategoryController.update.form(category.id)
                    : CategoryController.store.form())}
                className="max-w-xl space-y-6 rounded-xl border p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={category?.name ?? ''}
                                placeholder="cth. Laptop, Perawatan Rutin"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="type">Tipe</Label>
                            <select
                                id="type"
                                name="type"
                                defaultValue={category?.type ?? 'product'}
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                            >
                                {types.map((type) => (
                                    <option key={type} value={type}>
                                        {type === 'product' ? 'Produk' : 'Jasa'}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.type} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <textarea
                                id="description"
                                name="description"
                                defaultValue={category?.description ?? ''}
                                className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                placeholder="Deskripsi singkat (opsional)"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={processing}>
                                {category
                                    ? 'Simpan Perubahan'
                                    : 'Simpan Kategori'}
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

CategoryForm.layout = {
    breadcrumbs: [
        { title: 'Master Data', href: index().url },
        { title: 'Kategori', href: index().url },
    ],
};
