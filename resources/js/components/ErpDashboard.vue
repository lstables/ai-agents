<script setup lang="ts">
import { ref } from 'vue';
import DashboardHome from './DashboardHome.vue';
import PurchasesPage from './purchases/PurchasesPage.vue';
import SuppliersPage from './suppliers/SuppliersPage.vue';
import InventoryPage from './inventory/InventoryPage.vue';
import CustomersPage from './customers/CustomersPage.vue';
import SalesOrdersPage from './sales-orders/SalesOrdersPage.vue';
import ReportsPage from './reports/ReportsPage.vue';
import FinancePage from './finance/FinancePage.vue';

const modules = [
    'Purchasing',
    'Inventory',
    'Sales Orders',
    'Suppliers',
    'Customers',
    'Finance',
    'Workflow',
    'Reports',
];

const activeModule = ref<string | null>(null);

function selectModule(module: string) {
    activeModule.value = module;
}

function showDashboard() {
    activeModule.value = null;
}
</script>

<template>
    <main class="min-h-screen bg-zinc-100 text-zinc-950">
        <div class="flex min-h-screen">
            <aside class="hidden w-64 border-r border-zinc-200 bg-zinc-950 px-5 py-6 text-white lg:block">
                <div class="mb-8">
                    <button type="button" class="block text-left" @click="showDashboard">
                        <p class="text-xs font-semibold uppercase tracking-normal text-cyan-300">AI-built ERP</p>
                        <h1 class="mt-2 text-2xl font-bold">Operator</h1>
                    </button>
                </div>

                <nav class="space-y-1">
                    <button
                        v-for="module in modules"
                        :key="module"
                        type="button"
                        class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-zinc-300 hover:bg-zinc-800 hover:text-white"
                        :class="{ 'bg-zinc-800 text-white': activeModule === module }"
                        @click="selectModule(module)"
                    >
                        {{ module }}
                    </button>
                </nav>
            </aside>

            <section class="flex-1">
                <header class="border-b border-zinc-200 bg-white px-5 py-4 sm:px-8">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-cyan-700">ERP workspace</p>
                            <h2 class="mt-1 text-2xl font-bold text-zinc-950">Operational dashboard</h2>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button class="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">
                                New feature brief
                            </button>
                            <button class="rounded-md border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-50">
                                View agent tasks
                            </button>
                        </div>
                    </div>
                </header>

                <div class="px-5 py-6 sm:px-8">
                    <DashboardHome v-if="activeModule === null" />
                    <PurchasesPage v-else-if="activeModule === 'Purchasing'" />
                    <SuppliersPage v-else-if="activeModule === 'Suppliers'" />
                    <InventoryPage v-else-if="activeModule === 'Inventory'" />
                    <CustomersPage v-else-if="activeModule === 'Customers'" />
                    <SalesOrdersPage v-else-if="activeModule === 'Sales Orders'" />
                    <ReportsPage v-else-if="activeModule === 'Reports'" />
                    <FinancePage v-else-if="activeModule === 'Finance'" />
                    <div v-else class="rounded-lg border border-dashed border-zinc-300 bg-white p-8 text-center text-sm text-zinc-500">
                        {{ activeModule }} has not been built yet.
                    </div>
                </div>
            </section>
        </div>
    </main>
</template>
