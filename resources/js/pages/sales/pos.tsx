import { Head, router } from '@inertiajs/react';
import {
    CloudUpload,
    Minus,
    Plus,
    Search,
    ShoppingCart,
    Trash2,
    WifiOff,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import SaleController from '@/actions/App/Http/Controllers/Sales/SaleController';
import { PageHeader } from '@/components/page-header';
import { PosOfflineDialog } from '@/components/pos-offline-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    clearCartDraft,
    createReference,
    dequeueSale,
    enqueueSale,
    readCartDraft,
    readQueue,
    retrySale,
    saveCartDraft,
    syncPendingSales
    
    
} from '@/lib/offline-sales';
import type {QueuedSale, QueuedSaleItem} from '@/lib/offline-sales';
import { formatCurrency } from '@/lib/utils';
import { index } from '@/routes/sales';
import type { Customer, Product } from '@/types';

type CartItem = QueuedSaleItem;

const storeUrl = SaleController.store.url();

type Props = {
    products: Product[];
    customers: Customer[];
    paymentMethods: string[];
    paymentMethodLabels: Record<string, string>;
};

export default function Pos({
    products,
    customers,
    paymentMethods,
    paymentMethodLabels,
}: Props) {
    const draft = readCartDraft();
    const [search, setSearch] = useState('');
    const [cart, setCart] = useState<CartItem[]>(draft?.cart ?? []);
    const [customerId, setCustomerId] = useState(draft?.customerId ?? '');
    const [discount, setDiscount] = useState(draft?.discount ?? 0);
    const [paymentMethod, setPaymentMethod] = useState(
        draft?.paymentMethod ?? 'cash',
    );
    const [paidAmount, setPaidAmount] = useState(draft?.paidAmount ?? 0);
    const [processing, setProcessing] = useState(false);
    const [isOnline, setIsOnline] = useState(() => navigator.onLine);
    const [queue, setQueue] = useState<QueuedSale[]>(() => readQueue());
    const [syncing, setSyncing] = useState(false);
    const [queueOpen, setQueueOpen] = useState(false);

    const filteredProducts = useMemo(
        () =>
            products.filter(
                (product) =>
                    product.name.toLowerCase().includes(search.toLowerCase()) ||
                    (product.sku ?? '')
                        .toLowerCase()
                        .includes(search.toLowerCase()) ||
                    (product.barcode ?? '')
                        .toLowerCase()
                        .includes(search.toLowerCase()),
            ),
        [products, search],
    );

    const subtotal = cart.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0,
    );
    const total = Math.max(subtotal - discount, 0);
    const change = Math.max(paidAmount - total, 0);

    useEffect(() => {
        saveCartDraft({
            cart,
            customerId,
            discount,
            paymentMethod,
            paidAmount,
        });
    }, [cart, customerId, discount, paymentMethod, paidAmount]);

    const refreshQueue = useCallback(() => setQueue(readQueue()), []);

    const syncQueue = useCallback(async (): Promise<void> => {
        if (!navigator.onLine || syncing) {
            return;
        }

        if (readQueue().every((sale) => sale.status !== 'pending')) {
            return;
        }

        setSyncing(true);

        const result = await syncPendingSales(storeUrl, {
            onSynced: (_sale, invoiceNumber) => {
                toast.success(`Transaksi ${invoiceNumber} berhasil dikirim.`);
            },
            onFailed: (_sale, message) => {
                toast.error(message);
            },
        });

        setSyncing(false);
        refreshQueue();

        if (result) {
            router.reload({ only: ['products'] });
        } else if (!navigator.onLine) {
            toast.info('Masih offline — antrean akan dikirim saat koneksi kembali.');
        }
    }, [refreshQueue, syncing]);

    useEffect(() => {
        const timer = window.setTimeout(() => void syncQueue(), 0);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        const goOnline = () => {
            setIsOnline(true);
            void syncQueue();
        };
        const goOffline = () => setIsOnline(false);

        window.addEventListener('online', goOnline);
        window.addEventListener('offline', goOffline);

        return () => {
            window.removeEventListener('online', goOnline);
            window.removeEventListener('offline', goOffline);
        };
    }, [syncQueue]);

    function addToCart(product: Product) {
        setCart((current) => {
            const existing = current.find(
                (item) => item.product_id === product.id,
            );

            if (existing) {
                return current.map((item) =>
                    item.product_id === product.id
                        ? {
                              ...item,
                              quantity: Math.min(
                                  item.quantity + 1,
                                  product.stock,
                              ),
                          }
                        : item,
                );
            }

            return [
                ...current,
                {
                    product_id: product.id,
                    name: product.name,
                    quantity: 1,
                    price: Number(product.selling_price),
                },
            ];
        });
    }

    function updateQuantity(productId: number, quantity: number) {
        setCart((current) =>
            current
                .map((item) => {
                    if (item.product_id !== productId) {
                        return item;
                    }

                    const product = products.find(
                        (product) => product.id === productId,
                    );
                    const max = product
                        ? product.stock
                        : Number.MAX_SAFE_INTEGER;

                    return {
                        ...item,
                        quantity: Math.min(Math.max(quantity, 1), max),
                    };
                })
                .filter((item) => item.quantity > 0),
        );
    }

    function removeFromCart(productId: number) {
        setCart((current) =>
            current.filter((item) => item.product_id !== productId),
        );
    }

    function resetCart() {
        setCart([]);
        setCustomerId('');
        setDiscount(0);
        setPaidAmount(0);
        clearCartDraft();
    }

    function buildPayload(reference: string) {
        return {
            customer_id: customerId || undefined,
            payment_method: paymentMethod,
            discount,
            paid_amount: paidAmount,
            client_reference: reference,
            items: cart.map((item) => ({
                product_id: item.product_id,
                name: item.name,
                quantity: item.quantity,
                price: item.price,
            })),
        };
    }

    function checkout() {
        if (cart.length === 0 || processing) {
            return;
        }

        setProcessing(true);

        const reference = createReference();
        const payload = buildPayload(reference);

        if (!navigator.onLine) {
            enqueueSale(payload, total);
            refreshQueue();
            toast.info(
                'Anda offline — transaksi disimpan ke antrean dan dikirim otomatis saat online.',
            );
            resetCart();
            setProcessing(false);

            return;
        }

        router.post(storeUrl, payload, {
            preserveScroll: true,
            onSuccess: () => clearCartDraft(),
            onNetworkError: () => {
                enqueueSale(payload, total);
                refreshQueue();
                toast.info(
                    'Koneksi terputus — transaksi disimpan ke antrean offline.',
                );
                resetCart();
            },
            onFinish: () => setProcessing(false),
        });
    }

    return (
        <div className="px-4 pt-4 pb-28 md:px-6 md:pt-6 md:pb-6">
            <Head title="Kasir / POS" />

            <PageHeader
                title="Kasir / POS"
                description="Penjualan produk retail."
            />

            <div className="grid gap-4 md:h-[calc(100svh-11rem)] md:grid-cols-[minmax(0,1fr)_minmax(320px,360px)] md:gap-6">
                <div className="min-w-0 md:flex md:min-h-0 md:flex-col">
                    <div className="mb-3 shrink-0 md:mb-4">
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                className="pl-9"
                                placeholder="Cari produk..."
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                            />
                        </div>
                    </div>

                    <div className="min-w-0 md:min-h-0 md:flex-1 md:overflow-y-auto md:pr-1">
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {filteredProducts.map((product) => {
                                const inCart =
                                    cart.find(
                                        (item) =>
                                            item.product_id === product.id,
                                    )?.quantity ?? 0;
                                const outOfStock = inCart >= product.stock;

                                return (
                                    <button
                                        key={product.id}
                                        type="button"
                                        onClick={() => addToCart(product)}
                                        disabled={
                                            product.stock === 0 || outOfStock
                                        }
                                        className="flex items-center justify-between gap-3 rounded-xl border p-4 text-left transition hover:border-primary/50 hover:bg-accent/50 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {product.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Stok {product.stock} ·{' '}
                                                {product.category?.name ??
                                                    'Umum'}
                                            </p>
                                            {product.stock === 0 && (
                                                <p className="text-xs font-medium text-destructive">
                                                    Habis
                                                </p>
                                            )}
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm font-semibold">
                                                {formatCurrency(
                                                    product.selling_price,
                                                )}
                                            </p>
                                            {inCart > 0 && (
                                                <p className="text-xs text-muted-foreground">
                                                    {inCart} di keranjang
                                                </p>
                                            )}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>

                <div
                    id="pos-cart"
                    className="scroll-mt-24 space-y-4 md:flex md:min-h-0 md:flex-col md:gap-4 md:space-y-0 md:overflow-y-auto"
                >
                    <div className="rounded-xl border md:flex md:min-h-0 md:flex-1 md:flex-col md:overflow-hidden">
                        <div className="flex shrink-0 items-center justify-between border-b px-5 py-4">
                            <h3 className="flex items-center gap-2 font-semibold">
                                <ShoppingCart className="size-4" />
                                Keranjang
                            </h3>
                            <div className="flex items-center gap-2">
                                {!isOnline && (
                                    <span className="flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                                        <WifiOff className="size-3" />
                                        Offline
                                    </span>
                                )}
                                {queue.length > 0 && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setQueueOpen(true)}
                                    >
                                        <CloudUpload />
                                        Antrean ({queue.length})
                                    </Button>
                                )}
                                <span className="text-sm text-muted-foreground">
                                    {cart.length} item
                                </span>
                            </div>
                        </div>

                        <div className="max-h-72 min-h-0 space-y-3 overflow-y-auto p-5 md:max-h-none md:flex-1">
                            {cart.length === 0 && (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    Klik produk untuk menambahkan.
                                </p>
                            )}
                            {cart.map((item) => (
                                <div
                                    key={item.product_id}
                                    className="flex items-center justify-between gap-3 text-sm"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">
                                            {item.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatCurrency(item.price)} x{' '}
                                            {item.quantity}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            className="size-7"
                                            onClick={() =>
                                                item.product_id &&
                                                updateQuantity(
                                                    item.product_id,
                                                    item.quantity - 1,
                                                )
                                            }
                                        >
                                            <Minus />
                                        </Button>
                                        <span className="w-6 text-center">
                                            {item.quantity}
                                        </span>
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            className="size-7"
                                            onClick={() =>
                                                item.product_id &&
                                                updateQuantity(
                                                    item.product_id,
                                                    item.quantity + 1,
                                                )
                                            }
                                        >
                                            <Plus />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-7 text-destructive"
                                            onClick={() =>
                                                item.product_id &&
                                                removeFromCart(item.product_id)
                                            }
                                        >
                                            <Trash2 />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-xl border p-5">
                        <div className="space-y-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="customer_id">Pelanggan</Label>
                                <select
                                    id="customer_id"
                                    value={customerId}
                                    onChange={(event) =>
                                        setCustomerId(event.target.value)
                                    }
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                >
                                    <option value="">
                                        Umum (tanpa pelanggan)
                                    </option>
                                    {customers.map((customer) => (
                                        <option
                                            key={customer.id}
                                            value={customer.id}
                                        >
                                            {customer.name}
                                            {customer.phone
                                                ? ` (${customer.phone})`
                                                : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="payment_method">
                                    Metode Pembayaran
                                </Label>
                                <select
                                    id="payment_method"
                                    value={paymentMethod}
                                    onChange={(event) =>
                                        setPaymentMethod(event.target.value)
                                    }
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                                >
                                    {paymentMethods.map((method) => (
                                        <option key={method} value={method}>
                                            {paymentMethodLabels[method]}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="discount">Diskon (Rp)</Label>
                                <Input
                                    id="discount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={discount}
                                    onChange={(event) =>
                                        setDiscount(Number(event.target.value))
                                    }
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="paid_amount">
                                    Dibayar (Rp)
                                </Label>
                                <Input
                                    id="paid_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={paidAmount}
                                    onChange={(event) =>
                                        setPaidAmount(
                                            Number(event.target.value),
                                        )
                                    }
                                />
                            </div>

                            <dl className="space-y-1.5 border-t pt-3 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">
                                        Subtotal
                                    </dt>
                                    <dd>{formatCurrency(subtotal)}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">
                                        Diskon
                                    </dt>
                                    <dd>-{formatCurrency(discount)}</dd>
                                </div>
                                <div className="flex justify-between text-base font-semibold">
                                    <dt>Total</dt>
                                    <dd>{formatCurrency(total)}</dd>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <dt className="text-muted-foreground">
                                        Kembalian
                                    </dt>
                                    <dd>{formatCurrency(change)}</dd>
                                </div>
                            </dl>

                            <Button
                                className="w-full"
                                size="lg"
                                onClick={checkout}
                                disabled={processing || cart.length === 0}
                            >
                                {processing
                                    ? 'Memproses...'
                                    : `Bayar ${formatCurrency(total)}`}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="fixed inset-x-0 bottom-0 z-50 border-t bg-background px-4 py-3 md:hidden">
                <div className="flex items-center justify-between gap-3">
                    <div className="min-w-0">
                        <p className="text-xs text-muted-foreground">
                            Total · {cart.length} item
                        </p>
                        <p className="text-lg font-semibold">
                            {formatCurrency(total)}
                        </p>
                    </div>
                    <Button
                        size="lg"
                        className="shrink-0"
                        onClick={() =>
                            document
                                .getElementById('pos-cart')
                                ?.scrollIntoView({ behavior: 'smooth' })
                        }
                    >
                        Keranjang & Bayar
                    </Button>
                </div>
            </div>

            <PosOfflineDialog
                open={queueOpen}
                onOpenChange={setQueueOpen}
                items={queue}
                syncing={syncing}
                onSyncAll={() => void syncQueue()}
                onRetry={(reference) => {
                    retrySale(reference);
                    refreshQueue();
                    void syncQueue();
                }}
                onDiscard={(reference) => {
                    dequeueSale(reference);
                    refreshQueue();
                }}
            />
        </div>
    );
}

Pos.layout = {
    breadcrumbs: [
        { title: 'Transaksi', href: index().url },
        { title: 'Kasir / POS', href: index().url },
    ],
};
