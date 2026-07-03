<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { createCustomer, updateCustomer } from '../../api/customers';
import { ApiValidationError } from '../../api/client';
import type { Customer, NewCustomerInput } from '../../types/customers';
import type { ValidationErrors } from '../../types/purchases';

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
            <label class="block text-sm font-semibold text-zinc-800" for="customer_name">Name</label>
            <input
                id="customer_name"
                v-model="form.name"
                type="text"
                class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
            >
            <p v-if="fieldError('name')" class="mt-1 text-xs font-medium text-rose-700">
                {{ fieldError('name') }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="customer_email">Email</label>
                <input
                    id="customer_email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('email')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('email') }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-zinc-800" for="customer_phone">Phone</label>
                <input
                    id="customer_phone"
                    v-model="form.phone"
                    type="text"
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm"
                >
                <p v-if="fieldError('phone')" class="mt-1 text-xs font-medium text-rose-700">
                    {{ fieldError('phone') }}
                </p>
            </div>
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
                {{ submitState === 'submitting' ? 'Saving…' : (customer ? 'Save changes' : 'Create customer') }}
            </button>
        </div>
    </form>
</template>
