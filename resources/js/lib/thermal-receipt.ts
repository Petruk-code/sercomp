import { EscposBuilder, PAPER_WIDTH_CHARS } from '@/lib/escpos';
import type { PaperWidth } from '@/lib/escpos';
import { formatCurrency, formatDate } from '@/lib/utils';
import type { Sale, ServiceOrder } from '@/types';

export const STORE_NAME = import.meta.env.VITE_APP_NAME || 'Servis Pos';

/** Bungkus teks panjang ke beberapa baris selebar kertas. */
export function wrapText(value: string, widthChars: number): string[] {
    const lines: string[] = [];

    for (const paragraph of value.split('\n')) {
        let current = '';

        for (const word of paragraph.split(/\s+/).filter(Boolean)) {
            const candidate = current ? `${current} ${word}` : word;

            if (candidate.length <= widthChars) {
                current = candidate;

                continue;
            }

            if (current) {
                lines.push(current);
            }

            // Kata melebihi lebar: paksa potong per karakter.
            let rest = word;

            while (rest.length > widthChars) {
                lines.push(rest.slice(0, widthChars));
                rest = rest.slice(widthChars);
            }

            current = rest;
        }

        if (current) {
            lines.push(current);
        }
    }

    return lines.length > 0 ? lines : [''];
}

type SaleReceiptOptions = {
    paymentMethodLabels: Record<string, string>;
    paymentStatusLabels: Record<string, string>;
};

export function buildSaleReceipt(
    sale: Sale,
    options: SaleReceiptOptions,
    paperWidth: PaperWidth,
): Uint8Array {
    const width = PAPER_WIDTH_CHARS[paperWidth];
    const printer = new EscposBuilder().init();

    printer.align('center').bold(true).size(true, true).text(STORE_NAME);
    printer.size(false, false).bold(false).text('NOTA PENJUALAN');
    printer.align('left').separator(width);

    printer.columns('No', sale.invoice_number, width);
    printer.columns('Tanggal', formatDate(sale.created_at), width);
    printer.columns('Kasir', sale.cashier?.name ?? '-', width);
    printer.columns('Pelanggan', sale.customer?.name ?? '-', width);

    if (sale.serviceOrder) {
        printer.columns('No. Servis', sale.serviceOrder.invoice_number, width);
    }

    printer.separator(width);

    for (const item of sale.items ?? []) {
        for (const line of wrapText(item.name, width)) {
            printer.text(line);
        }

        printer.columns(
            `  ${item.quantity} x ${formatCurrency(item.price)}`,
            formatCurrency(item.subtotal),
            width,
        );
    }

    printer.separator(width);
    printer.columns('Subtotal', formatCurrency(sale.subtotal), width);

    if (Number(sale.discount) > 0) {
        printer.columns('Diskon', `-${formatCurrency(sale.discount)}`, width);
    }

    printer.bold(true).size(true, false);
    printer.columns('TOTAL', formatCurrency(sale.total), width);
    printer.bold(false).size(false, false);
    printer.separator(width);

    printer.columns(
        'Metode',
        options.paymentMethodLabels[sale.payment_method] ?? sale.payment_method,
        width,
    );
    printer.columns(
        'Status',
        options.paymentStatusLabels[sale.payment_status] ?? sale.payment_status,
        width,
    );
    printer.columns('Dibayar', formatCurrency(sale.paid_amount), width);

    const change = Math.max(Number(sale.paid_amount) - Number(sale.total), 0);
    printer.columns('Kembalian', formatCurrency(change), width);

    printer.separator(width);
    printer.align('center');

    for (const line of wrapText(
        'Terima kasih atas kunjungan Anda. Barang yang sudah dibeli tidak dapat dikembalikan kecuali ada kesepakatan.',
        width,
    )) {
        printer.text(line);
    }

    printer.feed(3).cut();

    return printer.build();
}

type ServiceReceiptOptions = {
    statusLabels: Record<string, string>;
    trackingUrl: string;
};

export function buildServiceReceipt(
    order: ServiceOrder,
    options: ServiceReceiptOptions,
    paperWidth: PaperWidth,
): Uint8Array {
    const width = PAPER_WIDTH_CHARS[paperWidth];
    const printer = new EscposBuilder().init();

    printer.align('center').bold(true).size(true, true).text(STORE_NAME);
    printer.size(false, false).bold(false).text('TANDA TERIMA SERVIS');
    printer.align('left').separator(width);

    printer.columns('No. Servis', order.invoice_number, width);
    printer.columns('Masuk', formatDate(order.created_at), width);
    printer.columns(
        'Estimasi',
        order.estimated_completion_at
            ? formatDate(order.estimated_completion_at)
            : '-',
        width,
    );
    printer.columns(
        'Status',
        options.statusLabels[order.status] ?? order.status,
        width,
    );
    printer.columns('Diterima', order.receiver?.name ?? '-', width);
    printer.columns(
        'Teknisi',
        order.technician?.name ?? 'Belum ditugaskan',
        width,
    );

    printer.separator(width);
    printer.columns('Pelanggan', order.customer?.name ?? '-', width);
    printer.columns('No. HP', order.customer?.phone ?? '-', width);

    for (const line of wrapText(`Unit: ${order.item_name}`, width)) {
        printer.text(line);
    }

    if (order.serial_number) {
        printer.columns('Serial', order.serial_number, width);
    }

    for (const line of wrapText(`Keluhan: ${order.complaint}`, width)) {
        printer.text(line);
    }

    if (order.physical_condition) {
        for (const line of wrapText(
            `Kondisi: ${order.physical_condition}`,
            width,
        )) {
            printer.text(line);
        }
    }

    if (order.notes) {
        for (const line of wrapText(`Catatan: ${order.notes}`, width)) {
            printer.text(line);
        }
    }

    printer.columns(
        'Estimasi Biaya',
        formatCurrency(order.estimated_cost),
        width,
    );

    printer.separator(width);
    printer.align('center');

    for (const line of wrapText(
        'Simpan tanda terima ini dan bawa saat pengambilan unit.',
        width,
    )) {
        printer.text(line);
    }

    for (const line of wrapText(
        `Lacak servis: ${options.trackingUrl}`,
        width,
    )) {
        printer.text(line);
    }

    printer.feed(3).cut();

    return printer.build();
}
