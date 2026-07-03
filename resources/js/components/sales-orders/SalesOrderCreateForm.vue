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
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const emit = defineEmits<{ created: [salesOrder: SalesOrder] }>();

const FREE_TEXT_ITEM = 'free_text';

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

const customerIdModel = computed<string>({
    get: () => (form.customer_id === '' ? '' : String(form.customer_id)),
    set: (value: string) => {
        form.customer_id = value === '' ? '' : Number(value);
    },
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

function inventoryItemValue(index: number): string {
    const value = form.items[index].inventory_item_id;
    return value === '' ? FREE_TEXT_ITEM : String(value);
}

function setInventoryItem(index: number, value: string) {
    form.items[index].inventory_item_id = value === FREE_TEXT_ITEM ? '' : Number(value);
    applyInventoryItem(index);
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
                <Label for="customer_id">Customer</Label>
                <Select v-model="customerIdModel" :disabled="customersState === 'loading'">
                    <SelectTrigger id="customer_id" class="mt-1 w-full">
                        <SelectValue placeholder="Select a customer" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="customer in customers" :key="customer.id" :value="String(customer.id)">
                            {{ customer.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="customersState === 'error'" class="mt-1 text-xs font-medium text-destructive">
                    Could not load customers.
                </p>
                <p v-if="fieldError('customer_id')" class="mt-1 text-xs font-medium text-destructive">
                    {{ fieldError('customer_id') }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <Label for="order_date">Order date</Label>
                    <Input id="order_date" v-model="form.order_date" type="date" class="mt-1" />
                    <p v-if="fieldError('order_date')" class="mt-1 text-xs font-medium text-destructive">
                        {{ fieldError('order_date') }}
                    </p>
                </div>
                <div>
                    <Label for="expected_date">Expected date</Label>
                    <Input id="expected_date" v-model="form.expected_date" type="date" class="mt-1" />
                    <p v-if="fieldError('expected_date')" class="mt-1 text-xs font-medium text-destructive">
                        {{ fieldError('expected_date') }}
                    </p>
                </div>
            </div>
        </div>

        <div>
            <Label for="notes">Notes</Label>
            <Textarea id="notes" v-model="form.notes" rows="2" class="mt-1" />
            <p v-if="fieldError('notes')" class="mt-1 text-xs font-medium text-destructive">
                {{ fieldError('notes') }}
            </p>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <span class="block text-sm font-semibold text-zinc-800">Line items</span>
                <Button type="button" variant="outline" size="sm" @click="addItem">
                    Add item
                </Button>
            </div>

            <div class="mt-2 space-y-2">
                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="grid grid-cols-[160px_1fr_90px_110px_32px] items-start gap-2"
                >
                    <div>
                        <Select
                            :model-value="inventoryItemValue(index)"
                            :disabled="inventoryItemsState === 'loading'"
                            @update:model-value="(value) => setInventoryItem(index, String(value))"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Free-text item" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="FREE_TEXT_ITEM">Free-text item</SelectItem>
                                <SelectItem v-for="inventoryItem in inventoryItems" :key="inventoryItem.id" :value="String(inventoryItem.id)">
                                    {{ inventoryItem.sku }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="fieldError(`items.${index}.inventory_item_id`)" class="mt-1 text-xs font-medium text-destructive">
                            {{ fieldError(`items.${index}.inventory_item_id`) }}
                        </p>
                    </div>
                    <div>
                        <Input v-model="item.description" type="text" placeholder="Description" />
                        <p v-if="fieldError(`items.${index}.description`)" class="mt-1 text-xs font-medium text-destructive">
                            {{ fieldError(`items.${index}.description`) }}
                        </p>
                    </div>
                    <div>
                        <Input v-model="item.quantity" type="number" step="0.01" min="0" placeholder="Qty" />
                        <p v-if="fieldError(`items.${index}.quantity`)" class="mt-1 text-xs font-medium text-destructive">
                            {{ fieldError(`items.${index}.quantity`) }}
                        </p>
                    </div>
                    <div>
                        <Input v-model="item.unit_price" type="number" step="0.01" min="0" placeholder="Unit price" />
                        <p v-if="fieldError(`items.${index}.unit_price`)" class="mt-1 text-xs font-medium text-destructive">
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

            <p v-if="fieldError('items')" class="mt-1 text-xs font-medium text-destructive">
                {{ fieldError('items') }}
            </p>
        </div>

        <div class="flex items-center justify-between border-t pt-4">
            <p class="text-sm font-semibold text-zinc-800">
                Total: <span class="font-bold">{{ runningTotal.toFixed(2) }}</span>
            </p>
            <Button type="submit" :disabled="submitState === 'submitting'">
                {{ submitState === 'submitting' ? 'Saving…' : 'Create sales order' }}
            </Button>
        </div>
    </form>
</template>
