<script setup>
defineProps({
    rows: {
        type: Number,
        default: 5,
    },
    label: {
        type: String,
        default: 'Memuat data...',
    },
});
</script>

<template>
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 space-y-3" role="status" aria-live="polite">
        <span class="sr-only">{{ label }}</span>
        <div class="flex items-center justify-between">
            <div class="h-4 w-40 rounded bg-gray-200 dark:bg-gray-700 animate-pulse"></div>
            <div class="h-4 w-24 rounded bg-gray-200 dark:bg-gray-700 animate-pulse"></div>
        </div>
        <div v-for="n in rows" :key="n" class="h-10 w-full rounded-lg bg-gray-100 dark:bg-gray-700/60 animate-pulse"></div>
    </div>
</template>
