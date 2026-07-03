<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { createSupplier, updateSupplier } from '../../api/suppliers';
import { ApiValidationError } from '../../api/client';
import type { Supplier, ValidationErrors } from '../../types/purchases';
import type { NewSupplierInput } from '../../types/suppliers';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ supplier: Supplier | null }>();
const emit = defineEmits<{ saved: [supplier: Supplier]; cancel: [] }>();

const submitState = ref<'idle' | 'submitting' | 'error'>('idle');
const errors = ref<ValidationErrors>({});
const generalError = ref('');

function toInput(supplier: Supplier | null): NewSupplierInput {
    return {
        name: supplier?.name ?? '',
        email: supplier?.email ?? '',
        phone: supplier?.phone ?? '',
    };
}

const form = reactive<NewSupplierInput>(toInput(props.supplier));

watch(() => props.supplier, (supplier) => {
    Object.assign(form, toInput(supplier));
    errors.value = {};
    generalError.value = '';
});

function fieldError(field: string): string | null {
    return errors.value[field]?.[0] ?? null;
}

async function submit() {
    submitState.value = 'submitting';
    errors.value = {};
    generalError.value = '';

    try {
        const response = props.supplier
            ? await updateSupplier(props.supplier.id, form)
            : await createSupplier(form);

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

        <div>
            <Label for="supplier_name">Name</Label>
            <Input
                id="supplier_name"
                v-model="form.name"
                type="text"
                class="mt-1"
                :aria-invalid="!!fieldError('name')"
            />
            <p v-if="fieldError('name')" class="mt-1 text-xs font-medium text-destructive">
                {{ fieldError('name') }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <Label for="supplier_email">Email</Label>
                <Input
                    id="supplier_email"
                    v-model="form.email"
                    type="email"
                    class="mt-1"
                    :aria-invalid="!!fieldError('email')"
                />
                <p v-if="fieldError('email')" class="mt-1 text-xs font-medium text-destructive">
                    {{ fieldError('email') }}
                </p>
            </div>
            <div>
                <Label for="supplier_phone">Phone</Label>
                <Input
                    id="supplier_phone"
                    v-model="form.phone"
                    type="text"
                    class="mt-1"
                    :aria-invalid="!!fieldError('phone')"
                />
                <p v-if="fieldError('phone')" class="mt-1 text-xs font-medium text-destructive">
                    {{ fieldError('phone') }}
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t pt-4">
            <Button type="button" variant="outline" @click="emit('cancel')">
                Cancel
            </Button>
            <Button type="submit" :disabled="submitState === 'submitting'">
                {{ submitState === 'submitting' ? 'Saving…' : (supplier ? 'Save changes' : 'Create supplier') }}
            </Button>
        </div>
    </form>
</template>
