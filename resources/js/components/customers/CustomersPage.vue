<script setup lang="ts">
import { computed, ref } from 'vue';
import CustomerForm from './CustomerForm.vue';
import CustomersTable from './CustomersTable.vue';
import type { Customer } from '../../types/customers';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

type FormMode = { kind: 'create' } | { kind: 'edit'; customer: Customer } | null;

const formMode = ref<FormMode>(null);
const refreshToken = ref(0);
const confirmation = ref('');

const dialogOpen = computed({
    get: () => formMode.value !== null,
    set: (open: boolean) => {
        if (!open) {
            formMode.value = null;
        }
    },
});

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
            <Button type="button" @click="startCreate">
                New customer
            </Button>
        </div>

        <div v-if="confirmation" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ confirmation }}
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ formMode?.kind === 'create' ? 'New customer' : 'Edit customer' }}</DialogTitle>
                </DialogHeader>
                <CustomerForm
                    v-if="formMode !== null"
                    :customer="formMode.kind === 'edit' ? formMode.customer : null"
                    @saved="handleSaved"
                    @cancel="closeForm"
                />
            </DialogContent>
        </Dialog>

        <CustomersTable :refresh-token="refreshToken" @edit="startEdit" />
    </div>
</template>
