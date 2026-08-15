<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\ServiceCatalog;
use App\Models\ServiceOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Support\InvoiceNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Seed a realistic set of demo data spanning the whole application.
     */
    public function run(): void
    {
        $this->seedUsers();
        $this->seedCategories();
        $this->seedSuppliers();
        $this->seedCustomers();
        $this->seedProducts();
        $this->seedServiceCatalog();
        $this->seedServiceOrders();
        $this->seedRetailSales();
        $this->seedPurchases();
        $this->seedPurchaseReturns();
    }

    /**
     * Demo accounts: admin, owner, cashiers and technicians.
     */
    private function seedUsers(): void
    {
        $users = [
            ['name' => 'Admin Utama', 'email' => 'admin@sercomp.test', 'role' => 'admin', 'phone' => '088224130806'],
            ['name' => 'Pemilik Toko', 'email' => 'owner@sercomp.test', 'role' => 'owner'],
            ['name' => 'Andi Kasir', 'email' => 'kasir1@sercomp.test', 'role' => 'cashier'],
            ['name' => 'Rina Kasir', 'email' => 'kasir2@sercomp.test', 'role' => 'cashier'],
            ['name' => 'Budi Teknisi', 'email' => 'teknisi1@sercomp.test', 'role' => 'technician'],
            ['name' => 'Slamet Teknisi', 'email' => 'teknisi2@sercomp.test', 'role' => 'technician'],
            ['name' => 'Dewi Teknisi', 'email' => 'teknisi3@sercomp.test', 'role' => 'technician'],
        ];

        foreach ($users as $user) {
            $model = User::query()->firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'phone' => $user['phone'] ?? null,
                ],
            );

            $model->syncRoles([$user['role']]);
        }
    }

    /**
     * Product and service categories.
     */
    private function seedCategories(): void
    {
        foreach ([
            ['name' => 'Handphone', 'type' => 'product', 'description' => 'Gadget dan perangkat genggam'],
            ['name' => 'Laptop & Komputer', 'type' => 'product', 'description' => 'Laptop, PC dan perangkat komputasi'],
            ['name' => 'Aksesoris', 'type' => 'product', 'description' => 'Aksesoris pendukung perangkat'],
            ['name' => 'Sparepart', 'type' => 'product', 'description' => 'Komponen pengganti internal'],
            ['name' => 'Layar & LCD', 'type' => 'product', 'description' => 'Layar, LCD dan touchscreen'],
            ['name' => 'Perbaikan Handphone', 'type' => 'service', 'description' => 'Jasa perbaikan handphone'],
            ['name' => 'Perbaikan Laptop', 'type' => 'service', 'description' => 'Jasa perbaikan laptop dan komputer'],
            ['name' => 'Software & Data', 'type' => 'service', 'description' => 'Jasa instalasi dan pemulihan data'],
            ['name' => 'Pembersihan & Perawatan', 'type' => 'service', 'description' => 'Jasa pembersihan dan perawatan berkala'],
        ] as $category) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                $category,
            );
        }
    }

    /**
     * Suppliers (distributor sparepart).
     */
    private function seedSuppliers(): void
    {
        foreach ([
            ['name' => 'PT Sinar Komponen', 'contact_person' => 'Hendra Wijaya', 'phone' => '0812-3456-7890', 'email' => 'cs@sinarkomponen.co.id'],
            ['name' => 'CV Jaya Elektronik', 'contact_person' => 'Siti Rahma', 'phone' => '0813-9988-7766', 'email' => 'penjualan@jayaelektronik.co.id'],
            ['name' => 'PT Global Gadget Parts', 'contact_person' => 'Rudi Hartono', 'phone' => '0821-5566-1122', 'email' => 'order@globalgadgetparts.co.id'],
            ['name' => 'CV Maju Bersama', 'contact_person' => 'Agus Salim', 'phone' => '0857-2233-4455', 'email' => 'info@majubersama.co.id'],
            ['name' => 'PT Nusantara Teknik', 'contact_person' => 'Maria Ulfa', 'phone' => '0811-7788-9900', 'email' => 'sales@nusantarateknik.co.id'],
        ] as $supplier) {
            Supplier::query()->updateOrCreate(['name' => $supplier['name']], $supplier);
        }
    }

    /**
     * Customers, only when the table is still empty.
     */
    private function seedCustomers(): void
    {
        if (Customer::query()->exists()) {
            return;
        }

        $names = [
            'Budi Santoso', 'Siti Nurhaliza', 'Agus Wijaya', 'Dewi Lestari', 'Rahmat Hidayat',
            'Fitri Handayani', 'Joko Susilo', 'Ratna Sari', 'Deni Kurniawan', 'Lina Marlina',
            'Bayu Pratama', 'Nur Aini', 'Fajar Ramadhan', 'Rina Wati', 'Eko Prasetyo',
            'Putri Ayu', 'Andi Firmansyah', 'Sri Wahyuni', 'Dimas Anggara', 'Tuti Herawati',
        ];

        foreach ($names as $name) {
            Customer::query()->create([
                'name' => $name,
                'phone' => '08'.fake()->numerify('##-####-####'),
                'email' => Str::slug($name).'@example.com',
                'address' => fake()->address(),
                'notes' => fake()->optional(30)->sentence(),
            ]);
        }
    }

    /**
     * Realistic sparepart & accessory catalog with mixed stock levels.
     */
    private function seedProducts(): void
    {
        $suppliers = Supplier::query()->get();

        $items = [
            ['name' => 'LCD iPhone 12', 'category' => 'Layar & LCD', 'purchase' => 700_000, 'selling' => 850_000, 'stock' => 12, 'min' => 3],
            ['name' => 'LCD Samsung Galaxy A52', 'category' => 'Layar & LCD', 'purchase' => 450_000, 'selling' => 580_000, 'stock' => 8, 'min' => 3],
            ['name' => 'Touchscreen Xiaomi Redmi 9', 'category' => 'Layar & LCD', 'purchase' => 350_000, 'selling' => 450_000, 'stock' => 5, 'min' => 3],
            ['name' => 'LCD Laptop 14 inch', 'category' => 'Laptop & Komputer', 'purchase' => 650_000, 'selling' => 850_000, 'stock' => 3, 'min' => 2],
            ['name' => 'LCD Laptop 15.6 inch', 'category' => 'Laptop & Komputer', 'purchase' => 750_000, 'selling' => 950_000, 'stock' => 2, 'min' => 2],
            ['name' => 'Baterai iPhone 11', 'category' => 'Sparepart', 'purchase' => 150_000, 'selling' => 220_000, 'stock' => 10, 'min' => 4],
            ['name' => 'Baterai Samsung A32', 'category' => 'Sparepart', 'purchase' => 120_000, 'selling' => 185_000, 'stock' => 0, 'min' => 4],
            ['name' => 'Baterai Laptop Universal', 'category' => 'Laptop & Komputer', 'purchase' => 250_000, 'selling' => 350_000, 'stock' => 4, 'min' => 3],
            ['name' => 'Konektor Charging Type-C', 'category' => 'Sparepart', 'purchase' => 30_000, 'selling' => 55_000, 'stock' => 40, 'min' => 10],
            ['name' => 'Konektor Charging Micro USB', 'category' => 'Sparepart', 'purchase' => 20_000, 'selling' => 40_000, 'stock' => 35, 'min' => 10],
            ['name' => 'Flexible Flat Cable iPhone 12', 'category' => 'Sparepart', 'purchase' => 85_000, 'selling' => 135_000, 'stock' => 14, 'min' => 5],
            ['name' => 'Lensa Kamera iPhone 11', 'category' => 'Sparepart', 'purchase' => 120_000, 'selling' => 180_000, 'stock' => 9, 'min' => 4],
            ['name' => 'Speaker Handphone', 'category' => 'Sparepart', 'purchase' => 40_000, 'selling' => 75_000, 'stock' => 18, 'min' => 6],
            ['name' => 'Power IC Charging', 'category' => 'Sparepart', 'purchase' => 60_000, 'selling' => 100_000, 'stock' => 6, 'min' => 3],
            ['name' => 'Bohlam Flash LED', 'category' => 'Sparepart', 'purchase' => 8_000, 'selling' => 20_000, 'stock' => 30, 'min' => 10],
            ['name' => 'IC Baterai Protection', 'category' => 'Sparepart', 'purchase' => 25_000, 'selling' => 50_000, 'stock' => 12, 'min' => 5],
            ['name' => 'Kabel Data Type-C', 'category' => 'Aksesoris', 'purchase' => 15_000, 'selling' => 35_000, 'stock' => 60, 'min' => 15],
            ['name' => 'Kabel Data Lightning', 'category' => 'Aksesoris', 'purchase' => 20_000, 'selling' => 45_000, 'stock' => 45, 'min' => 15],
            ['name' => 'Charger 20W Type-C', 'category' => 'Aksesoris', 'purchase' => 60_000, 'selling' => 95_000, 'stock' => 22, 'min' => 8],
            ['name' => 'Charger 30W USB-C', 'category' => 'Aksesoris', 'purchase' => 90_000, 'selling' => 145_000, 'stock' => 0, 'min' => 8],
            ['name' => 'Headset Bluetooth', 'category' => 'Aksesoris', 'purchase' => 90_000, 'selling' => 145_000, 'stock' => 16, 'min' => 6],
            ['name' => 'Powerbank 10000mAh', 'category' => 'Aksesoris', 'purchase' => 110_000, 'selling' => 170_000, 'stock' => 12, 'min' => 5],
            ['name' => 'Tempered Glass Universal', 'category' => 'Aksesoris', 'purchase' => 5_000, 'selling' => 20_000, 'stock' => 80, 'min' => 20],
            ['name' => 'SSD 256GB SATA', 'category' => 'Laptop & Komputer', 'purchase' => 450_000, 'selling' => 550_000, 'stock' => 7, 'min' => 3],
            ['name' => 'RAM DDR4 8GB', 'category' => 'Laptop & Komputer', 'purchase' => 300_000, 'selling' => 380_000, 'stock' => 9, 'min' => 4],
            ['name' => 'Adapter Laptop 65W', 'category' => 'Laptop & Komputer', 'purchase' => 180_000, 'selling' => 265_000, 'stock' => 6, 'min' => 3],
            ['name' => 'Kipas / Fan Laptop', 'category' => 'Laptop & Komputer', 'purchase' => 90_000, 'selling' => 150_000, 'stock' => 5, 'min' => 3],
            ['name' => 'Pasta Termal 30g', 'category' => 'Laptop & Komputer', 'purchase' => 30_000, 'selling' => 55_000, 'stock' => 15, 'min' => 5],
            ['name' => 'Tempat SIM & SD Card', 'category' => 'Sparepart', 'purchase' => 15_000, 'selling' => 30_000, 'stock' => 50, 'min' => 10],
            ['name' => 'Microphone Handphone', 'category' => 'Sparepart', 'purchase' => 15_000, 'selling' => 35_000, 'stock' => 20, 'min' => 8],
        ];

        foreach ($items as $index => $item) {
            $categoryId = Category::query()->where('slug', Str::slug($item['category']))->value('id');

            Product::query()->updateOrCreate(
                ['sku' => 'SKU-'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT)],
                [
                    'name' => $item['name'],
                    'category_id' => $categoryId,
                    'supplier_id' => fake()->boolean(70) ? $suppliers->random()->id : null,
                    'barcode' => fake()->unique()->numerify('899###########'),
                    'unit' => 'pcs',
                    'purchase_price' => $item['purchase'],
                    'selling_price' => $item['selling'],
                    'stock' => $item['stock'],
                    'min_stock' => $item['min'],
                ],
            );
        }
    }

    /**
     * Service catalog with realistic prices and durations.
     */
    private function seedServiceCatalog(): void
    {
        $services = [
            ['name' => 'Ganti LCD Handphone', 'category' => 'Perbaikan Handphone', 'price' => 150_000, 'minutes' => 120],
            ['name' => 'Ganti Touchscreen', 'category' => 'Perbaikan Handphone', 'price' => 120_000, 'minutes' => 90],
            ['name' => 'Ganti Baterai', 'category' => 'Perbaikan Handphone', 'price' => 50_000, 'minutes' => 30],
            ['name' => 'Servis Tidak Menyala', 'category' => 'Perbaikan Handphone', 'price' => 200_000, 'minutes' => 240],
            ['name' => 'Servis Konektor Charging', 'category' => 'Perbaikan Handphone', 'price' => 80_000, 'minutes' => 60],
            ['name' => 'Ganti Lensa Kamera', 'category' => 'Perbaikan Handphone', 'price' => 90_000, 'minutes' => 45],
            ['name' => 'Ganti LCD Laptop', 'category' => 'Perbaikan Laptop', 'price' => 250_000, 'minutes' => 180],
            ['name' => 'Upgrade RAM / SSD', 'category' => 'Perbaikan Laptop', 'price' => 100_000, 'minutes' => 45],
            ['name' => 'Servis Keyboard Laptop', 'category' => 'Perbaikan Laptop', 'price' => 150_000, 'minutes' => 120],
            ['name' => 'Ganti Kipas Laptop', 'category' => 'Perbaikan Laptop', 'price' => 120_000, 'minutes' => 90],
            ['name' => 'Install Ulang / Flash ROM', 'category' => 'Software & Data', 'price' => 75_000, 'minutes' => 90],
            ['name' => 'Backup & Migrasi Data', 'category' => 'Software & Data', 'price' => 50_000, 'minutes' => 60],
            ['name' => 'Pembersihan & Perawatan', 'category' => 'Pembersihan & Perawatan', 'price' => 60_000, 'minutes' => 60],
        ];

        foreach ($services as $service) {
            $categoryId = Category::query()->where('slug', Str::slug($service['category']))->value('id');

            ServiceCatalog::query()->updateOrCreate(
                ['name' => $service['name'], 'category_id' => $categoryId],
                [
                    'price' => $service['price'],
                    'estimated_minutes' => $service['minutes'],
                    'description' => fake()->optional(40)->sentence(),
                ],
            );
        }
    }

    /**
     * Service orders across every status with spareparts, warranty and sales.
     */
    private function seedServiceOrders(): void
    {
        if (ServiceOrder::query()->exists()) {
            return;
        }

        $customers = Customer::query()->get(['id', 'name']);
        $receivers = User::query()->role(['admin', 'owner', 'cashier'])->get(['id']);
        $technicians = User::query()->role('technician')->get(['id']);

        $devices = ['iPhone 12', 'Samsung Galaxy A52', 'Xiaomi Redmi Note 10', 'Oppo Reno 7', 'Asus ROG Phone 5', 'Laptop Asus VivoBook', 'Laptop Lenovo IdeaPad', 'Laptop HP Pavilion', 'Monitor Samsung 24 inch', 'Printer Epson L3110'];

        $complaints = ['Tidak bisa menyala', 'Layar pecah', 'Baterai cepat habis', 'Tidak mau di-charge', 'Air masuk ke dalam unit', 'Touchscreen tidak merespons', 'Kamera tidak fokus', 'Sering restart sendiri', 'Speaker tidak berbunyi', 'Lambat dan sering hang'];

        $statuses = collect([
            ...array_fill(0, 3, 'pending'),
            ...array_fill(0, 4, 'diagnosed'),
            ...array_fill(0, 5, 'in_progress'),
            ...array_fill(0, 6, 'done'),
            ...array_fill(0, 7, 'taken'),
            ...array_fill(0, 3, 'cancelled'),
        ])->shuffle();

        foreach ($statuses as $status) {
            $createdAt = now()->subDays(fake()->numberBetween(0, 30))->subMinutes(fake()->numberBetween(0, 720));

            $order = ServiceOrder::query()->create([
                'invoice_number' => InvoiceNumber::next('SRV'),
                'customer_id' => fake()->boolean(90) ? $customers->random()->id : null,
                'user_id' => $receivers->random()->id,
                'technician_id' => in_array($status, ['pending', 'diagnosed']) && fake()->boolean(40) ? null : $technicians->random()->id,
                'item_name' => $devices[array_rand($devices)],
                'serial_number' => fake()->boolean(80) ? 'SN-'.strtoupper(Str::random(4)).'-'.fake()->numberBetween(1000, 9999) : null,
                'complaint' => $complaints[array_rand($complaints)],
                'physical_condition' => fake()->optional(50)->sentence(),
                'status' => 'pending',
                'estimated_cost' => fake()->numberBetween(50, 500) * 1000,
                'labor_cost' => 0,
                'total_cost' => 0,
                'notes' => fake()->optional(40)->sentence(),
                'warranty_days' => null,
                'warranty_end_date' => null,
                'estimated_completion_at' => in_array($status, ['pending', 'diagnosed', 'in_progress'])
                    ? $createdAt->addDays(fake()->numberBetween(1, 5))
                    : null,
            ]);

            $order->forceFill(['created_at' => $createdAt])->save();

            $this->transitionStatuses($order, $status);

            if (in_array($status, ['done', 'taken'])) {
                $order->update(['labor_cost' => fake()->numberBetween(50, 300) * 1000]);

                if (fake()->boolean(70)) {
                    $order->update(['warranty_days' => fake()->randomElement([30, 60, 90, 180])]);
                }

                $order->forceFill(['completed_at' => $order->created_at->addHours(fake()->numberBetween(6, 72))])->save();
                $order->recomputeWarrantyEndDate();
            }

            if ($status !== 'cancelled') {
                $this->attachParts($order);
            }

            $order->recalculateTotal();
            $this->backdateHistory($order);
        }

        foreach (ServiceOrder::query()->where('status', 'taken')->get() as $order) {
            $this->createServiceSale($order, 'paid');
        }

        foreach (ServiceOrder::query()->where('status', 'done')->get()->shuffle()->take(2) as $order) {
            $this->createServiceSale($order, fake()->randomElement(['partial', 'unpaid']));
        }
    }

    /**
     * Move an order through the status flow, recording each step in history.
     */
    private function transitionStatuses(ServiceOrder $order, string $final): void
    {
        $flow = ['pending', 'diagnosed', 'in_progress', 'done', 'taken'];
        $index = array_search($final, $flow);

        if ($index === false) {
            if (fake()->boolean(50)) {
                $order->update(['status' => 'diagnosed']);
            }

            $order->update(['status' => 'cancelled']);

            return;
        }

        foreach (array_slice($flow, 1, $index) as $status) {
            $order->update(['status' => $status]);
        }
    }

    /**
     * Attach random spareparts and decrement stock accordingly.
     */
    private function attachParts(ServiceOrder $order): void
    {
        $candidates = Product::query()->where('stock', '>', 3)->get();

        if ($candidates->isEmpty()) {
            return;
        }

        $rows = [];

        foreach ($candidates->shuffle()->take(fake()->numberBetween(0, 2)) as $product) {
            $quantity = fake()->numberBetween(1, 2);

            if ($product->stock < $quantity) {
                $quantity = $product->stock;
            }

            if ($quantity < 1) {
                continue;
            }

            $product->decrement('stock', $quantity);

            $rows[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $quantity,
                'price' => $product->selling_price,
                'subtotal' => (float) $product->selling_price * $quantity,
            ];
        }

        if ($rows !== []) {
            $order->items()->createMany($rows);
        }
    }

    /**
     * Spread the recorded status history across the order's lifetime.
     */
    private function backdateHistory(ServiceOrder $order): void
    {
        $histories = $order->statusHistories()->orderBy('id')->get();

        if ($histories->isEmpty()) {
            return;
        }

        $span = max($order->created_at->diffInMinutes(now()), $histories->count() * 10);
        $step = intdiv((int) $span, $histories->count());

        foreach ($histories as $index => $history) {
            $history->forceFill(['created_at' => $order->created_at->copy()->addMinutes($step * $index)])->save();
        }
    }

    /**
     * Create a service sale (nota) for a completed order.
     */
    private function createServiceSale(ServiceOrder $order, string $paymentStatus): void
    {
        $order->load('items');

        $subtotal = (float) $order->total_cost;
        $discount = fake()->boolean(30) ? fake()->numberBetween(0, 25_000) : 0;
        $total = max($subtotal - $discount, 0);
        $paid = match ($paymentStatus) {
            'paid' => $total,
            'partial' => round($total * fake()->numberBetween(30, 70) / 100, 2),
            default => 0.0,
        };

        $sale = Sale::query()->create([
            'invoice_number' => InvoiceNumber::next('INV'),
            'type' => 'service',
            'service_order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'user_id' => User::query()->role('cashier')->inRandomOrder()->value('id'),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'payment_method' => fake()->randomElement(Sale::PAYMENT_METHODS),
            'payment_status' => $paymentStatus,
            'paid_amount' => $paid,
            'notes' => fake()->optional(30)->sentence(),
        ]);

        $sale->forceFill(['created_at' => $order->created_at->addHours(fake()->numberBetween(24, 120))])->save();

        $rows = [];

        if ((float) $order->labor_cost > 0) {
            $rows[] = [
                'product_id' => null,
                'name' => 'Jasa servis: '.$order->item_name,
                'quantity' => 1,
                'price' => $order->labor_cost,
                'subtotal' => $order->labor_cost,
            ];
        }

        foreach ($order->items as $item) {
            $rows[] = [
                'product_id' => $item->product_id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ];
        }

        $sale->items()->createMany($rows);
    }

    /**
     * Retail sales spread over the last 30 days with consistent stock.
     */
    private function seedRetailSales(): void
    {
        if (Sale::query()->exists()) {
            return;
        }

        $cashiers = User::query()->role('cashier')->get(['id']);
        $customers = Customer::query()->get(['id']);

        foreach (range(1, 20) as $ignored) {
            $createdAt = now()->subDays(fake()->numberBetween(0, 30))->setTime(fake()->numberBetween(9, 20), fake()->numberBetween(0, 59), 0);

            $candidates = Product::query()->where('stock', '>', 0)->get();
            $picks = $candidates->shuffle()->take(fake()->numberBetween(1, 3));

            $rows = [];
            $subtotal = 0.0;

            foreach ($picks as $product) {
                $quantity = fake()->numberBetween(1, 3);

                if ($product->stock < $quantity) {
                    $quantity = $product->stock;
                }

                if ($quantity < 1) {
                    continue;
                }

                $product->decrement('stock', $quantity);
                $subtotal += (float) $product->selling_price * $quantity;

                $rows[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $product->selling_price,
                    'subtotal' => (float) $product->selling_price * $quantity,
                ];
            }

            if ($rows === []) {
                continue;
            }

            $discount = fake()->boolean(30) ? fake()->numberBetween(0, 25_000) : 0;
            $total = max($subtotal - $discount, 0);
            $paymentStatus = fake()->randomElement(['paid', 'paid', 'paid', 'partial', 'unpaid']);
            $paid = match ($paymentStatus) {
                'paid' => $total,
                'partial' => round($total * fake()->numberBetween(30, 70) / 100, 2),
                default => 0.0,
            };

            $sale = Sale::query()->create([
                'invoice_number' => InvoiceNumber::next('INV'),
                'type' => 'retail',
                'customer_id' => fake()->boolean(70) ? $customers->random()->id : null,
                'user_id' => $cashiers->random()->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => fake()->randomElement(Sale::PAYMENT_METHODS),
                'payment_status' => $paymentStatus,
                'paid_amount' => $paid,
                'notes' => fake()->optional(30)->sentence(),
            ]);

            $sale->forceFill(['created_at' => $createdAt])->save();
            $sale->items()->createMany($rows);
        }
    }

    /**
     * Purchases from suppliers over the last 45 days. Stock goes up, and some
     * invoices carry payment terms (tempo) with a partial or zero payment.
     */
    private function seedPurchases(): void
    {
        if (Purchase::query()->exists()) {
            return;
        }

        $suppliers = Supplier::query()->get(['id']);
        $users = User::query()->role(['admin', 'owner'])->get(['id']);

        foreach (range(1, 10) as $ignored) {
            $purchaseDate = now()->subDays(fake()->numberBetween(3, 45))->setTime(9, 0);
            $picks = Product::query()->inRandomOrder()->take(fake()->numberBetween(1, 3))->get();

            $rows = [];
            $subtotal = 0.0;

            foreach ($picks as $product) {
                $quantity = fake()->numberBetween(2, 10);
                $price = (float) $product->purchase_price;

                $product->increment('stock', $quantity);
                $subtotal += $price * $quantity;

                $rows[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'purchase_price' => $price,
                    'subtotal' => $price * $quantity,
                ];
            }

            $discount = fake()->boolean(25) ? fake()->numberBetween(0, 50_000) : 0;
            $total = max($subtotal - $discount, 0);
            $paymentStatus = fake()->randomElement(['paid', 'paid', 'partial', 'unpaid']);
            $paid = match ($paymentStatus) {
                'paid' => $total,
                'partial' => round($total * fake()->numberBetween(30, 70) / 100, 2),
                default => 0.0,
            };

            $purchase = Purchase::query()->create([
                'purchase_number' => InvoiceNumber::next('PO'),
                'supplier_id' => $suppliers->random()->id,
                'user_id' => $users->random()->id,
                'purchase_date' => $purchaseDate,
                'due_date' => fake()->boolean(70) ? $purchaseDate->copy()->addDays(fake()->randomElement([7, 14, 30])) : null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'payment_status' => $paymentStatus,
                'notes' => fake()->optional(30)->sentence(),
            ]);

            $purchase->forceFill(['created_at' => $purchaseDate->copy()->addHours(8)])->save();
            $purchase->items()->createMany($rows);

            if ($paid > 0) {
                $purchase->payments()->create([
                    'amount' => $paid,
                    'payment_method' => fake()->randomElement(['cash', 'transfer', 'qris']),
                    'paid_at' => $purchaseDate->copy()->addHours(fake()->numberBetween(0, 96)),
                ]);
            }
        }
    }

    /**
     * A few of the seeded purchases have a return (ganti / refund / potong
     * hutang). Stock for the returned products is removed again, and a
     * potong-hutang return reduces the purchase total like the controller does.
     */
    private function seedPurchaseReturns(): void
    {
        if (PurchaseReturn::query()->exists()) {
            return;
        }

        $users = User::query()->role(['admin', 'owner'])->get(['id']);
        $purchases = Purchase::query()->with('items')->get();

        foreach ($purchases as $purchase) {
            if ($purchase->items->isEmpty() || ! fake()->boolean(35)) {
                continue;
            }

            $item = $purchase->items->random();
            $quantity = fake()->numberBetween(1, $item->quantity);
            $returnDate = now()->subDays(fake()->numberBetween(0, 5));
            $resultType = fake()->randomElement(['replacement', 'refund', 'debt_deduction']);

            if ($item->product_id !== null) {
                Product::query()->whereKey($item->product_id)->decrement('stock', $quantity);
            }

            $total = (float) $item->purchase_price * $quantity;

            $purchaseReturn = PurchaseReturn::query()->create([
                'return_number' => InvoiceNumber::next('RTN'),
                'purchase_id' => $purchase->id,
                'user_id' => $users->random()->id,
                'return_date' => $returnDate,
                'result_type' => $resultType,
                'reason' => fake()->optional(80)->sentence(4),
                'total' => $total,
                'notes' => fake()->optional(30)->sentence(),
            ]);

            $purchaseReturn->items()->create([
                'purchase_item_id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'quantity' => $quantity,
                'price' => $item->purchase_price,
                'subtotal' => $total,
            ]);

            if ($resultType === 'debt_deduction') {
                $newTotal = max((float) $purchase->total - $total, 0);
                $paid = min((float) $purchase->paid_amount, $newTotal);
                $purchase->update([
                    'total' => $newTotal,
                    'paid_amount' => $paid,
                    'payment_status' => $paid >= $newTotal ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                ]);
            }
        }
    }
}
