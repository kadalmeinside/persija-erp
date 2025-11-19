<script setup>
import { ref, watch, onMounted } from 'vue';
import { XCircleIcon, CheckCircleIcon, InformationCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'; // Sesuaikan jika Anda menggunakan icon lain
import { XMarkIcon } from '@heroicons/vue/20/solid'; // Icon tutup

const props = defineProps({
    message: String,
    type: {
        type: String,
        default: 'info', // Default type if not specified
        validator: (value) => ['success', 'danger', 'warning', 'info'].includes(value),
    },
    duration: {
        type: Number,
        default: 5000, // Durasi default 5 detik
    },
});

const showToast = ref(false);
let timeoutId = null;

const toastClasses = {
    success: 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900 dark:border-green-700 dark:text-green-200',
    danger: 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900 dark:border-red-700 dark:text-red-200',
    warning: 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900 dark:border-yellow-700 dark:text-yellow-200',
    info: 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900 dark:border-blue-700 dark:text-blue-200',
};

const iconComponent = {
    success: CheckCircleIcon,
    danger: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
};

const closeToast = () => {
    showToast.value = false;
    clearTimeout(timeoutId); // Pastikan timeout di-clear saat ditutup manual
};

// Watch for changes in message prop to show the toast
watch(() => props.message, (newMessage) => {
    if (newMessage) {
        showToast.value = true;
        // Set timeout to hide the toast automatically
        timeoutId = setTimeout(() => {
            showToast.value = false;
        }, props.duration);
    } else {
        // If message becomes empty, hide toast and clear timeout
        showToast.value = false;
        clearTimeout(timeoutId);
    }
}, { immediate: true }); // Run immediately on component mount if message is already present
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="transform opacity-0 scale-95 translate-y-full"
        enter-to-class="transform opacity-100 scale-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="transform opacity-100 scale-100 translate-y-0"
        leave-to-class="transform opacity-0 scale-95 translate-y-full"
    >
        <div v-if="showToast"
            :class="[toastClasses[type], 'fixed top-4 right-4 z-50 p-4 border rounded-lg shadow-lg flex items-start space-x-3 max-w-sm w-full']"
            role="alert"
        >
            <component :is="iconComponent[type]" class="h-6 w-6 flex-shrink-0" />
            <div class="flex-1 text-sm font-medium">
                {{ message }}
            </div>
            <button @click="closeToast" class="ml-auto text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                <span class="sr-only">Close notification</span>
                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>
    </Transition>
</template>