<script setup lang="ts">
import { computed, ref } from 'vue';
import SupplierForm from './SupplierForm.vue';
import SuppliersTable from './SuppliersTable.vue';
import type { Supplier } from '../../types/purchases';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

type FormMode = { kind: 'create' } | { kind: 'edit'; supplier: Supplier } | null;

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

function startEdit(supplier: Supplier) {
    formMode.value = { kind: 'edit', supplier };
}

function closeForm() {
    formMode.value = null;
}

function handleSaved(supplier: Supplier) {
    const wasCreate = formMode.value?.kind === 'create';
    formMode.value = null;
    confirmation.value = wasCreate ? `Supplier "${supplier.name}" created.` : `Supplier "${supplier.name}" updated.`;
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
                <p class="text-sm font-semibold text-cyan-700">Suppliers</p>
                <h2 class="mt-1 text-2xl font-bold text-zinc-950">Suppliers</h2>
            </div>
            <Button type="button" @click="startCreate">
                New supplier
            </Button>
        </div>

        <div v-if="confirmation" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ confirmation }}
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ formMode?.kind === 'create' ? 'New supplier' : 'Edit supplier' }}</DialogTitle>
                </DialogHeader>
                <SupplierForm
                    v-if="formMode !== null"
                    :supplier="formMode.kind === 'edit' ? formMode.supplier : null"
                    @saved="handleSaved"
                    @cancel="closeForm"
                />
            </DialogContent>
        </Dialog>

        <SuppliersTable :refresh-token="refreshToken" @edit="startEdit" />
    </div>
</template>
