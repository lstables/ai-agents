<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { createCustomer, updateCustomer } from '../../api/customers';
import { ApiValidationError } from '../../api/client';
import type { Customer, NewCustomerInput } from '../../types/customers';
import type { ValidationErrors } from '../../types/purchases';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ customer: Customer | null }>();
const emit = defineEmits<{ saved: [customer: Customer]; cancel: [] }>();

const submitState = ref<'idle' | 'submitting' | 'error'>('idle');
const errors = ref<ValidationErrors>({});
const generalError = ref('');

function toInput(customer: Customer | null): NewCustomerInput {
    return {
        name: customer?.name ?? '',
        email: customer?.email ?? '',
        phone: customer?.phone ?? '',
    };
}

const form = reactive<NewCustomerInput>(toInput(props.customer));

watch(() => props.customer, (customer) => {
    Object.assign(form, toInput(customer));
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
        const response = props.customer
            ? await updateCustomer(props.customer.id, form)
            : await createCustomer(form);

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
            <Label for="customer_name">Name</Label>
            <Input
                id="customer_name"
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
                <Label for="customer_email">Email</Label>
                <Input
                    id="customer_email"
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
                <Label for="customer_phone">Phone</Label>
                <Input
                    id="customer_phone"
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
                {{ submitState === 'submitting' ? 'Saving…' : (customer ? 'Save changes' : 'Create customer') }}
            </Button>
        </div>
    </form>
</template>
