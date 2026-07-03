export type PayableType = 'purchase' | 'sales_order';

export const PAYABLE_TYPES: PayableType[] = ['purchase', 'sales_order'];

export const PAYABLE_TYPE_LABELS: Record<PayableType, string> = {
    purchase: 'Purchase',
    sales_order: 'Sales order',
};

export type PayableSummary = {
    type: PayableType;
    id: number;
    reference: string;
};

export type Payment = {
    id: number;
    amount: number;
    payment_date: string;
    method: string | null;
    reference: string | null;
    notes: string | null;
    payable: PayableSummary | null;
    created_at: string | null;
};

export type PaymentFilters = {
    payableType: PayableType | '';
    search: string;
    page: number;
    perPage: number;
};

export type PayableOption = {
    id: number;
    reference: string;
    balance_due: number;
};

export type NewPaymentInput = {
    payable_type: PayableType | '';
    payable_id: number | '';
    amount: string;
    payment_date: string;
    method: string;
    reference: string;
    notes: string;
};
