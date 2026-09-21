<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

defineProps({
    links: Array,
});

const isPrevious = (label) => {
    return label.includes('Previous') || label.includes('pagination.previous') || label.includes('&laquo;');
};

const isNext = (label) => {
    return label.includes('Next') || label.includes('pagination.next') || label.includes('&raquo;');
};
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap -mb-1 mt-6 justify-center md:justify-end">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 dark:text-gray-500 border rounded dark:border-gray-600 bg-gray-50 dark:bg-gray-800"
            >
                <span v-if="isPrevious(link.label)"><ChevronLeftIcon class="w-4 h-4" /></span>
                <span v-else-if="isNext(link.label)"><ChevronRightIcon class="w-4 h-4" /></span>
                <span v-else v-html="link.label"></span>
            </div>
            
            <Link
                v-else
                class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded dark:border-gray-600 hover:bg-white dark:hover:bg-gray-700 focus:border-indigo-500 dark:focus:border-indigo-700 focus:text-indigo-500 dark:focus:text-indigo-300 transition-colors duration-150"
                :class="{ 'bg-indigo-500 text-white dark:bg-indigo-600 dark:text-white dark:border-indigo-700': link.active, 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300': !link.active }"
                :href="link.url"
                preserve-scroll
            >
                <span v-if="isPrevious(link.label)" class="flex items-center"><ChevronLeftIcon class="w-4 h-4" /></span>
                <span v-else-if="isNext(link.label)" class="flex items-center"><ChevronRightIcon class="w-4 h-4" /></span>
                <span v-else v-html="link.label"></span>
            </Link>
        </template>
    </div>
</template>