<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { createInventoryItem, updateInventoryItem } from '../../api/inventory';
import { fetchAllSuppliers } from '../../api/suppliers';
import { ApiValidationError } from '../../api/client';
import type { InventoryItem, NewInventoryItemInput } from '../../types/inventory';
import type { Supplier, ValidationErrors } from '../../types/purchases';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{ item: InventoryItem | null }>();
const emit = defineEmits<{ saved: [item: InventoryItem]; cancel: [] }>();

const NO_SUPPLIER_VALUE = 'none';

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

const supplierIdModel = computed<string>({
    get: () => (form.supplier_id === '' ? NO_SUPPLIER_VALUE : String(form.supplier_id)),
    set: (value: string) => {
        form.supplier_id = value === NO_SUPPLIER_VALUE ? '' : Number(value);
    },
});

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
                <Label for="item_sku">SKU</Label>
                <Input
                    id="item_sku"
                    v-model="form.sku"
                    type="text"
                    class="mt-1"
                    :aria-invalid="!!fieldError('sku')"
                />
                <p v-if="fieldError('sku')" class="mt-1 text-xs font-medium text-destructive">
                    {{ fieldError('sku') }}
                </p>
            </div>
            <div>
                <Label for="item_name">Name</Label>
                <Input
                    id="item_name"
                    v-model="form.name"
                    type="text"
                    class="mt-1"
                    :aria-invalid="!!fieldError('name')"
                />
                <p v-if="fieldError('name')" class="mt-1 text-xs font-medium text-destructive">
                    {{ fieldError('name') }}
                </p>
            </div>
        </div>

        <div>
            <Label for="item_description">Description</Label>
            <Textarea
                id="item_description"
                v-model="form.description"
                rows="2"
                class="mt-1"
                :aria-invalid="!!fieldError('description')"
            />
            <p v-if="fieldError('description')" class="mt-1 text-xs font-medium text-destructive">
                {{ fieldError('description') }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <Label for="item_quantity">Quantity on hand</Label>
                <Input
                    id="item_quantity"
                    v-model="form.quantity_on_hand"
                    type="number"
                    min="0"
                    step="1"
                    class="mt-1"
                    :aria-invalid="!!fieldError('quantity_on_hand')"
                />
                <p v-if="fieldError('quantity_on_hand')" class="mt-1 text-xs font-medium text-destructive">
                    {{ fieldError('quantity_on_hand') }}
                </p>
            </div>
            <div>
                <Label for="item_reorder_level">Reorder level</Label>
                <Input
                    id="item_reorder_level"
                    v-model="form.reorder_level"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="None"
                    class="mt-1"
                    :aria-invalid="!!fieldError('reorder_level')"
                />
                <p v-if="fieldError('reorder_level')" class="mt-1 text-xs font-medium text-destructive">
                    {{ fieldError('reorder_level') }}
                </p>
            </div>
            <div>
                <Label for="item_unit">Unit</Label>
                <Input
                    id="item_unit"
                    v-model="form.unit"
                    type="text"
                    placeholder="each"
                    class="mt-1"
                    :aria-invalid="!!fieldError('unit')"
                />
                <p v-if="fieldError('unit')" class="mt-1 text-xs font-medium text-destructive">
                    {{ fieldError('unit') }}
                </p>
            </div>
        </div>

        <div>
            <Label for="item_supplier_id">Preferred supplier</Label>
            <Select v-model="supplierIdModel" :disabled="suppliersState === 'loading'">
                <SelectTrigger id="item_supplier_id" class="mt-1 w-full">
                    <SelectValue placeholder="No preferred supplier" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="NO_SUPPLIER_VALUE">No preferred supplier</SelectItem>
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

        <div class="flex items-center justify-end gap-2 border-t pt-4">
            <Button type="button" variant="outline" @click="emit('cancel')">
                Cancel
            </Button>
            <Button type="submit" :disabled="submitState === 'submitting'">
                {{ submitState === 'submitting' ? 'Saving…' : (item ? 'Save changes' : 'Create item') }}
            </Button>
        </div>
    </form>
</template>
