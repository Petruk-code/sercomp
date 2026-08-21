import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { active as updateActive } from '@/routes/stores';

type StoreOption = { id: number; name: string };

export function StoreSwitcher() {
    const { stores, activeStoreId } = usePage<{
        stores: StoreOption[];
        activeStoreId: number | null;
    }>().props;

    const [pending, setPending] = useState(false);

    if (stores.length < 2) {
        return null;
    }

    const active = stores.find((store) => store.id === activeStoreId);

    function choose(storeId: number) {
        if (storeId === activeStoreId || pending) {
            return;
        }

        setPending(true);
        router.put(updateActive.url(), { store_id: storeId }, {
            onFinish: () => setPending(false),
        });
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="gap-1.5"
                    disabled={pending}
                >
                    <Building2 className="size-4" />
                    <span className="max-w-40 truncate">
                        {active?.name ?? 'Pilih cabang'}
                    </span>
                    <ChevronsUpDown className="size-3.5 opacity-50" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
                <DropdownMenuLabel>Cabang aktif</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {stores.map((store) => (
                    <DropdownMenuItem
                        key={store.id}
                        onClick={() => choose(store.id)}
                        className="justify-between"
                    >
                        {store.name}
                        {store.id === activeStoreId && <Check />}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
