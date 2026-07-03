<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue';
import { createInventoryItem, updateInventoryItem } from '../../api/inventory';
import { fetchAllSuppliers } from '../../api/suppliers';
import { ApiValidationError } from '../../api/client';
import type { InventoryItem, NewInventoryItemInput } from '../../types/inventory';
import type { Supplier, ValidationErrors } from '../../types/purchases';

const props = defineProps<{ item: InventoryItem | null }>();
const emit = defineEmits<{ saved: [item: InventoryItem]; cancel: [] }>();

const submitState = ref<'idle' | 'submitting' | 'error'>('idle');
const errors = ref<ValidationErrors>({});
const generalError = ref('');
const suppliers = ref<Supplier[]>([]);
const suppliersState = ref<'loading' | 'ready' | 'error'>('loading');

function toInput(item: InventoryItem | null): NewInventoryItemInput {
    return {
        sku: item?.sku ?? '',
        name: item?.name ?? '',
        description: item?.description ?? '',
        quantity_on_hand: item ? String(item.quantity_on_hand) : '0',
        reorder_level: item?.reorder_level != null ? String(item.reorder_level) : '',
        unit: item?.unit ?? '',
        supplier_id: item?.supplier?.id ?? '',
    };
}

const form = reactive<NewInventoryItemInput>(toInput(props.item));

watch(() => props.item, (item) => {
    Object.assign(form, toInput(item));
    errors.value = {};
    generalError.value = '';
});

async function loadSuppliers() {
    suppliersState.value = 'loading';

    try {
        const response = await fetchAllSuppliers();
        suppliers.value = response.data;
        suppliersState.value = 'ready';
    } catch {
        suppliersState.value = 'error';
    }
}

onMounted(loadSuppliers);

function fieldError(field: string): string | null {
    return errors.value[field]?.[0] ?? null;
}

async function submit() {
    submitState.value = 'submitting';
    errors.value = {};
    generalError.value = '';

    try {
        const response = props.item
            ? await updateInventoryItem(props.item.id, form)
            : await createInventoryItem(form);

        submitState.value = 'idle';
        emit('saved', response.data);
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

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="item_sku">SKU</label>
                <input
                    id="item_sku"
                    v-model="form.sku"
                    type="text"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('sku')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('sku') }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="item_name">Name</label>
                <input
                    id="item_name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('name')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('name') }}
                </p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-zinc-800" for="item_description">Description</label>
            <textarea
                id="item_description"
                v-model="form.description"
                rows="2"
                class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
            />
            <p v-if="fieldError('description')" class="mt-1 text-xs font-medium text-rose-700">
                {{ fieldError('description') }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="item_quantity">Quantity on hand</label>
                <input
                    id="item_quantity"
                    v-model="form.quantity_on_hand"
                    type="number"
                    min="0"
                    step="1"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('quantity_on_hand')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('quantity_on_hand') }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="item_reorder_level">Reorder level</label>
                <input
                    id="item_reorder_level"
                    v-model="form.reorder_level"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="None"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('reorder_level')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('reorder_level') }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="item_unit">Unit</label>
                <input
                    id="item_unit"
                    v-model="form.unit"
                    type="text"
                    placeholder="each"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('unit')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('unit') }}
                </p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-zinc-800" for="item_supplier_id">Preferred supplier</label>
            <select
                id="item_supplier_id"
                v-model="form.supplier_id"
                class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                :disabled="suppliersState === 'loading'"
            >
                <option value="">No preferred supplier</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                    {{ supplier.name }}
                </option>
            </select>
            <p v-if="suppliersState === 'error'" class="mt-1 text-xs font-medium text-rose-700">
                Could not load suppliers.
            </p>
            <p v-if="fieldError('supplier_id')" class="mt-1 text-xs font-medium text-rose-700">
                {{ fieldError('supplier_id') }}
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
                :disabled="submitState === 'submitting'"
            >
                {{ submitState === 'submitting' ? 'Saving…' : (item ? 'Save changes' : 'Create item') }}
            </button>
        </div>
    </form>
</template>
