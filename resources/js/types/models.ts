export type PaginatedData<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    first_page_url: string | null;
    last_page_url: string | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};

export type Category = {
    id: number;
    name: string;
    slug: string;
    type: 'product' | 'service';
    description: string | null;
    products_count?: number;
    service_catalogs_count?: number;
    created_at: string;
};

export type Product = {
    id: number;
    category_id: number | null;
    supplier_id: number | null;
    name: string;
    sku: string | null;
    barcode: string | null;
    unit: string;
    purchase_price: string | number;
    selling_price: string | number;
    stock: number;
    min_stock: number;
    description: string | null;
    category?: { id: number; name: string } | null;
    supplier?: { id: number; name: string } | null;
    created_at: string;
};

export type ServiceCatalog = {
    id: number;
    category_id: number | null;
    name: string;
    description: string | null;
    price: string | number;
    estimated_minutes: number | null;
    category?: { id: number; name: string } | null;
    created_at: string;
};

export type Customer = {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    address: string | null;
    notes: string | null;
    service_orders_count?: number;
    sales_count?: number;
    created_at: string;
};

export type Supplier = {
    id: number;
    name: string;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    notes: string | null;
    products_count?: number;
    created_at: string;
};

export type Role = {
    id: number;
    name: string;
    label?: string | null;
};

export type UserWithRole = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    roles: Role[];
    created_at: string;
};

export type ServiceOrderStatus =
    'pending' | 'diagnosed' | 'in_progress' | 'done' | 'taken' | 'cancelled';

export type ServiceOrderItem = {
    id: number;
    service_order_id: number;
    product_id: number | null;
    name: string;
    quantity: number;
    price: string | number;
    subtotal: string | number;
    product?: { id: number; name: string; unit: string } | null;
};

export type ServiceOrder = {
    id: number;
    invoice_number: string;
    customer_id: number | null;
    user_id: number;
    technician_id: number | null;
    item_name: string;
    serial_number: string | null;
    complaint: string;
    physical_condition: string | null;
    status: ServiceOrderStatus;
    estimated_cost: string | number;
    labor_cost: string | number;
    total_cost: string | number;
    notes: string | null;
    warranty_days: number | null;
    warranty_end_date: string | null;
    warranty_notes: string | null;
    completed_at: string | null;
    estimated_completion_at: string | null;
    customer?: Customer | null;
    receiver?: { id: number; name: string } | null;
    technician?: { id: number; name: string } | null;
    items?: ServiceOrderItem[];
    sale?: Sale | null;
    created_at: string;
};

export type WarrantyStatus = 'none' | 'pending' | 'active' | 'expired';

export type ServiceOrderStatusHistory = {
    id: number;
    service_order_id: number;
    status: ServiceOrderStatus;
    user_id: number | null;
    created_at: string;
};

export type PaymentMethod = 'cash' | 'transfer' | 'qris';

export type PaymentStatus = 'paid' | 'partial' | 'unpaid';

export type PurchaseItem = {
    id: number;
    purchase_id: number;
    product_id: number | null;
    name: string;
    quantity: number;
    purchase_price: string | number;
    subtotal: string | number;
    product?: { id: number; name: string; unit: string } | null;
};

export type PurchasePayment = {
    id: number;
    purchase_id: number;
    amount: string | number;
    payment_method: PaymentMethod;
    paid_at: string;
    notes: string | null;
    created_at: string;
};

export type Purchase = {
    id: number;
    purchase_number: string;
    supplier_id: number | null;
    user_id: number;
    purchase_date: string;
    due_date: string | null;
    subtotal: string | number;
    discount: string | number;
    total: string | number;
    paid_amount: string | number;
    payment_status: PaymentStatus;
    notes: string | null;
    supplier?: Supplier | null;
    receiver?: { id: number; name: string } | null;
    items?: PurchaseItem[];
    payments?: PurchasePayment[];
    created_at: string;
};

export type PurchaseReturnResultType =
    'replacement' | 'refund' | 'debt_deduction';

export type PurchaseReturnItem = {
    id: number;
    purchase_return_id: number;
    purchase_item_id: number | null;
    product_id: number | null;
    name: string;
    quantity: number;
    price: string | number;
    subtotal: string | number;
    product?: { id: number; name: string; unit: string } | null;
};

export type PurchaseReturn = {
    id: number;
    return_number: string;
    purchase_id: number;
    user_id: number;
    return_date: string;
    result_type: PurchaseReturnResultType;
    reason: string | null;
    total: string | number;
    notes: string | null;
    purchase?: Purchase | null;
    creator?: { id: number; name: string } | null;
    items?: PurchaseReturnItem[];
    created_at: string;
};

export type SaleItem = {
    id: number;
    sale_id: number;
    product_id: number | null;
    name: string;
    quantity: number;
    price: string | number;
    subtotal: string | number;
};

export type Sale = {
    id: number;
    invoice_number: string;
    type: 'retail' | 'service';
    service_order_id: number | null;
    customer_id: number | null;
    user_id: number;
    subtotal: string | number;
    discount: string | number;
    total: string | number;
    payment_method: PaymentMethod;
    payment_status: PaymentStatus;
    paid_amount: string | number;
    notes: string | null;
    customer?: Customer | null;
    cashier?: { id: number; name: string } | null;
    serviceOrder?: {
        id: number;
        invoice_number: string;
        item_name: string;
    } | null;
    items?: SaleItem[];
    created_at: string;
};

export type AppNotification = {
    id: string;
    title: string;
    message: string;
    action_url: string | null;
    read_at: string | null;
    created_at: string;
};

export type Flash = {
    toast?: {
        type: 'success' | 'info' | 'warning' | 'error';
        message: string;
    };
};

export type AuditLog = {
    id: number;
    user_id: number | null;
    action: string;
    auditable_type: string | null;
    auditable_id: number | null;
    description: string;
    old_values: Record<string, string | number | null> | null;
    new_values: Record<string, string | number | null> | null;
    ip_address: string | null;
    user?: { id: number; name: string } | null;
    created_at: string;
};
