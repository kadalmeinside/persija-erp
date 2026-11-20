<script setup>
import { InboxIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: {
        type: String,
        default: 'Belum ada data',
    },
    description: {
        type: String,
        default: 'Data akan muncul di sini setelah ditambahkan atau disesuaikan dengan filter aktif.',
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/40 px-6 py-10 text-center">
        <InboxIcon class="w-10 h-10 text-gray-400 dark:text-gray-500 mb-3" aria-hidden="true" />
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
        <p class="mt-1 max-w-md text-xs text-gray-600 dark:text-gray-400">{{ description }}</p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
