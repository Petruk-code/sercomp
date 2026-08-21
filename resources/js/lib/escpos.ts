/**
 * Encoder ESC/POS minimal untuk printer thermal (58mm/80mm).
 * Menghasilkan Uint8Array siap dikirim via Web Bluetooth / Web Serial.
 */

export type Align = 'left' | 'center' | 'right';

const ESC = 0x1b;
const GS = 0x1d;
const CMD_ALIGN = 0x61; // 'a'
const CMD_BOLD = 0x45; // 'E'
const CMD_FEED = 0x64; // 'd'
const CMD_CUT = 0x56; // 'V'
const CMD_SIZE = 0x21; // '!'
const CMD_INIT = 0x40; // '@'

/** Jumlah karakter per baris sesuai lebar kertas & font normal. */
export const PAPER_WIDTH_CHARS = {
    '58': 32,
    '80': 48,
} as const;

export type PaperWidth = keyof typeof PAPER_WIDTH_CHARS;

/**
 * Karakter di luar Latin-1 diganti '?' karena codepage printer bervariasi.
 */
function encodeText(value: string): number[] {
    const bytes: number[] = [];

    for (const char of value) {
        const code = char.codePointAt(0) ?? 63;

        if (code <= 0xff) {
            bytes.push(code);
        } else {
            bytes.push(63);
        }
    }

    return bytes;
}

export class EscposBuilder {
    private bytes: number[] = [];

    raw(...byte: number[]): this {
        this.bytes.push(...byte);

        return this;
    }

    init(): this {
        return this.raw(ESC, CMD_INIT);
    }

    align(mode: Align): this {
        const value = mode === 'center' ? 1 : mode === 'right' ? 2 : 0;

        return this.raw(ESC, CMD_ALIGN, value);
    }

    bold(enabled: boolean): this {
        return this.raw(ESC, CMD_BOLD, enabled ? 1 : 0);
    }

    /** doubleWidth/doubleHeight memakai GS ! n (ukuran karakter 1–8). */
    size(doubleWidth: boolean, doubleHeight: boolean): this {
        const value = (doubleHeight ? 16 : 0) | (doubleWidth ? 8 : 0);

        return this.raw(GS, CMD_SIZE, value);
    }

    text(value: string): this {
        return this.raw(...encodeText(`${value}\n`));
    }

    feed(lines = 1): this {
        return this.raw(ESC, CMD_FEED, lines);
    }

    cut(): this {
        // Partial cut (GS V 66 0) — lebih aman daripada full cut.
        return this.raw(GS, CMD_CUT, 66, 0);
    }

    /** Baris dua kolom rata kiri-kanan dengan titik-titik penghubung. */
    columns(left: string, right: string, widthChars: number): this {
        const space = widthChars - left.length - right.length;

        if (space < 1) {
            // Terlalu panjang: potong kolom kiri agar tetap satu baris.
            const trimmed = left.slice(
                0,
                Math.max(widthChars - right.length - 1, 0),
            );

            return this.text(`${trimmed} ${right}`);
        }

        return this.text(`${left}${'.'.repeat(space)}${right}`);
    }

    separator(widthChars: number, char = '-'): this {
        return this.text(char.repeat(widthChars));
    }

    build(): Uint8Array {
        return new Uint8Array(this.bytes);
    }
}

/**
 * Pecah data jadi chunk kecil — beberapa printer BLE menolak tulisan besar
 * sekaligus dan butuh jeda antar paket.
 */
export function chunkData(data: Uint8Array, size = 180): Uint8Array[] {
    const chunks: Uint8Array[] = [];

    for (let offset = 0; offset < data.length; offset += size) {
        chunks.push(data.subarray(offset, offset + size));
    }

    return chunks;
}
