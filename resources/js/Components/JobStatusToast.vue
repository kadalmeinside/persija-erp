<script setup>
import { computed } from 'vue';
import { ArrowPathIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    show: Boolean,
    status: String, // 'processing', 'finished', 'failed'
    message: String,
    progress: Number,
});

const emit = defineEmits(['close']);

const statusClasses = computed(() => {
    if (props.status === 'finished') return 'bg-green-500 border-green-600';
    if (props.status === 'failed') return 'bg-red-500 border-red-600';
    return 'bg-blue-500 border-blue-600'; // processing
});
</script>

<template>
    <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
    >
        <div v-if="show" :class="statusClasses" class="fixed bottom-5 right-5 max-w-sm w-full shadow-lg rounded-lg pointer-events-auto text-white border-b-4 p-4 z-50">
            <div class="flex items-start">
                <div class="flex-shrink-0 pt-0.5">
                    <ArrowPathIcon v-if="status === 'processing'" class="h-6 w-6 animate-spin"/>
                    <CheckCircleIcon v-else-if="status === 'finished'" class="h-6 w-6"/>
                    <XCircleIcon v-else-if="status === 'failed'" class="h-6 w-6"/>
                </div>
                <div class="ml-3 w-0 flex-1">
                    <p class="text-sm font-bold">Proses Latar Belakang</p>
                    <p class="mt-1 text-sm">{{ message }}</p>
                    <div v-if="status === 'processing' && progress > 0" class="w-full bg-black/20 rounded-full h-1.5 mt-2 overflow-hidden">
                        <div class="bg-white h-1.5 rounded-full transition-all duration-300 ease-linear" :style="{ width: progress + '%' }"></div>
                    </div>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button @click="$emit('close')" class="inline-flex rounded-md text-current opacity-75 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-white">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>
