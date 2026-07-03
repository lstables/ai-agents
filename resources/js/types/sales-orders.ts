import type { Customer } from './customers';

export type SalesOrderStatus = 'draft' | 'pending' | 'confirmed' | 'fulfilled' | 'cancelled';

export const SALES_ORDER_STATUSES: SalesOrderStatus[] = ['draft', 'pending', 'confirmed', 'fulfilled', 'cancelled'];

export type LinkedInventoryItem = {
    id: number;
    sku: string;
    name: string;
};

export type SalesOrderItem = {
    id: number;
    description: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    inventory_item: LinkedInventoryItem | null;
};

export type SalesOrder = {
    id: number;
    reference: string;
    status: SalesOrderStatus;
    order_date: string;
    expected_date: string | null;
    notes: string | null;
    total_amount: number;
    amount_paid: number;
    balance_due: number;
    customer: Customer | null;
    items: SalesOrderItem[];
    created_at: string | null;
};

export type SalesOrderFilters = {
    status: SalesOrderStatus | '';
    customerId: number | '';
    search: string;
    page: number;
    perPage: number;
};

export type NewSalesOrderItemInput = {
    inventory_item_id: number | '';
    description: string;
    quantity: string;
    unit_price: string;
};

export type NewSalesOrderInput = {
    customer_id: number | '';
    order_date: string;
    expected_date: string;
    notes: string;
    items: NewSalesOrderItemInput[];
};
