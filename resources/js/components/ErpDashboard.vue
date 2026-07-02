<script setup lang="ts">
type Metric = {
    label: string;
    value: string;
    trend: string;
    status: 'healthy' | 'warning' | 'attention';
};

type WorkItem = {
    module: string;
    owner: string;
    state: string;
    due: string;
};

const metrics: Metric[] = [
    { label: 'Open orders', value: '148', trend: '+12 this week', status: 'healthy' },
    { label: 'Supplier risks', value: '7', trend: '3 need review', status: 'warning' },
    { label: 'Stock exceptions', value: '23', trend: '5 below reorder', status: 'attention' },
    { label: 'Agent tasks', value: '11', trend: '4 in progress', status: 'healthy' },
];

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

const workItems: WorkItem[] = [
    { module: 'Purchasing', owner: 'Senior developer agent', state: 'Building supplier credit checks', due: 'Today' },
    { module: 'Inventory', owner: 'QA / test agent', state: 'Adding reorder-level regression tests', due: 'Tomorrow' },
    { module: 'Workflow', owner: 'GitHub reviewer agent', state: 'Reviewing permission changes', due: 'Blocked on CI' },
];

const statusClass = {
    healthy: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    warning: 'border-amber-200 bg-amber-50 text-amber-900',
    attention: 'border-rose-200 bg-rose-50 text-rose-800',
};
</script>

<template>
    <main class="min-h-screen bg-zinc-100 text-zinc-950">
        <div class="flex min-h-screen">
            <aside class="hidden w-64 border-r border-zinc-200 bg-zinc-950 px-5 py-6 text-white lg:block">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-normal text-cyan-300">AI-built ERP</p>
                    <h1 class="mt-2 text-2xl font-bold">Operator</h1>
                </div>

                <nav class="space-y-1">
                    <a
                        v-for="module in modules"
                        :key="module"
                        href="#"
                        class="block rounded-md px-3 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800 hover:text-white"
                    >
                        {{ module }}
                    </a>
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
                    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <article
                            v-for="metric in metrics"
                            :key="metric.label"
                            class="rounded-lg border bg-white p-5 shadow-sm"
                            :class="statusClass[metric.status]"
                        >
                            <p class="text-sm font-semibold">{{ metric.label }}</p>
                            <div class="mt-3 flex items-end justify-between gap-3">
                                <span class="text-3xl font-bold">{{ metric.value }}</span>
                                <span class="text-sm font-medium">{{ metric.trend }}</span>
                            </div>
                        </article>
                    </section>

                    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_420px]">
                        <div class="rounded-lg border border-zinc-200 bg-white shadow-sm">
                            <div class="border-b border-zinc-200 px-5 py-4">
                                <h3 class="text-lg font-bold">Active delivery queue</h3>
                            </div>
                            <div class="divide-y divide-zinc-200">
                                <article v-for="item in workItems" :key="item.module" class="grid gap-3 px-5 py-4 md:grid-cols-[160px_1fr_140px]">
                                    <div>
                                        <p class="text-sm font-bold text-zinc-950">{{ item.module }}</p>
                                        <p class="text-xs font-medium text-zinc-500">{{ item.owner }}</p>
                                    </div>
                                    <p class="text-sm text-zinc-700">{{ item.state }}</p>
                                    <p class="text-sm font-semibold text-zinc-600 md:text-right">{{ item.due }}</p>
                                </article>
                            </div>
                        </div>

                        <aside class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                            <h3 class="text-lg font-bold">Agent orchestration</h3>
                            <div class="mt-4 space-y-3">
                                <div class="rounded-md border border-cyan-200 bg-cyan-50 p-3">
                                    <p class="text-sm font-bold text-cyan-950">Team lead session</p>
                                    <p class="mt-1 text-sm text-cyan-800">Splits ERP feature briefs into backend, frontend, QA, and review tasks.</p>
                                </div>
                                <div class="rounded-md border border-violet-200 bg-violet-50 p-3">
                                    <p class="text-sm font-bold text-violet-950">Senior developer agent</p>
                                    <p class="mt-1 text-sm text-violet-800">Builds focused Laravel/Vue feature branches and opens PRs.</p>
                                </div>
                                <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3">
                                    <p class="text-sm font-bold text-emerald-950">QA + reviewer agents</p>
                                    <p class="mt-1 text-sm text-emerald-800">Add regression coverage, check permissions, and comment on GitHub PRs.</p>
                                </div>
                            </div>
                        </aside>
                    </section>
                </div>
            </section>
        </div>
    </main>
</template>
