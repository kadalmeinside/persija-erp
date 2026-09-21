<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const notifications = computed(() => page.props.notifications?.list || []);
const count = computed(() => page.props.notifications?.count || 0);

const isOpen = ref(false);
const dropdownRef = ref(null);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', closeDropdown));
onUnmounted(() => document.removeEventListener('click', closeDropdown));
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <!-- Bell Icon -->
        <button @click.stop="toggleDropdown" class="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 relative">
            <span class="sr-only">View notifications</span>
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            
            <!-- Badge -->
            <span v-if="count > 0" class="absolute top-0 right-0 block h-2.5 w-2.5 rounded-full ring-2 ring-white bg-red-500"></span>
        </button>

        <!-- Dropdown Menu -->
        <div v-show="isOpen" class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
            <div class="px-4 py-2 border-b flex justify-between items-center">
                <span class="text-sm text-gray-700 font-bold">Notifikasi ({{ count }})</span>
                <Link v-if="count > 0" :href="route('admin.notifications.mark-all-read')" method="post" as="button" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                    Tandai semua dibaca
                </Link>
            </div>
            
            <div v-if="notifications.length === 0" class="px-4 py-2 text-sm text-gray-500">
                Tidak ada notifikasi baru.
            </div>

            <div v-else class="max-h-64 overflow-y-auto">
                <Link 
                    v-for="notification in notifications" 
                    :key="notification.id"
                    :href="route('admin.notifications.read', notification.id)"
                    class="block px-4 py-3 hover:bg-gray-50 transition duration-150 ease-in-out border-b border-gray-100 last:border-0"
                >
                    <p class="text-sm font-medium text-gray-900">{{ notification.data.message }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ new Date(notification.created_at).toLocaleString() }}</p>
                </Link>
            </div>
        </div>
    </div>
</template>
