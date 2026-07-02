import type { ValidationErrors } from '../types/purchases';

export class ApiValidationError extends Error {
    errors: ValidationErrors;

    constructor(message: string, errors: ValidationErrors) {
        super(message);
        this.name = 'ApiValidationError';
        this.errors = errors;
    }
}

export class ApiError extends Error {
    status: number;

    constructor(message: string, status: number) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
    }
}

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

export async function apiRequest<T>(path: string, init: RequestInit = {}): Promise<T> {
    const response = await fetch(path, {
        ...init,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(init.headers ?? {}),
        },
    });

    if (response.status === 422) {
        const body = (await response.json()) as { message: string; errors: ValidationErrors };
        throw new ApiValidationError(body.message, body.errors);
    }

    if (!response.ok) {
        let message = `Request failed with status ${response.status}`;
        try {
            const body = (await response.json()) as { message?: string };
            message = body.message ?? message;
        } catch {
            // Response body was not JSON; keep the generic message.
        }
        throw new ApiError(message, response.status);
    }

    if (response.status === 204) {
        return undefined as T;
    }

    return (await response.json()) as T;
}
