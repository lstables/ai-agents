<script setup lang="ts">
import { ref } from 'vue';
import SupplierForm from './SupplierForm.vue';
import SuppliersTable from './SuppliersTable.vue';
import type { Supplier } from '../../types/purchases';

type FormMode = { kind: 'create' } | { kind: 'edit'; supplier: Supplier } | null;

const formMode = ref<FormMode>(null);
const refreshToken = ref(0);
const confirmation = ref('');

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
            <button
                v-if="formMode === null"
                type="button"
                class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800"
                @click="startCreate"
            >
                New supplier
            </button>
        </div>

        <div v-if="confirmation" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ confirmation }}
        </div>

        <div v-if="formMode !== null" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-lg font-bold text-zinc-950">
                {{ formMode.kind === 'create' ? 'New supplier' : 'Edit supplier' }}
            </h3>
            <SupplierForm
                :supplier="formMode.kind === 'edit' ? formMode.supplier : null"
                @saved="handleSaved"
                @cancel="closeForm"
            />
        </div>

        <SuppliersTable :refresh-token="refreshToken" @edit="startEdit" />
    </div>
</template>
