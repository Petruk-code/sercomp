/**
 * Antrean transaksi POS offline.
 *
 * Keranjang kasir disimpan sebagai draf di localStorage agar selamat dari
 * refresh/tabrakan, dan transaksi yang gagal terkirim (offline) masuk antrean
 * lalu dikirim ulang otomatis saat koneksi kembali. Idempotensi dijamin
 * lewat `client_reference` yang unik per transaksi (server menolak duplikat).
 */

export type QueuedSaleStatus = 'pending' | 'failed';

export type QueuedSaleItem = {
    product_id: number | null;
    name: string;
    quantity: number;
    price: number;
};

export type QueuedSalePayload = {
    customer_id?: string;
    payment_method: string;
    discount: number;
    paid_amount: number;
    client_reference: string;
    items: QueuedSaleItem[];
};

export type QueuedSale = {
    reference: string;
    payload: QueuedSalePayload;
    total: number;
    queuedAt: string;
    status: QueuedSaleStatus;
    error?: string;
};

const QUEUE_KEY = 'sercomp.pos.offline-sales';
const DRAFT_KEY = 'sercomp.pos.cart-draft';

export type CartDraft = {
    cart: QueuedSaleItem[];
    customerId: string;
    discount: number;
    paymentMethod: string;
    paidAmount: number;
};

function readJson<T>(key: string, fallback: T): T {
    try {
        const raw = window.localStorage.getItem(key);

        if (raw === null) {
            return fallback;
        }

        return JSON.parse(raw) as T;
    } catch {
        return fallback;
    }
}

function writeJson(key: string, value: unknown): void {
    try {
        window.localStorage.setItem(key, JSON.stringify(value));
    } catch {
        // Penyimpanan penuh/blokir — antrean bersifat best-effort.
    }
}

export function readQueue(): QueuedSale[] {
    return readJson<QueuedSale[]>(QUEUE_KEY, []);
}

function writeQueue(queue: QueuedSale[]): void {
    writeJson(QUEUE_KEY, queue);
}

export function createReference(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `pos-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

export function enqueueSale(payload: QueuedSalePayload, total: number): QueuedSale {
    const sale: QueuedSale = {
        reference: payload.client_reference,
        payload,
        total,
        queuedAt: new Date().toISOString(),
        status: 'pending',
    };

    writeQueue([sale, ...readQueue()]);

    return sale;
}

export function dequeueSale(reference: string): void {
    writeQueue(readQueue().filter((sale) => sale.reference !== reference));
}

export function markSaleFailed(reference: string, error: string): void {
    writeQueue(
        readQueue().map((sale) =>
            sale.reference === reference
                ? { ...sale, status: 'failed' as const, error }
                : sale,
        ),
    );
}

export function retrySale(reference: string): void {
    writeQueue(
        readQueue().map((sale) =>
            sale.reference === reference
                ? { ...sale, status: 'pending' as const, error: undefined }
                : sale,
        ),
    );
}

export function readCartDraft(): CartDraft | null {
    return readJson<CartDraft | null>(DRAFT_KEY, null);
}

export function saveCartDraft(draft: CartDraft): void {
    writeJson(DRAFT_KEY, draft);
}

export function clearCartDraft(): void {
    try {
        window.localStorage.removeItem(DRAFT_KEY);
    } catch {
        // abaikan
    }
}

function xsrfToken(): string | null {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : null;
}

type SyncHandlers = {
    onSynced: (sale: QueuedSale, invoiceNumber: string) => void;
    onFailed: (sale: QueuedSale, message: string) => void;
};

/**
 * Kirim semua transaksi berstatus "pending" satu per satu (urut terlama).
 * Berhenti (return false) bila masih offline atau server bermasalah —
 * antrean tidak dibuang agar bisa dicoba lagi nanti.
 */
export async function syncPendingSales(
    storeUrl: string,
    handlers: SyncHandlers,
): Promise<boolean> {
    const pending = readQueue()
        .filter((sale) => sale.status === 'pending')
        .sort((a, b) => a.queuedAt.localeCompare(b.queuedAt));

    for (const sale of pending) {
        let response: Response;

        try {
            const token = xsrfToken();

            response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Offline-Sync': '1',
                    ...(token ? { 'X-XSRF-TOKEN': token } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify(sale.payload),
            });
        } catch {
            return false;
        }

        if (response.status === 422) {
            let message = 'Transaksi ditolak server.';

            try {
                const body = (await response.json()) as { message?: string };

                if (body.message) {
                    message = body.message;
                }
            } catch {
                // pakai pesan default
            }

            markSaleFailed(sale.reference, message);
            handlers.onFailed(sale, message);

            continue;
        }

        if (!response.ok) {
            return false;
        }

        let invoiceNumber = sale.reference;

        try {
            const body = (await response.json()) as { invoice_number?: string };

            if (body.invoice_number) {
                invoiceNumber = body.invoice_number;
            }
        } catch {
            // fallback ke reference
        }

        dequeueSale(sale.reference);
        handlers.onSynced(sale, invoiceNumber);
    }

    return true;
}
