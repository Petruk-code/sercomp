import { Form, Head, Link } from '@inertiajs/react';
import ServiceCatalogController from '@/actions/App/Http/Controllers/Master/ServiceCatalogController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/master/services';
import type { ServiceCatalog } from '@/types';

type Props = {
    service: ServiceCatalog | null;
    categories: { id: number; name: string }[];
};

export default function ServiceForm({ service, categories }: Props) {
    return (
        <>
            <Head title={service ? 'Edit Jasa' : 'Tambah Jasa'} />

            <PageHeader
                title={service ? 'Edit Jasa' : 'Tambah Jasa'}
                description={
                    service
                        ? `Memperbarui jasa ${service.name}.`
                        : 'Buat jasa servis baru.'
                }
            />

            <Form
                {...(service
                    ? ServiceCatalogController.update.form(service.id)
                    : ServiceCatalogController.store.form())}
                className="max-w-xl space-y-6 rounded-xl border p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={service?.name ?? ''}
                                placeholder="cth. Perawatan Rutin"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="category_id">Kategori</Label>
                            <select
                                id="category_id"
                                name="category_id"
                                defaultValue={service?.category_id ?? ''}
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                            >
                                <option value="">Tanpa kategori</option>
                                {categories.map((category) => (
                                    <option
                                        key={category.id}
                                        value={category.id}
                                    >
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.category_id} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="price">Biaya</Label>
                                <Input
                                    id="price"
                                    name="price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    defaultValue={service?.price ?? ''}
                                />
                                <InputError message={errors.price} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="estimated_minutes">
                                    Estimasi (menit)
                                </Label>
                                <Input
                                    id="estimated_minutes"
                                    name="estimated_minutes"
                                    type="number"
                                    min="0"
                                    defaultValue={
                                        service?.estimated_minutes ?? ''
                                    }
                                />
                                <InputError
                                    message={errors.estimated_minutes}
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <textarea
                                id="description"
                                name="description"
                                defaultValue={service?.description ?? ''}
                                className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                placeholder="Deskripsi singkat (opsional)"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="flex items-center gap-2">
                            <Button type="submit" disabled={processing}>
                                {service ? 'Simpan Perubahan' : 'Simpan Jasa'}
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

ServiceForm.layout = {
    breadcrumbs: [
        { title: 'Master Data', href: index().url },
        { title: 'Jasa Servis', href: index().url },
    ],
};
