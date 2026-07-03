<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { createPayment, searchPayableOrders } from '../../api/finance';
import { ApiValidationError } from '../../api/client';
import { PAYABLE_TYPES, PAYABLE_TYPE_LABELS } from '../../types/finance';
import type { NewPaymentInput, PayableOption, Payment } from '../../types/finance';
import type { ValidationErrors } from '../../types/purchases';

const emit = defineEmits<{ created: [payment: Payment]; cancel: [] }>();

const submitState = ref<'idle' | 'submitting' | 'error'>('idle');
const errors = ref<ValidationErrors>({});
const generalError = ref('');

const orderSearch = ref('');
const orderResults = ref<PayableOption[]>([]);
const orderSearchState = ref<'idle' | 'loading' | 'error'>('idle');
const selectedOrder = ref<PayableOption | null>(null);

function emptyForm(): NewPaymentInput {
    return {
        payable_type: '',
        payable_id: '',
        amount: '',
        payment_date: new Date().toISOString().slice(0, 10),
        method: '',
        reference: '',
        notes: '',
    };
}

const form = reactive<NewPaymentInput>(emptyForm());

let searchDebounce: ReturnType<typeof setTimeout> | undefined;

watch([() => form.payable_type, orderSearch], () => {
    selectedOrder.value = null;
    form.payable_id = '';
    orderResults.value = [];

    if (!form.payable_type) {
        return;
    }

    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(async () => {
        orderSearchState.value = 'loading';

        try {
            orderResults.value = await searchPayableOrders(form.payable_type as 'purchase' | 'sales_order', orderSearch.value);
            orderSearchState.value = 'idle';
        } catch {
            orderSearchState.value = 'error';
        }
    }, 300);
});

function selectOrder(order: PayableOption) {
    selectedOrder.value = order;
    form.payable_id = order.id;
    orderResults.value = [];
}

function changeOrder() {
    selectedOrder.value = null;
    form.payable_id = '';
    orderSearch.value = '';
}

function fieldError(field: string): string | null {
    return errors.value[field]?.[0] ?? null;
}

async function submit() {
    submitState.value = 'submitting';
    errors.value = {};
    generalError.value = '';

    try {
        const response = await createPayment(form);
        emit('created', response.data);
        Object.assign(form, emptyForm());
        selectedOrder.value = null;
        orderSearch.value = '';
        submitState.value = 'idle';
    } catch (error) {
        submitState.value = 'error';

        if (error instanceof ApiValidationError) {
            errors.value = error.errors;
        } else {
            generalError.value = error instanceof Error ? error.message : 'Something went wrong.';
        }
    }
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div v-if="generalError" class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
            {{ generalError }}
        </div>

        <div>
            <label class="block text-sm font-semibold text-zinc-800" for="payable_type">Order type</label>
            <select
                id="payable_type"
                v-model="form.payable_type"
                class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
            >
                <option value="" disabled>Select order type</option>
                <option v-for="type in PAYABLE_TYPES" :key="type" :value="type">
                    {{ PAYABLE_TYPE_LABELS[type] }}
                </option>
            </select>
            <p v-if="fieldError('payable_type')" class="mt-1 text-xs font-medium text-rose-700">
                {{ fieldError('payable_type') }}
            </p>
        </div>

        <div v-if="form.payable_type">
            <div v-if="selectedOrder" class="flex items-center justify-between rounded-md border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm">
                <span>
                    <span class="font-semibold text-zinc-900">{{ selectedOrder.reference }}</span>
                    — balance due: <span class="font-semibold">{{ selectedOrder.balance_due.toFixed(2) }}</span>
                </span>
                <button type="button" class="text-xs font-semibold text-cyan-700 hover:underline" @click="changeOrder">
                    Change
                </button>
            </div>
            <div v-else>
                <label class="block text-sm font-semibold text-zinc-800" for="order_search">Search by reference</label>
                <input
                    id="order_search"
                    v-model="orderSearch"
                    type="text"
                    placeholder="Type a reference…"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="orderSearchState === 'error'" class="mt-1 text-xs font-medium text-rose-700">
                    Could not search orders.
                </p>
                <ul v-if="orderResults.length > 0" class="mt-2 divide-y divide-zinc-100 rounded-md border border-zinc-200">
                    <li v-for="order in orderResults" :key="order.id">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-zinc-50"
                            @click="selectOrder(order)"
                        >
                            <span class="font-semibold text-zinc-900">{{ order.reference }}</span>
                            <span class="text-zinc-600">Balance due: {{ order.balance_due.toFixed(2) }}</span>
                        </button>
                    </li>
                </ul>
            </div>
            <p v-if="fieldError('payable_id')" class="mt-1 text-xs font-medium text-rose-700">
                {{ fieldError('payable_id') }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="amount">Amount</label>
                <input
                    id="amount"
                    v-model="form.amount"
                    type="number"
                    min="0"
                    step="0.01"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('amount')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('amount') }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="payment_date">Payment date</label>
                <input
                    id="payment_date"
                    v-model="form.payment_date"
                    type="date"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('payment_date')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('payment_date') }}
                </p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="method">Method</label>
                <input
                    id="method"
                    v-model="form.method"
                    type="text"
                    placeholder="bank_transfer"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('method')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('method') }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="reference">Reference</label>
                <input
                    id="reference"
                    v-model="form.reference"
                    type="text"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('reference')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('reference') }}
                </p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-zinc-800" for="notes">Notes</label>
            <textarea
                id="notes"
                v-model="form.notes"
                rows="2"
                class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
            />
            <p v-if="fieldError('notes')" class="mt-1 text-xs font-medium text-rose-700">
                {{ fieldError('notes') }}
            </p>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-4">
            <button
                type="button"
                class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50"
                @click="emit('cancel')"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800 disabled:opacity-50"
                :disabled="submitState === 'submitting' || !form.payable_id"
            >
                {{ submitState === 'submitting' ? 'Saving…' : 'Record payment' }}
            </button>
        </div>
    </form>
</template>
