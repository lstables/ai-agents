export type ReportFilters = {
    from: string;
    to: string;
};

export type PurchasingSummary = {
    total_orders: number;
    total_spend: number;
    by_status: Record<string, number>;
};

export type SalesSummary = {
    total_orders: number;
    total_revenue: number;
    by_status: Record<string, number>;
};

export type LowStockItem = {
    id: number;
    sku: string;
    name: string;
    quantity_on_hand: number;
    reorder_level: number | null;
};

export type InventorySummary = {
    below_reorder_level_count: number;
    items: LowStockItem[];
};

export type TopSupplier = {
    supplier: { id: number; name: string };
    total_spend: number;
};

export type TopCustomer = {
    customer: { id: number; name: string };
    total_revenue: number;
};

export type ReportSummary = {
    purchasing: PurchasingSummary;
    sales: SalesSummary;
    inventory: InventorySummary;
    top_suppliers: TopSupplier[];
    top_customers: TopCustomer[];
};
