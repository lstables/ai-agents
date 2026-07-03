import { apiRequest } from './client';
import type { PaginatedResponse } from '../types/purchases';
import type { NewPaymentInput, PayableOption, PayableType, Payment, PaymentFilters } from '../types/finance';

export function fetchPayments(filters: PaymentFilters): Promise<PaginatedResponse<Payment>> {
    const params = new URLSearchParams();
    params.set('page', String(filters.page));
    params.set('per_page', String(filters.perPage));

    if (filters.payableType) {
        params.set('payable_type', filters.payableType);
    }

    if (filters.search.trim()) {
        params.set('search', filters.search.trim());
    }

    return apiRequest<PaginatedResponse<Payment>>(`/api/payments?${params.toString()}`);
}

export function createPayment(input: NewPaymentInput): Promise<{ data: Payment }> {
    return apiRequest<{ data: Payment }>('/api/payments', {
        method: 'POST',
        body: JSON.stringify({
            payable_type: input.payable_type,
            payable_id: input.payable_id,
            amount: input.amount,
            payment_date: input.payment_date,
            method: input.method || null,
            reference: input.reference || null,
            notes: input.notes || null,
        }),
    });
}

type PayableSearchResult = {
    id: number;
    reference: string;
    balance_due: number;
};

/**
 * Search purchases or sales orders by reference, for the record-payment
 * form's order picker. Reuses the existing paginated index endpoints
 * rather than adding a bespoke lookup endpoint.
 */
export async function searchPayableOrders(type: PayableType, search: string): Promise<PayableOption[]> {
    const endpoint = type === 'purchase' ? '/api/purchases' : '/api/sales-orders';
    const params = new URLSearchParams({ per_page: '5' });

    if (search.trim()) {
        params.set('search', search.trim());
    }

    const response = await apiRequest<PaginatedResponse<PayableSearchResult>>(`${endpoint}?${params.toString()}`);

    return response.data.map((item) => ({
        id: item.id,
        reference: item.reference,
        balance_due: item.balance_due,
    }));
}
