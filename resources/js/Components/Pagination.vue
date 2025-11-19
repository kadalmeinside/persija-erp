<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array, // Menerima array links dari paginator Laravel
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap -mb-1 mt-6">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 dark:text-gray-500 border rounded dark:border-gray-600"
                v-html="link.label"
            />
            <Link
                v-else
                class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded dark:border-gray-600 hover:bg-white dark:hover:bg-gray-700 focus:border-indigo-500 dark:focus:border-indigo-700 focus:text-indigo-500 dark:focus:text-indigo-300"
                :class="{ 'bg-indigo-500 text-white dark:bg-indigo-600 dark:text-white dark:border-indigo-700': link.active }"
                :href="link.url"
                v-html="link.label"
                preserve-scroll
            />
        </template>
    </div>
</template>