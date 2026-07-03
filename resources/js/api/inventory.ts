import { apiRequest } from './client';
import type { PaginatedResponse } from '../types/purchases';
import type { InventoryItem, InventoryItemFilters, NewInventoryItemInput } from '../types/inventory';

/**
 * Inventory rarely numbers in the hundreds for this application, so a large
 * fixed page size stands in for an unpaginated list where one is needed
 * (e.g. populating a select). See the InventoryItemController pagination cap.
 */
const DROPDOWN_PAGE_SIZE = 100;

export function fetchAllInventoryItems(): Promise<{ data: InventoryItem[] }> {
    return apiRequest<{ data: InventoryItem[] }>(`/api/inventory-items?per_page=${DROPDOWN_PAGE_SIZE}`);
}

export function fetchInventoryItems(filters: InventoryItemFilters): Promise<PaginatedResponse<InventoryItem>> {
    const params = new URLSearchParams();
    params.set('page', String(filters.page));
    params.set('per_page', String(filters.perPage));

    if (filters.belowReorderLevel) {
        params.set('below_reorder_level', '1');
    }

    if (filters.search.trim()) {
        params.set('search', filters.search.trim());
    }

    return apiRequest<PaginatedResponse<InventoryItem>>(`/api/inventory-items?${params.toString()}`);
}

function payload(input: NewInventoryItemInput) {
    return {
        sku: input.sku,
        name: input.name,
        description: input.description || null,
        quantity_on_hand: input.quantity_on_hand,
        reorder_level: input.reorder_level || null,
        unit: input.unit || null,
        supplier_id: input.supplier_id || null,
    };
}

export function createInventoryItem(input: NewInventoryItemInput): Promise<{ data: InventoryItem }> {
    return apiRequest<{ data: InventoryItem }>('/api/inventory-items', {
        method: 'POST',
        body: JSON.stringify(payload(input)),
    });
}

export function updateInventoryItem(id: number, input: NewInventoryItemInput): Promise<{ data: InventoryItem }> {
    return apiRequest<{ data: InventoryItem }>(`/api/inventory-items/${id}`, {
        method: 'PUT',
        body: JSON.stringify(payload(input)),
    });
}

export function deleteInventoryItem(id: number): Promise<void> {
    return apiRequest<void>(`/api/inventory-items/${id}`, { method: 'DELETE' });
}
