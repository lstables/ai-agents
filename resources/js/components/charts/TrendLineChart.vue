<script setup lang="ts">
import { computed } from 'vue';

type Series = {
    name: string;
    color: string;
    values: number[];
};

const props = defineProps<{
    series: Series[];
    labels: string[];
}>();

const width = 600;
const height = 180;
const paddingX = 10;
const paddingY = 10;

const maxValue = computed(() => {
    const allValues = props.series.flatMap((series) => series.values);
    return Math.max(1, ...allValues);
});

function pointsFor(values: number[]): string {
    const usableWidth = width - paddingX * 2;
    const usableHeight = height - paddingY * 2;
    const step = values.length > 1 ? usableWidth / (values.length - 1) : 0;

    return values
        .map((value, index) => {
            const x = paddingX + index * step;
            const y = paddingY + usableHeight - (value / maxValue.value) * usableHeight;
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');
}

const firstLabel = computed(() => props.labels[0] ?? '');
const lastLabel = computed(() => props.labels[props.labels.length - 1] ?? '');
</script>

<template>
    <div>
        <svg :viewBox="`0 0 ${width} ${height}`" class="w-full" role="img" aria-label="Activity trend chart">
            <polyline
                v-for="line in series"
                :key="line.name"
                :points="pointsFor(line.values)"
                fill="none"
                :stroke="line.color"
                stroke-width="2"
            />
        </svg>
        <div class="mt-1 flex justify-between text-xs text-zinc-500">
            <span>{{ firstLabel }}</span>
            <span>{{ lastLabel }}</span>
        </div>
        <div class="mt-3 flex flex-wrap gap-4 text-sm">
            <div v-for="line in series" :key="line.name" class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: line.color }" />
                <span class="text-zinc-700">{{ line.name }}</span>
            </div>
        </div>
    </div>
</template>
