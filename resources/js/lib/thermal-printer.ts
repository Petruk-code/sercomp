/**
 * Transport pengirim data ESC/POS ke printer thermal:
 * - Web Bluetooth (printer thermal BLE, service standar 18F0)
 * - Web Serial (printer terhubung kabel USB via mode serial)
 *
 * Preferensi (lebar kertas & transport terakhir) disimpan di localStorage.
 */

import { chunkData } from '@/lib/escpos';
import type { PaperWidth } from '@/lib/escpos';

const ESCPOS_SERVICE = 0x18f0;
const CHUNK_DELAY_MS = 20;
const SERIAL_BAUD_RATE = 9600;

const WIDTH_KEY = 'sercomp.thermal.paper-width';
const TRANSPORT_KEY = 'sercomp.thermal.transport';

export type ThermalTransport = 'bluetooth' | 'serial';

export function bluetoothSupported(): boolean {
    return typeof navigator !== 'undefined' && 'bluetooth' in navigator;
}

export function serialSupported(): boolean {
    return typeof navigator !== 'undefined' && 'serial' in navigator;
}

export function readPaperWidth(): PaperWidth {
    try {
        const value = window.localStorage.getItem(WIDTH_KEY);

        if (value === '58' || value === '80') {
            return value;
        }
    } catch {
        // abaikan
    }

    return '80';
}

export function savePaperWidth(width: PaperWidth): void {
    try {
        window.localStorage.setItem(WIDTH_KEY, width);
    } catch {
        // abaikan
    }
}

export function readLastTransport(): ThermalTransport | null {
    try {
        const value = window.localStorage.getItem(TRANSPORT_KEY);

        if (value === 'bluetooth' || value === 'serial') {
            return value;
        }
    } catch {
        // abaikan
    }

    return null;
}

export function saveLastTransport(transport: ThermalTransport): void {
    try {
        window.localStorage.setItem(TRANSPORT_KEY, transport);
    } catch {
        // abaikan
    }
}

function delay(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

type GattCharacteristicLike = {
    properties: {
        write: boolean;
        writeWithoutResponse: boolean;
    };
    writeValue(value: Uint8Array): Promise<void>;
    writeValueWithoutResponse?(value: Uint8Array): Promise<void>;
};

type GattServerLike = {
    connect(): Promise<{
        getPrimaryService(service: number): Promise<{
            getCharacteristics(): Promise<unknown[]>;
        }>;
        disconnect(): void;
    }>;
};

type BluetoothDeviceLike = {
    gatt?: GattServerLike;
};

type BluetoothLike = {
    requestDevice(options: {
        filters: Array<{ services: number[] }>;
        optionalServices: number[];
    }): Promise<BluetoothDeviceLike>;
    getDevices?(): Promise<BluetoothDeviceLike[]>;
};

/**
 * Kirim data via Web Bluetooth ke printer dengan service ESC/POS (18F0).
 * Memakai perangkat yang sudah diberi izin sebelumnya bila ada.
 */
export async function printViaBluetooth(data: Uint8Array): Promise<void> {
    const bluetooth = (navigator as unknown as { bluetooth?: BluetoothLike })
        .bluetooth;

    if (!bluetooth) {
        throw new Error('Web Bluetooth tidak didukung browser ini.');
    }

    let device: BluetoothDeviceLike | undefined;

    if ('getDevices' in bluetooth) {
        try {
            const granted = await bluetooth.getDevices?.();

            device = granted?.[0];
        } catch {
            // abaikan, lanjut minta pemilihan perangkat
        }
    }

    device ??= await bluetooth.requestDevice({
        filters: [{ services: [ESCPOS_SERVICE] }],
        optionalServices: [ESCPOS_SERVICE],
    });

    const server = await device.gatt?.connect();

    if (!server) {
        throw new Error('Tidak bisa terhubung ke printer.');
    }

    try {
        const service = await server.getPrimaryService(ESCPOS_SERVICE);
        const characteristics = await service.getCharacteristics();
        const characteristic = (
            characteristics as GattCharacteristicLike[]
        ).find(
            (item) =>
                item.properties.write || item.properties.writeWithoutResponse,
        );

        if (!characteristic) {
            throw new Error('Karakteristik penulisan printer tidak ditemukan.');
        }

        for (const chunk of chunkData(data)) {
            if (
                characteristic.properties.writeWithoutResponse &&
                characteristic.writeValueWithoutResponse
            ) {
                await characteristic.writeValueWithoutResponse(chunk);
            } else {
                await characteristic.writeValue(chunk);
            }

            await delay(CHUNK_DELAY_MS);
        }
    } finally {
        server.disconnect();
    }
}

type SerialPortLike = {
    open(options: { baudRate: number }): Promise<void>;
    close(): Promise<void>;
    writable: {
        getWriter(): {
            write(chunk: Uint8Array): Promise<void>;
            releaseLock(): void;
        };
    };
};

type SerialLike = {
    getPorts(): Promise<SerialPortLike[]>;
    requestPort(): Promise<SerialPortLike>;
};

function serial(): SerialLike | null {
    if (!serialSupported()) {
        return null;
    }

    return (navigator as unknown as { serial: SerialLike }).serial;
}

/**
 * Kirim data via Web Serial (kabel USB ke printer dengan mode serial).
 * Memakai port yang sudah diberi izin sebelumnya bila ada.
 */
export async function printViaSerial(data: Uint8Array): Promise<void> {
    const serialApi = serial();

    if (!serialApi) {
        throw new Error('Web Serial tidak didukung browser ini.');
    }

    let port: SerialPortLike | undefined;

    try {
        const ports = await serialApi.getPorts();

        port = ports[0];
    } catch {
        // abaikan
    }

    port ??= await serialApi.requestPort();
    await port.open({ baudRate: SERIAL_BAUD_RATE });

    const writer = port.writable.getWriter();

    try {
        for (const chunk of chunkData(data)) {
            await writer.write(chunk);
            await delay(CHUNK_DELAY_MS);
        }
    } finally {
        writer.releaseLock();
        await port.close();
    }
}
