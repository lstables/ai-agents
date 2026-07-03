<script setup lang="ts">
import { computed, ref } from 'vue';
import InventoryItemForm from './InventoryItemForm.vue';
import InventoryTable from './InventoryTable.vue';
import type { InventoryItem } from '../../types/inventory';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

type FormMode = { kind: 'create' } | { kind: 'edit'; item: InventoryItem } | null;

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

function startEdit(item: InventoryItem) {
    formMode.value = { kind: 'edit', item };
}

function closeForm() {
    formMode.value = null;
}

function handleSaved(item: InventoryItem) {
    const wasCreate = formMode.value?.kind === 'create';
    formMode.value = null;
    confirmation.value = wasCreate ? `Inventory item "${item.name}" created.` : `Inventory item "${item.name}" updated.`;
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
                <p class="text-sm font-semibold text-cyan-700">Inventory</p>
                <h2 class="mt-1 text-2xl font-bold text-zinc-950">Inventory items</h2>
            </div>
            <Button type="button" @click="startCreate">
                New item
            </Button>
        </div>

        <div v-if="confirmation" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
            {{ confirmation }}
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ formMode?.kind === 'create' ? 'New inventory item' : 'Edit inventory item' }}</DialogTitle>
                </DialogHeader>
                <InventoryItemForm
                    v-if="formMode !== null"
                    :item="formMode.kind === 'edit' ? formMode.item : null"
                    @saved="handleSaved"
                    @cancel="closeForm"
                />
            </DialogContent>
        </Dialog>

        <InventoryTable :refresh-token="refreshToken" @edit="startEdit" />
    </div>
</template>
