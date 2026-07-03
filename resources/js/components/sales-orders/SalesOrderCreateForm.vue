<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { createSalesOrder } from '../../api/sales-orders';
import { fetchAllCustomers } from '../../api/customers';
import { fetchAllInventoryItems } from '../../api/inventory';
import { ApiValidationError } from '../../api/client';
import type { ValidationErrors } from '../../types/purchases';
import type { Customer } from '../../types/customers';
import type { InventoryItem } from '../../types/inventory';
import type { NewSalesOrderInput, SalesOrder } from '../../types/sales-orders';

const emit = defineEmits<{ created: [salesOrder: SalesOrder] }>();

const customers = ref<Customer[]>([]);
const customersState = ref<'loading' | 'ready' | 'error'>('loading');
const inventoryItems = ref<InventoryItem[]>([]);
const inventoryItemsState = ref<'loading' | 'ready' | 'error'>('loading');
const submitState = ref<'idle' | 'submitting' | 'error'>('idle');
const errors = ref<ValidationErrors>({});
const generalError = ref('');

function emptyItem() {
    return { inventory_item_id: '' as number | '', description: '', quantity: '1', unit_price: '0' };
}

const form = reactive<NewSalesOrderInput>({
    customer_id: '',
    order_date: new Date().toISOString().slice(0, 10),
    expected_date: '',
    notes: '',
    items: [emptyItem()],
});

const runningTotal = computed(() =>
    form.items.reduce((total, item) => {
        const quantity = Number(item.quantity) || 0;
        const unitPrice = Number(item.unit_price) || 0;

        return total + quantity * unitPrice;
    }, 0),
);

async function loadCustomers() {
    customersState.value = 'loading';

    try {
        const response = await fetchAllCustomers();
        customers.value = response.data;
        customersState.value = 'ready';
    } catch {
        customersState.value = 'error';
    }
}

async function loadInventoryItems() {
    inventoryItemsState.value = 'loading';

    try {
        const response = await fetchAllInventoryItems();
        inventoryItems.value = response.data;
        inventoryItemsState.value = 'ready';
    } catch {
        inventoryItemsState.value = 'error';
    }
}

onMounted(() => {
    loadCustomers();
    loadInventoryItems();
});

function addItem() {
    form.items.push(emptyItem());
}

function removeItem(index: number) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

function applyInventoryItem(index: number) {
    const item = form.items[index];
    const inventoryItem = inventoryItems.value.find((candidate) => candidate.id === item.inventory_item_id);

    if (inventoryItem && !item.description) {
        item.description = inventoryItem.name;
    }
}

function fieldError(field: string): string | null {
    return errors.value[field]?.[0] ?? null;
}

function resetForm() {
    form.customer_id = '';
    form.order_date = new Date().toISOString().slice(0, 10);
    form.expected_date = '';
    form.notes = '';
    form.items = [emptyItem()];
}

async function submit() {
    submitState.value = 'submitting';
    errors.value = {};
    generalError.value = '';

    try {
        const response = await createSalesOrder(form);
        emit('created', response.data);
        resetForm();
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
    <form class="space-y-5" @submit.prevent="submit">
        <div v-if="generalError" class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
            {{ generalError }}
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="customer_id">Customer</label>
                <select
                    id="customer_id"
                    v-model="form.customer_id"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                    :disabled="customersState === 'loading'"
                >
                    <option value="" disabled>Select a customer</option>
                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                        {{ customer.name }}
                    </option>
                </select>
                <p v-if="customersState === 'error'" class="mt-1 text-xs font-medium text-rose-700">
                    Could not load customers.
                </p>
                <p v-if="fieldError('customer_id')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('customer_id') }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-zinc-800" for="order_date">Order date</label>
                    <input
                        id="order_date"
                        v-model="form.order_date"
                        type="date"
                        class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                    >
                    <p v-if="fieldError('order_date')" class="mt-1 text-xs font-medium text-rose-700">
                        {{ fieldError('order_date') }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-zinc-800" for="expected_date">Expected date</label>
                    <input
                        id="expected_date"
                        v-model="form.expected_date"
                        type="date"
                        class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                    >
                    <p v-if="fieldError('expected_date')" class="mt-1 text-xs font-medium text-rose-700">
                        {{ fieldError('expected_date') }}
                    </p>
                </div>
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

        <div>
            <div class="flex items-center justify-between">
                <span class="block text-sm font-semibold text-zinc-800">Line items</span>
                <button
                    type="button"
                    class="rounded-md border border-zinc-300 px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-50"
                    @click="addItem"
                >
                    Add item
                </button>
            </div>

            <div class="mt-2 space-y-2">
                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="grid grid-cols-[160px_1fr_90px_110px_32px] items-start gap-2"
                >
                    <div>
                        <select
                            v-model="item.inventory_item_id"
                            class="w-full rounded-md border border-zinc-300 px-2 py-2 text-sm"
                            :disabled="inventoryItemsState === 'loading'"
                            @change="applyInventoryItem(index)"
                        >
                            <option value="">Free-text item</option>
                            <option v-for="inventoryItem in inventoryItems" :key="inventoryItem.id" :value="inventoryItem.id">
                                {{ inventoryItem.sku }}
                            </option>
                        </select>
                        <p v-if="fieldError(`items.${index}.inventory_item_id`)" class="mt-1 text-xs font-medium text-rose-700">
                            {{ fieldError(`items.${index}.inventory_item_id`) }}
                        </p>
                    </div>
                    <div>
                        <input
                            v-model="item.description"
                            type="text"
                            placeholder="Description"
                            class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                        >
                        <p v-if="fieldError(`items.${index}.description`)" class="mt-1 text-xs font-medium text-rose-700">
                            {{ fieldError(`items.${index}.description`) }}
                        </p>
                    </div>
                    <div>
                        <input
                            v-model="item.quantity"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Qty"
                            class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                        >
                        <p v-if="fieldError(`items.${index}.quantity`)" class="mt-1 text-xs font-medium text-rose-700">
                            {{ fieldError(`items.${index}.quantity`) }}
                        </p>
                    </div>
                    <div>
                        <input
                            v-model="item.unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Unit price"
                            class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                        >
                        <p v-if="fieldError(`items.${index}.unit_price`)" class="mt-1 text-xs font-medium text-rose-700">
                            {{ fieldError(`items.${index}.unit_price`) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="mt-1 text-zinc-400 hover:text-rose-600 disabled:opacity-30"
                        :disabled="form.items.length === 1"
                        title="Remove item"
                        @click="removeItem(index)"
                    >
                        &times;
                    </button>
                </div>
            </div>

            <p v-if="fieldError('items')" class="mt-1 text-xs font-medium text-rose-700">
                {{ fieldError('items') }}
            </p>
        </div>

        <div class="flex items-center justify-between border-t border-zinc-200 pt-4">
            <p class="text-sm font-semibold text-zinc-800">
                Total: <span class="font-bold">{{ runningTotal.toFixed(2) }}</span>
            </p>
            <button
                type="submit"
                class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800 disabled:opacity-50"
                :disabled="submitState === 'submitting'"
            >
                {{ submitState === 'submitting' ? 'Saving…' : 'Create sales order' }}
            </button>
        </div>
    </form>
</template>
