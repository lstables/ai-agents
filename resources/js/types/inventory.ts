import type { Supplier } from './purchases';

export type InventoryItem = {
    id: number;
    sku: string;
    name: string;
    description: string | null;
    quantity_on_hand: number;
    reorder_level: number | null;
    unit: string | null;
    is_below_reorder_level: boolean;
    supplier: Supplier | null;
};

export type InventoryItemFilters = {
    search: string;
    belowReorderLevel: boolean;
    page: number;
    perPage: number;
};

export type NewInventoryItemInput = {
    sku: string;
    name: string;
    description: string;
    quantity_on_hand: string;
    reorder_level: string;
    unit: string;
    supplier_id: number | '';
};
