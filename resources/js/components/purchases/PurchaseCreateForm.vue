<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { createPurchase, fetchSuppliers } from '../../api/purchases';
import { ApiValidationError } from '../../api/client';
import type { NewPurchaseInput, Purchase, Supplier, ValidationErrors } from '../../types/purchases';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const emit = defineEmits<{ created: [purchase: Purchase] }>();

const suppliers = ref<Supplier[]>([]);
const suppliersState = ref<'loading' | 'ready' | 'error'>('loading');
const submitState = ref<'idle' | 'submitting' | 'error'>('idle');
const errors = ref<ValidationErrors>({});
const generalError = ref('');

function emptyItem() {
    return { description: '', quantity: '1', unit_price: '0' };
}

const form = reactive<NewPurchaseInput>({
    supplier_id: '',
    order_date: new Date().toISOString().slice(0, 10),
    expected_date: '',
    notes: '',
    items: [emptyItem()],
});

const supplierIdModel = computed<string>({
    get: () => (form.supplier_id === '' ? '' : String(form.supplier_id)),
    set: (value: string) => {
        form.supplier_id = value === '' ? '' : Number(value);
    },
});

const runningTotal = computed(() =>
    form.items.reduce((total, item) => {
        const quantity = Number(item.quantity) || 0;
        const unitPrice = Number(item.unit_price) || 0;

        return total + quantity * unitPrice;
    }, 0),
);

async function loadSuppliers() {
    suppliersState.value = 'loading';

    try {
        const response = await fetchSuppliers();
        suppliers.value = response.data;
        suppliersState.value = 'ready';
    } catch {
        suppliersState.value = 'error';
    }
}

onMounted(loadSuppliers);

function addItem() {
    form.items.push(emptyItem());
}

function removeItem(index: number) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

function fieldError(field: string): string | null {
    return errors.value[field]?.[0] ?? null;
}

function resetForm() {
    form.supplier_id = '';
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
        const response = await createPurchase(form);
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
                <Label for="supplier_id">Supplier</Label>
                <Select v-model="supplierIdModel" :disabled="suppliersState === 'loading'">
                    <SelectTrigger id="supplier_id" class="mt-1 w-full">
                        <SelectValue placeholder="Select a supplier" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="supplier in suppliers" :key="supplier.id" :value="String(supplier.id)">
                            {{ supplier.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="suppliersState === 'error'" class="mt-1 text-xs font-medium text-destructive">
                    Could not load suppliers.
                </p>
                <p v-if="fieldError('supplier_id')" class="mt-1 text-xs font-medium text-destructive">
                    {{ fieldError('supplier_id') }}
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
                    class="grid grid-cols-[1fr_100px_120px_32px] items-start gap-2"
                >
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
                {{ submitState === 'submitting' ? 'Saving…' : 'Create purchase' }}
            </Button>
        </div>
    </form>
</template>
