export type PurchaseStatus = 'draft' | 'pending' | 'approved' | 'received' | 'cancelled';

export const PURCHASE_STATUSES: PurchaseStatus[] = ['draft', 'pending', 'approved', 'received', 'cancelled'];

export type Supplier = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
};

export type PurchaseItem = {
    id: number;
    description: string;
    quantity: number;
    unit_price: number;
    line_total: number;
};

export type Purchase = {
    id: number;
    reference: string;
    status: PurchaseStatus;
    allowed_next_statuses: PurchaseStatus[];
    order_date: string;
    expected_date: string | null;
    notes: string | null;
    total_amount: number;
    amount_paid: number;
    balance_due: number;
    supplier: Supplier | null;
    items: PurchaseItem[];
    created_at: string | null;
};

export type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

export type PaginatedResponse<T> = {
    data: T[];
    meta: PaginationMeta;
};

export type PurchaseFilters = {
    status: PurchaseStatus | '';
    supplierId: number | '';
    search: string;
    page: number;
    perPage: number;
};

export type NewPurchaseItemInput = {
    description: string;
    quantity: string;
    unit_price: string;
};

export type NewPurchaseInput = {
    supplier_id: number | '';
    order_date: string;
    expected_date: string;
    notes: string;
    items: NewPurchaseItemInput[];
};

export type ValidationErrors = Record<string, string[]>;
