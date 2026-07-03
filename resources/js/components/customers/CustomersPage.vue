<script setup lang="ts">
import { ref } from 'vue';
import CustomerForm from './CustomerForm.vue';
import CustomersTable from './CustomersTable.vue';
import type { Customer } from '../../types/customers';

type FormMode = { kind: 'create' } | { kind: 'edit'; customer: Customer } | null;

const formMode = ref<FormMode>(null);
const refreshToken = ref(0);
const confirmation = ref('');

function startCreate() {
    formMode.value = { kind: 'create' };
}

function startEdit(customer: Customer) {
    formMode.value = { kind: 'edit', customer };
}

function closeForm() {
    formMode.value = null;
}

function handleSaved(customer: Customer) {
    const wasCreate = formMode.value?.kind === 'create';
    formMode.value = null;
    confirmation.value = wasCreate ? `Customer "${customer.name}" created.` : `Customer "${customer.name}" updated.`;
    refreshToken.value += 1;

    setTimeout(() => {
        confirmation.value = '';
    }, 4000);
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-cyan-700">Customers</p>
                <h2 class="mt-1 text-2xl font-bold text-zinc-950">Customers</h2>
            </div>
            <button
                v-if="formMode === null"
                type="button"
                class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800"
                @click="startCreate"
            >
                New customer
            </button>
        </div>

        <div v-if="confirmation" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ confirmation }}
        </div>

        <div v-if="formMode !== null" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-lg font-bold text-zinc-950">
                {{ formMode.kind === 'create' ? 'New customer' : 'Edit customer' }}
            </h3>
            <CustomerForm
                :customer="formMode.kind === 'edit' ? formMode.customer : null"
                @saved="handleSaved"
                @cancel="closeForm"
            />
        </div>

        <CustomersTable :refresh-token="refreshToken" @edit="startEdit" />
    </div>
</template>
