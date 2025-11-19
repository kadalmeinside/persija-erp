<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3';

// Import ikon yang dibutuhkan untuk siswa
import {
    HomeIcon,
    UserCircleIcon,
    DocumentChartBarIcon,
    Cog6ToothIcon,
    ArrowLeftStartOnRectangleIcon,
    XMarkIcon,
    BellIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline';

const page = usePage();

// State untuk sidebar (tetap berguna untuk tampilan desktop jika diperlukan)
const desktopSidebarOpen = ref(true);
const mobileSidebarOpen = ref(false);

// --- MENU KHUSUS UNTUK SISWA ---
const siswaMenu = ref([
    { name: 'Dashboard', route: 'dashboard', icon: HomeIcon, current: 'dashboard' },
    { name: 'Profil Saya', route: 'siswa.profil.show', icon: UserCircleIcon, current: 'siswa.profil.show' }, // Ganti dengan nama route profil siswa Anda
    { name: 'Tagihan Saya', route: 'siswa.tagihan.index', icon: DocumentChartBarIcon, current: 'siswa.tagihan.*' }, // Ganti dengan nama route tagihan siswa Anda
]);

// Helper untuk mengecek state menu aktif
function isLinkActive(pattern) {
    if (!pattern) return false;
    const currentRoute = route().current();
    if (!currentRoute) return false;
    return route().current(pattern) || currentRoute.startsWith(pattern.replace('.*', '.'));
}

// Info Pengguna
const userName = computed(() => page.props.auth?.user?.name ?? 'User');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());

// Fungsi Logout
const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Head :title="$page.props.pageTitle || 'Area Siswa'" />

    <div class="relative min-h-screen md:flex bg-gray-100 dark:bg-gray-900">
        <div v-if="mobileSidebarOpen" @click="mobileSidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity md:hidden" aria-hidden="true"></div>

        <aside :class="[
                    'fixed inset-y-0 left-0 z-50 bg-gray-800 text-gray-300 transform transition-transform duration-300 ease-in-out md:sticky md:translate-x-0 md:flex md:flex-col',
                    mobileSidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full',
                    'md:w-64'
                ]">
            <div class="h-16 flex items-center justify-between px-4 bg-gray-900 flex-shrink-0">
                <Link :href="route('dashboard')" @click="mobileSidebarOpen = false" class="flex items-center">
                    <ApplicationLogo class="block h-9 w-auto fill-current text-white" />
                    <span class="ml-3 text-white text-lg font-semibold">Area Siswa</span>
                </Link>
                <button @click="mobileSidebarOpen = false" class="text-gray-400 hover:text-white md:hidden">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto hidden md:block">
                <template v-for="(item, index) in siswaMenu" :key="'menu-' + index">
                    <Link :href="route(item.route)"
                          :class="['flex items-center px-2 py-2 text-sm font-medium rounded-md group', isLinkActive(item.current) ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white']">
                        <component :is="item.icon" class="mr-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                        {{ item.name }}
                    </Link>
                </template>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white dark:bg-gray-700 shadow-sm sticky top-0 z-30 flex-shrink-0 border-b border-gray-200 dark:border-gray-600">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center">
                            <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="md:hidden inline-flex items-center justify-center rounded-md p-2 text-gray-400 dark:text-gray-300 hover:text-gray-500">
                                <span class="sr-only">Open sidebar</span>
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                             <div class="ml-4">
                                <slot name="header" />
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                           <button class="p-1 rounded-full text-gray-400 dark:text-gray-300 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">View notifications</span>
                                <BellIcon class="h-6 w-6" aria-hidden="true" />
                            </button>
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center text-sm font-medium text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-100">
                                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 mr-2">
                                            <span class="text-sm font-medium leading-none text-indigo-700 dark:text-indigo-300">{{ userInitial }}</span>
                                        </span>
                                        <div>{{ userName }}</div>
                                        <div class="ml-1">
                                            <ChevronDownIcon class="h-4 w-4" />
                                        </div>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        <Cog6ToothIcon class="mr-2 h-4 w-4 inline-block text-gray-400" /> Profil Akun
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        <ArrowLeftStartOnRectangleIcon class="mr-2 h-4 w-4 inline-block text-gray-400" /> Keluar
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto">
                <div class="pb-16 md:pb-0">
                    <slot />
                </div>
            </main>
        </div>

        <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 flex justify-around items-center h-16 border-t border-gray-200 dark:border-gray-700 z-30">
             <template v-for="item in siswaMenu" :key="item.name + '-mobile'">
                <Link :href="route(item.route)"
                      :class="[isLinkActive(item.current) ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200', 'flex flex-col items-center justify-center p-2 text-xs w-full h-full']">
                    <component :is="item.icon" class="h-6 w-6 mb-1" />
                    <span class="truncate">{{ item.name }}</span>
                </Link>
            </template>
        </nav>
    </div>
</template>