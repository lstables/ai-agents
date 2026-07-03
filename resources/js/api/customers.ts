import { apiRequest } from './client';
import type { PaginatedResponse } from '../types/purchases';
import type { Customer, CustomerFilters, NewCustomerInput } from '../types/customers';

export function fetchCustomers(filters: CustomerFilters): Promise<PaginatedResponse<Customer>> {
    const params = new URLSearchParams();
    params.set('page', String(filters.page));
    params.set('per_page', String(filters.perPage));

    if (filters.search.trim()) {
        params.set('search', filters.search.trim());
    }

    return apiRequest<PaginatedResponse<Customer>>(`/api/customers?${params.toString()}`);
}

export function createCustomer(input: NewCustomerInput): Promise<{ data: Customer }> {
    return apiRequest<{ data: Customer }>('/api/customers', {
        method: 'POST',
        body: JSON.stringify({
            name: input.name,
            email: input.email || null,
            phone: input.phone || null,
        }),
    });
}

export function updateCustomer(id: number, input: NewCustomerInput): Promise<{ data: Customer }> {
    return apiRequest<{ data: Customer }>(`/api/customers/${id}`, {
        method: 'PUT',
        body: JSON.stringify({
            name: input.name,
            email: input.email || null,
            phone: input.phone || null,
        }),
    });
}

export function deleteCustomer(id: number): Promise<void> {
    return apiRequest<void>(`/api/customers/${id}`, { method: 'DELETE' });
}
