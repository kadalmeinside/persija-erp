<script setup>
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: {
        type: String,
        default: 'Gagal memuat data',
    },
    message: {
        type: String,
        default: 'Terjadi gangguan saat mengambil data dari server. Silakan coba lagi.',
    },
});

const emit = defineEmits(['retry']);
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-xl border border-red-200 dark:border-red-900/60 bg-red-50/70 dark:bg-red-950/20 px-6 py-8 text-center" role="alert">
        <ExclamationTriangleIcon class="w-9 h-9 text-red-600 dark:text-red-400 mb-2.5" aria-hidden="true" />
        <h3 class="text-sm font-semibold text-red-900 dark:text-red-200">{{ title }}</h3>
        <p class="mt-1 max-w-md text-xs text-red-700 dark:text-red-300">{{ message }}</p>
        <button
            type="button"
            @click="emit('retry')"
            class="mt-4 inline-flex items-center rounded-lg border border-red-300 dark:border-red-700 bg-white dark:bg-gray-800 px-3.5 py-2 text-xs font-medium text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30"
        >
            Coba Lagi
        </button>
    </div>
</template>
