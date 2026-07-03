export type Customer = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
};

export type CustomerFilters = {
    search: string;
    page: number;
    perPage: number;
};

export type NewCustomerInput = {
    name: string;
    email: string;
    phone: string;
};
