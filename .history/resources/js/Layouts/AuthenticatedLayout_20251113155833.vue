<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import JobStatusToast from '@/Components/JobStatusToast.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    HomeIcon,
    UsersIcon,
    UserCircleIcon,
    ShieldCheckIcon,
    Cog6ToothIcon,
    ArrowLeftStartOnRectangleIcon,
    XMarkIcon,
    ChevronDownIcon,
    BellIcon,
    BuildingOfficeIcon,
    UserGroupIcon,
    DocumentChartBarIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const showJobToast = ref(false);
const jobStatus = ref('');
const jobMessage = ref('');
const jobProgress = ref(0);

onMounted(() => {
    if (window.Echo && user.value) {
        window.Echo.private(`App.Models.User.${user.value.id}`)
            .listen('.mass-invoice.status', (e) => {
                console.log('Event received:', e);
                jobStatus.value = e.status;
                jobMessage.value = e.message;
                jobProgress.value = e.progress;
                showJobToast.value = true;

                if (e.status === 'finished' || e.status === 'failed') {
                    setTimeout(() => { showJobToast.value = false; }, 8000);
                }
            });
    }
});

// --- STATE & PROPS ---
const desktopSidebarOpen = ref(true);
const mobileSidebarOpen = ref(false);

const userRoles = computed(() => page.props.auth?.user?.roles || []);
const userPermissions = computed(() => page.props.auth?.user?.permissions || []);
const userName = computed(() => page.props.auth?.user?.name ?? 'User');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());
const appSettings = computed(() => page.props.app_settings || {});
const appName = computed(() => appSettings.value.app_name || 'Manajemen SPP');
const appLogo = computed(() => appSettings.value.app_logo || null);

// --- THEME MANAGEMENT ---
const themes = {
    gray: { 50: '#f9fafb', 100: '#f3f4f6', 200: '#e5e7eb', 300: '#d1d5db', 400: '#9ca3af', 500: '#6b7280', 600: '#4b5563', 700: '#374151', 800: '#1f2937', 900: '#111827' },
    maroon: { 50: '#fef2f2', 100: '#fee2e2', 200: '#fecaca', 300: '#fca5a5', 400: '#f87171', 500: '#ef4444', 600: '#dc2626', 700: '#b91c1c', 800: '#991b1b', 900: '#7f1d1d' },
    indigo: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81' },
    blue: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' },
    teal: { 50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4', 400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e', 800: '#115e59', 900: '#134e4a' },
    rose: { 50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd3', 300: '#fda4af', 400: '#fb7185', 500: '#f43f5e', 600: '#e11d48', 700: '#be123c', 800: '#9f1239', 900: '#881337' },
};

const currentTheme = ref(localStorage.getItem('app-theme') || 'indigo');

const applyTheme = (themeName) => {
    const themeColors = themes[themeName];
    if (!themeColors) {
        console.warn(`Theme "${themeName}" not found. Falling back to indigo.`);
        const fallback = themes['indigo'];
        if (fallback) {
            for (const [shade, color] of Object.entries(fallback)) {
                root.style.setProperty(`--color-primary-${shade}`, color);
            }
            localStorage.setItem('app-theme', 'indigo');
            currentTheme.value = 'indigo';
        }
        return;
    }

    const root = document.documentElement;
    for (const [shade, color] of Object.entries(themeColors)) {
        root.style.setProperty(`--color-primary-${shade}`, color);
    }
    localStorage.setItem('app-theme', themeName);
    currentTheme.value = themeName;
};

watch(currentTheme, (newTheme) => {
    applyTheme(newTheme);
});

onMounted(() => {
    applyTheme(currentTheme.value);
});

// --- HELPERS ---
const hasRole = (roleName) => userRoles.value.includes(roleName);
const hasPermission = (permissionName) => userPermissions.value.includes(permissionName);

function isLinkActive(pattern) {
    if (!pattern) return false;
    const currentRoute = route().current();
    if (!currentRoute) return false;
    return route().current(pattern) || currentRoute.startsWith(pattern.replace('.*', '.'));
}

const logout = () => { router.post(route('logout')); };


// --- MENU DEFINITION (REVISED & SIMPLIFIED) ---
const sidebarMenu = computed(() => {
    // Menu untuk Admin & Staff
    if (hasRole('Super Admin') || hasRole('Staf Finance')) {
        return [
            { type: 'heading', label: 'Utama' },
            { type: 'link', name: 'Dashboard', icon: HomeIcon, route: 'admin.dashboard', current: 'admin.dashboard' },

            { type: 'heading', label: 'Manajemen' },
            

            { type: 'heading', label: 'Pengaturan Sistem' },
            { type: 'link', name: 'Users', route: 'admin.users.index', icon: UsersIcon, current: 'admin.users.*', permission: 'user.manage' },
            { type: 'link', name: 'Roles', route: 'admin.roles.index', icon: UserCircleIcon, current: 'admin.roles.*', permission: 'role.manage' },
            { type: 'link', name: 'Permissions', route: 'admin.permissions.index', icon: ShieldCheckIcon, current: 'admin.permissions.*', permission: 'manage permissions' },
            { type: 'link', name: 'Pengaturan Aplikasi', route: 'admin.settings.index', icon: Cog6ToothIcon, current: 'admin.settings.*', permission: 'manage application settings' },
        ];
    }

    // Default menu jika tidak ada role yang cocok
    return [
        { type: 'heading', label: 'Utama' },
        { type: 'link', name: 'Dashboard', icon: HomeIcon, route: 'admin.dashboard', current: 'admin.dashboard' },
    ];
});
</script>

<template>
    <div class="relative min-h-screen md:flex bg-gray-100 dark:bg-gray-900">
        <div v-if="mobileSidebarOpen" @click="mobileSidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-50 z-20 transition-opacity md:hidden" aria-hidden="true"></div>

        <aside :class="[
                    'fixed inset-y-0 left-0 z-30 bg-[--color-primary-800] text-gray-300 transform transition-transform duration-300 ease-in-out md:sticky md:translate-x-0 md:flex md:flex-col',
                    mobileSidebarOpen ? 'translate-x-0 w-64 sm:w-72' : '-translate-x-full w-64 sm:w-72',
                    desktopSidebarOpen ? 'md:w-64' : 'md:w-20'
                ]">
            <div class="h-16 flex items-center justify-between px-4 bg-black/20 flex-shrink-0">
                <Link :href="hasRole('admin') || hasRole('staff_akademik') ? route('admin.dashboard') : route('dashboard')" @click="mobileSidebarOpen = false" class="flex items-center overflow-hidden">
                    <img v-if="appLogo" :src="`/storage/${appLogo}`" alt="App Logo" class="block h-9 w-auto">
                    <ApplicationLogo v-else class="block h-9 w-auto fill-current text-white" />
                    
                    <span v-show="desktopSidebarOpen || mobileSidebarOpen" class="ml-3 text-white text-lg font-semibold truncate">{{ appName }}</span>
                </Link>
                <button @click="mobileSidebarOpen = false" class="text-gray-400 hover:text-white md:hidden">
                    <span class="sr-only">Close sidebar</span>
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                <template v-for="(item, index) in sidebarMenu" :key="index">
                    <!-- Tampilkan Heading -->
                    <h3 v-if="item.type === 'heading'" v-show="desktopSidebarOpen || mobileSidebarOpen" class="px-2 pt-4 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        {{ item.label }}
                    </h3>
                    
                    <!-- Tampilkan Link, cek permission jika ada -->
                    <Link v-if="item.type === 'link' && (!item.permission || hasPermission(item.permission))"
                          :href="item.route ? route(item.route) : '#'"
                          @click="mobileSidebarOpen = false"
                          :class="['flex items-center px-2 py-2 text-sm font-medium rounded-md group', isLinkActive(item.current) ? 'bg-[--color-primary-600] text-white' : 'text-gray-300 hover:bg-[--color-primary-700] hover:text-white']">
                        <component :is="item.icon" class="mr-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                        <span v-show="desktopSidebarOpen || mobileSidebarOpen">{{ item.name }}</span>
                    </Link>
                </template>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-10 flex-shrink-0 border-b border-gray-200 dark:border-gray-700">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center">
                            <button @click="desktopSidebarOpen = !desktopSidebarOpen" class="hidden md:inline-flex items-center justify-center rounded-md p-2 text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700">
                                <span class="sr-only">Toggle desktop sidebar</span>
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                            </button>
                            <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="md:hidden inline-flex items-center justify-center rounded-md p-2 text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700">
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
                            <button class="p-1 rounded-full text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-100 focus:outline-none">
                                <span class="sr-only">View notifications</span>
                                <BellIcon class="h-6 w-6" aria-hidden="true" />
                            </button>

                            <div class="relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="flex items-center text-sm font-medium text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition duration-150 ease-in-out">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-[--color-primary-100] dark:bg-[--color-primary-900] mr-2">
                                                <span class="text-sm font-medium leading-none text-[--color-primary-700] dark:text-[--color-primary-300]">{{ userInitial }}</span>
                                            </span>
                                            <div>{{ userName }}</div>
                                            <div class="ml-1">
                                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ userName }}</p>
                                        </div>
                                        <DropdownLink :href="route('profile.edit')">
                                            <Cog6ToothIcon class="mr-2 h-4 w-4 inline-block text-gray-400" /> Profil
                                        </DropdownLink>
                                        <div class="px-4 py-3 border-b border-t dark:border-gray-600">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pilih Tema</p>
                                            <div class="mt-2 flex items-center space-x-2">
                                                <button @click="currentTheme = 'gray'" title="Default" class="h-6 w-6 rounded-full bg-gray-500 focus:outline-none ring-2 ring-offset-2 dark:ring-offset-gray-800" :class="currentTheme === 'gray' ? 'ring-gray-500' : 'ring-transparent'"></button>
                                                <button @click="currentTheme = 'maroon'" title="Maroon" class="h-6 w-6 rounded-full bg-red-800 focus:outline-none ring-2 ring-offset-2 dark:ring-offset-gray-800" :class="currentTheme === 'maroon' ? 'ring-red-500' : 'ring-transparent'"></button>
                                                <button @click="currentTheme = 'indigo'" title="Indigo" class="h-6 w-6 rounded-full bg-indigo-500 focus:outline-none ring-2 ring-offset-2 dark:ring-offset-gray-800" :class="currentTheme === 'indigo' ? 'ring-[--color-primary-500]' : 'ring-transparent'"></button>
                                                <button @click="currentTheme = 'blue'" title="Blue" class="h-6 w-6 rounded-full bg-blue-500 focus:outline-none ring-2 ring-offset-2 dark:ring-offset-gray-800" :class="currentTheme === 'blue' ? 'ring-blue-500' : 'ring-transparent'"></button>
                                                <button @click="currentTheme = 'teal'" title="Teal" class="h-6 w-6 rounded-full bg-teal-500 focus:outline-none ring-2 ring-offset-2 dark:ring-offset-gray-800" :class="currentTheme === 'teal' ? 'ring-teal-500' : 'ring-transparent'"></button>
                                                <button @click="currentTheme = 'rose'" title="Rose" class="h-6 w-6 rounded-full bg-rose-500 focus:outline-none ring-2 ring-offset-2 dark:ring-offset-gray-800" :class="currentTheme === 'rose' ? 'ring-rose-500' : 'ring-transparent'"></button>
                                            </div>
                                        </div>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            <ArrowLeftStartOnRectangleIcon class="mr-2 h-4 w-4 inline-block text-gray-400" /> Keluar
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
    <JobStatusToast :show="showJobToast" :status="jobStatus" :message="jobMessage" :progress="jobProgress" @close="showJobToast = false" />
</template>

<style scoped>
aside::-webkit-scrollbar { width: 6px; }
aside::-webkit-scrollbar-thumb { background-color: #4a5568; border-radius: 3px; }
aside::-webkit-scrollbar-track { background-color: #2d3748; }

main::-webkit-scrollbar { width: 8px; }
main::-webkit-scrollbar-thumb { background-color: #a0aec0; border-radius: 4px; }
main::-webkit-scrollbar-track { background-color: #edf2f7; }

.dark main::-webkit-scrollbar-thumb { background-color: #4a5568; }
.dark main::-webkit-scrollbar-track { background-color: #1a202c; }
</style>
