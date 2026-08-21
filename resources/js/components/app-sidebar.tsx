import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Boxes,
    Building2,
    LayoutGrid,
    Package,
    Receipt,
    ScrollText,
    ShieldCheck,
    ShoppingCart,
    Sparkles,
    Tag,
    Truck,
    Undo2,
    UserCog,
    Users,
    Wrench,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

type NavGroup = {
    label: string;
    items: NavItem[];
};

export function AppSidebar() {
    const { auth } = usePage<{
        auth: { user: { roles?: Array<{ name: string }> } };
    }>().props;

    const roles = new Set((auth.user.roles ?? []).map((role) => role.name));

    const is = (...allowed: string[]): boolean =>
        allowed.some((role) => roles.has(role));

    const groups: NavGroup[] = [
        {
            label: 'Utama',
            items: [
                {
                    title: 'Dashboard',
                    href: dashboard(),
                    icon: LayoutGrid,
                },
            ],
        },
        {
            label: 'Operasional',
            items: [
                {
                    title: 'Kasir / POS',
                    href: '/pos',
                    icon: ShoppingCart,
                },
                {
                    title: 'Transaksi',
                    href: '/sales',
                    icon: Receipt,
                },
                {
                    title: 'Manajemen Servis',
                    href: '/services',
                    icon: Wrench,
                },
                {
                    title: 'Pembelian Supplier',
                    href: '/supplier/purchases',
                    icon: Boxes,
                },
                {
                    title: 'Retur Pembelian',
                    href: '/supplier/returns',
                    icon: Undo2,
                },
            ].filter((item) => {
                if (item.href === '/pos' || item.href === '/sales') {
                    return is('admin', 'owner', 'cashier');
                }

                if (
                    item.href === '/supplier/purchases' ||
                    item.href === '/supplier/returns'
                ) {
                    return is('admin', 'owner');
                }

                return true;
            }),
        },
        {
            label: 'Master Data',
            items: [
                { title: 'Kategori', href: '/categories', icon: Tag },
                { title: 'Produk', href: '/products', icon: Package },
                {
                    title: 'Jasa Servis',
                    href: '/catalog/services',
                    icon: Sparkles,
                },
                { title: 'Pelanggan', href: '/customers', icon: Users },
                { title: 'Supplier', href: '/suppliers', icon: Truck },
                { title: 'Pengguna', href: '/users', icon: UserCog },
                { title: 'Manajemen Role', href: '/roles', icon: ShieldCheck },
                { title: 'Cabang', href: '/stores', icon: Building2 },
            ].filter((item) => {
                if (item.href === '/customers') {
                    return is('admin', 'owner', 'cashier');
                }

                return is('admin', 'owner');
            }),
        },
        {
            label: 'Laporan',
            items: [
                { title: 'Penjualan', href: '/reports/sales', icon: BarChart3 },
                { title: 'Servis', href: '/reports/services', icon: BarChart3 },
                { title: 'Stok', href: '/reports/stock', icon: BarChart3 },
                {
                    title: 'Pembelian',
                    href: '/reports/purchases',
                    icon: BarChart3,
                },
                {
                    title: 'Hutang Supplier',
                    href: '/reports/debts',
                    icon: BarChart3,
                },
            ].filter(() => is('admin', 'owner')),
        },
        {
            label: 'Sistem',
            items: [
                {
                    title: 'Audit Log',
                    href: '/audit-logs',
                    icon: ScrollText,
                },
            ].filter(() => is('admin', 'owner')),
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {groups.map(
                    (group) =>
                        group.items.length > 0 && (
                            <NavMain
                                key={group.label}
                                label={group.label}
                                items={group.items}
                            />
                        ),
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
