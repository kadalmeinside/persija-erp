<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import JobStatusToast from '@/Components/JobStatusToast.vue';
import NavbarNotification from '@/Components/NavbarNotification.vue';
// import Toast from '@/Components/Toast.vue'; // Removed custom Toast
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import NavLink from '@/Components/NavLink.vue'; 
import { Link, usePage, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2'; // Import SweetAlert2
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
    BuildingStorefrontIcon,
    BanknotesIcon,
    BookOpenIcon,
    ChartPieIcon, // Added for Budget menu item
    DocumentTextIcon,
    CalendarIcon,
    CheckBadgeIcon,
    CalendarDaysIcon,
    BriefcaseIcon,
    CalculatorIcon,
    ClockIcon,
    ArrowsRightLeftIcon, // Internal Transfer
    LifebuoyIcon, // Added LifebuoyIcon
    LockClosedIcon, LockOpenIcon, PhotoIcon
} from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth?.user);

// --- SWEETALERT2 TOAST CONFIG ---
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }
});

// Watch Flash Messages
watch(() => page.props.flash, (flash) => {
    if (flash?.message) {
        let icon = flash.type || 'success';
        if (icon === 'danger') icon = 'error'; // Map danger to error

        Toast.fire({
            icon: icon,
            title: flash.message
        });
    }
    // Handle specific keys if flash structure is different (e.g. flash.success, flash.error)
    if (flash?.success) {
        Toast.fire({ icon: 'success', title: flash.success });
    }
    if (flash?.error) {
        Toast.fire({ icon: 'error', title: flash.error });
    }
}, { deep: true, immediate: true });

const showJobToast = ref(false);
const jobStatus = ref('');
const jobMessage = ref('');
const jobProgress = ref(0);

onMounted(() => {
    if (window.Echo && user.value) {
        window.Echo.private(`App.Models.User.${user.value.id}`)
            .listen('.mass-invoice.status', (e) => {

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
const openSubmenu = ref('');

// --- PERBAIKAN: Menggunakan path yang benar dari HandleInertiaRequests.php ---
const userRoles = computed(() => page.props.auth?.user?.roles || []);
const userPermissions = computed(() => page.props.auth?.user?.permissions || []);
// --- AKHIR PERBAIKAN ---

const userName = computed(() => page.props.auth?.user?.name ?? 'User');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());
const appSettings = computed(() => page.props.app_settings || {});
const appName = computed(() => appSettings.value.app_name || 'Persija ERP'); 
const appLogo = computed(() => appSettings.value.app_logo || null);
const openTicketsCount = computed(() => page.props.open_tickets_count || 0); // Added ticket count
const isCutiApprover = computed(() => page.props.is_cuti_approver || false);
const isPengajuanApprover = computed(() => page.props.is_pengajuan_approver || false);

// --- THEME MANAGEMENT (Tidak Berubah) ---
const themes = {
    gray: { 50: '#f9fafb', 100: '#f3f4f6', 200: '#e5e7eb', 300: '#d1d5db', 400: '#9ca3af', 500: '#6b7280', 600: '#4b5563', 700: '#374151', 800: '#1f2937', 900: '#111827' },
    maroon: { 50: '#fef2f2', 100: '#fee2e2', 200: '#fecaca', 300: '#fca5a5', 400: '#f87171', 500: '#ef4444', 600: '#dc2626', 700: '#b91c1c', 800: '#991b1b', 900: '#7f1d1d' },
    indigo: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81' },
    blue: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' },
    teal: { 50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4', 400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e', 800: '#115e59', 900: '#134e4a' },
    rose: { 50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd3', 300: '#fda4af', 400: '#fb7185', 500: '#f43f5e', 600: '#e11d48', 700: '#be123c', 800: '#9f1239', 900: '#881337' },
};

const currentTheme = ref(localStorage.getItem('app-theme') || 'gray');

const applyTheme = (themeName) => {
    const themeColors = themes[themeName];
    if (!themeColors) {
        console.warn(`Theme "${themeName}" not found. Falling back to gray.`);
        const fallback = themes['gray'];
        if (fallback) {
            const root = document.documentElement;
            for (const [shade, color] of Object.entries(fallback)) {
                root.style.setProperty(`--color-primary-${shade}`, color);
            }
            localStorage.setItem('app-theme', 'gray');
            currentTheme.value = 'gray';
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
    
    applyTheme(currentTheme.value);
});

// Auto-open submenu logic moved to after sidebarMenu definition

// --- HELPERS (Penting untuk Cek Permission) ---
const hasRole = (roleName) => userRoles.value.includes(roleName);
const hasPermission = (permissionName) => userPermissions.value.includes(permissionName);

function isLinkActive(pattern) {
    if (!pattern) return false;
    const currentRoute = route().current();
    if (!currentRoute) return false;
    
    const patterns = pattern.split(' ');
    return patterns.some(p => {
        if (p.endsWith('.*')) {
            return currentRoute.startsWith(p.slice(0, -1));
        }
        return route().current(p);
    });
}

const toggleSubmenu = (name) => {
    openSubmenu.value = openSubmenu.value === name ? '' : name;
};

// --- ANIMATION HOOKS ---
const enter = (el) => {
    el.style.height = '0';
    el.offsetHeight; // Force reflow
    el.style.transition = 'height 0.3s ease-in-out';
    el.style.height = el.scrollHeight + 'px';
};

const afterEnter = (el) => {
    el.style.height = 'auto';
};

const leave = (el) => {
    el.style.height = el.scrollHeight + 'px';
    el.offsetHeight; // Force reflow
    el.style.transition = 'height 0.3s ease-in-out';
    el.style.height = '0';
};

// --- LOGOUT LOGIC ---
const showLogoutModal = ref(false);

const confirmLogout = () => {
    showLogoutModal.value = true;
};

const logout = () => {
    router.post(route('logout'));
};


// --- MENU DEFINITION (Sesuai ERP Kita) ---
const sidebarMenu = computed(() => {
    const menu = [
        { type: 'heading', label: 'Utama' },
        { 
            type: 'link', 
            name: 'Dashboard', 
            icon: HomeIcon, 
            route: 'admin.dashboard', 
            current: 'admin.dashboard' 
        },
    ];

    // GROUP: PERSONAL (EMPLOYEE SELF-SERVICE)
    menu.push({ type: 'heading', label: 'Personal' });
    
    // Cuti Saya (Visible to All)
    menu.push({ 
        type: 'link',
        name: 'Cuti Saya', 
        route: 'admin.cuti.my-requests', 
        icon: CalendarIcon, 
        current: 'admin.cuti.my-requests' 
    });

    // Pengajuan Saya (Visible to All)
    menu.push({ 
        type: 'link',
        name: 'Pengajuan Saya', 
        route: 'admin.pengajuan.my-requests', 
        icon: DocumentChartBarIcon, 
        current: 'admin.pengajuan.my-requests' 
    });

    // Manajemen Tugas (Visible to All)
    menu.push({ 
        type: 'link',
        name: 'Manajemen Tugas', 
        route: 'admin.tasks.index', 
        icon: BriefcaseIcon, 
        current: 'admin.tasks.*' 
    });

    menu.push({ 
        type: 'link',
        name: 'Kalender Perusahaan', 
        route: 'admin.calendar.index', 
        icon: CalendarDaysIcon, 
        current: 'admin.calendar.index' 
    });

    // IT Support Tickets (Personal View - For Everyone)
    menu.push({ 
        type: 'link',
        name: 'Tiket Saya (IT)', 
        route: 'admin.tickets.my-requests', 
        icon: LifebuoyIcon, 
        current: 'admin.tickets.my-requests' 
    });

    // Persetujuan Cuti
    if (hasRole('Super Admin') || isCutiApprover.value) {
        menu.push({ 
            type: 'link',
            name: 'Persetujuan Cuti', 
            route: 'admin.cuti.approvals', 
            icon: CheckBadgeIcon, 
            current: 'admin.cuti.approvals' 
        });
    }

    // Persetujuan Pengajuan
    if (hasRole('Super Admin') || isPengajuanApprover.value) {
        menu.push({ 
            type: 'link',
            name: 'Persetujuan Pengajuan', 
            route: 'admin.pengajuan.approvals', 
            icon: CheckBadgeIcon, 
            current: 'admin.pengajuan.approvals' 
        });
    }

    // GROUP: MANAJEMEN & APPROVAL (Managers, HR, Admin)
    if (hasRole('Super Admin') || hasRole('Manajer Departemen') || hasRole('HR Manager') || hasRole('HR Staff') || hasRole('Finance Manager')) {
        menu.push({ type: 'heading', label: 'Manajemen & Approval' });
        
        // Manajemen Cuti (Global View for HR)
        if (hasRole('Super Admin') || hasRole('HR Manager') || hasRole('HR Staff')) {
            menu.push({ 
                type: 'link',
                name: 'Manajemen Saldo Cuti', 
                route: 'admin.cuti.management', // Direct to management page
                icon: CalendarDaysIcon, 
                current: 'admin.cuti.management' 
            });
        }

        // Workflow Approval (HR & Admin)
        // Removed permission check to rely on group check or make it explicit
        if (hasRole('Super Admin') || hasRole('HR Manager') || hasRole('HR Staff')) {
            menu.push({ 
                type: 'link',
                name: 'Workflow Approval', 
                route: 'admin.approval-rules.index', 
                icon: ShieldCheckIcon, 
                current: 'admin.approval-rules.*' 
            });
        }
    }

    // GROUP: HR & PAYROLL (ADMIN ONLY)
    if (hasRole('Super Admin') || hasRole('HR Manager') || hasRole('HR Staff')) {
        const hrChildren = [];
        
        // Master Data
        hrChildren.push({ name: 'Data Karyawan', route: 'admin.karyawan.index', icon: UsersIcon, current: 'admin.karyawan.*' });
        hrChildren.push({ name: 'Pinjaman Karyawan', route: 'admin.pinjaman.index', icon: BanknotesIcon, current: 'admin.pinjaman.*' });
        hrChildren.push({ name: 'Hari Libur', route: 'admin.hari-libur.index', icon: CalendarDaysIcon, current: 'admin.hari-libur.*' });
        hrChildren.push({ name: 'Agenda Perusahaan', route: 'admin.company-events.index', icon: CalendarIcon, current: 'admin.company-events.*' });
        
        // Payroll
        hrChildren.push({ 
            name: 'Payroll Processing', 
            route: 'admin.payrolls.index', 
            icon: BanknotesIcon, 
            current: 'admin.payrolls.*'
        });

        menu.push({
            type: 'dropdown',
            name: 'HR & Payroll',
            icon: UserGroupIcon,
            current: 'admin.karyawan.* admin.hari-libur.* admin.payrolls.* admin.company-events.*',
            children: hrChildren
        });
    }



    // GROUP: FINANCE & ACCOUNTING (ADMIN ONLY)
    if (hasRole('Super Admin') || hasRole('Finance Manager') || hasRole('Finance Staff') || hasRole('Staf Finance') || hasRole('Finance')) {
        const financeChildren = [];

        financeChildren.push({ 
            name: 'Semua Pengajuan', 
            route: 'admin.pengajuan.index', 
            icon: DocumentChartBarIcon, 
            current: 'admin.pengajuan.index' 
        });

        financeChildren.push({ 
            name: 'Rencana Pembayaran', 
            route: 'admin.pengajuan.payment-schedule', 
            icon: BanknotesIcon, 
            current: 'admin.pengajuan.payment-schedule' 
        });

        financeChildren.push({ 
            name: 'Periode Anggaran', 
            route: 'admin.periode-anggaran.index', 
            icon: CalendarDaysIcon, 
            current: 'admin.periode-anggaran.*'
        });

        financeChildren.push({ 
            name: 'Anggaran (Budget)', 
            route: 'admin.budget.index', 
            icon: ChartPieIcon, 
            current: 'admin.budget.*'
        });
        
        financeChildren.push({ 
            name: 'Jurnal Umum', 
            route: 'admin.journals.index', 
            icon: BookOpenIcon, 
            current: 'admin.journals.*'
        });
        
        financeChildren.push({ 
            name: 'Laporan Keuangan', 
            route: 'admin.reports.index', 
            icon: ChartBarIcon, 
            current: 'admin.reports.*'
        });

        financeChildren.push({ 
            name: 'Tax Center', 
            route: 'admin.tax-report.index', 
            icon: CalculatorIcon, 
            current: 'admin.tax-report.*'
        });

        financeChildren.push({ 
            name: 'Pengaturan Keuangan', 
            route: 'admin.finance-settings.index', 
            icon: Cog6ToothIcon, 
            current: 'admin.finance-settings.*'
        });

        financeChildren.push({ 
            name: 'Transfer Internal', 
            route: 'admin.internal-transfers.index', 
            icon: ArrowsRightLeftIcon, 
            current: 'admin.internal-transfers.*'
        });

        financeChildren.push({ 
            name: 'Laporan Petty Cash', 
            route: 'admin.petty-cash-report.index', 
            icon: BanknotesIcon, 
            current: 'admin.petty-cash-report.*'
        });

        financeChildren.push({ 
            name: 'Tutup Buku', 
            route: 'admin.period-closings.index', 
            icon: LockClosedIcon, 
            current: 'admin.period-closings.*'
        });

        menu.push({
            type: 'dropdown',
            name: 'Finance & Accounting',
            icon: BanknotesIcon,
            current: 'admin.budget.* admin.journals.* admin.reports.* admin.tax-report.* admin.pengajuan.* admin.pengajuan.payment-schedule admin.periode-anggaran.* admin.internal-transfers.* admin.petty-cash-report.* admin.period-closings.*',
            children: financeChildren
        });
    }

    // GROUP: REVENUE & SALES
    if (hasRole('Super Admin') || hasRole('Finance Manager') || hasRole('Finance Staff') || hasRole('Staf Finance') || hasRole('Finance')) {
        menu.push({
            type: 'dropdown',
            name: 'Revenue & Sales',
            icon: DocumentTextIcon,
            current: 'admin.customers.* admin.invoices.*',
            children: [
                { 
                    name: 'Pelanggan', 
                    route: 'admin.customers.index', 
                    icon: UserGroupIcon, 
                    current: 'admin.customers.*'
                },
                { 
                    name: 'Invoice Penjualan', 
                    route: 'admin.invoices.index', 
                    icon: DocumentTextIcon, 
                    current: 'admin.invoices.*'
                }
            ]
        });
    }

    // GROUP: FIXED ASSETS
    if (hasRole('Super Admin')) {
        menu.push({
            type: 'dropdown',
            name: 'Fixed Assets',
            icon: BuildingOfficeIcon,
            current: 'admin.assets.*',
            children: [
                { 
                    name: 'Aset Tetap', 
                    route: 'admin.assets.index', 
                    icon: BuildingOfficeIcon, 
                    current: 'admin.assets.*'
                }
            ]
        });
    }

    // GROUP: MASTER DATA
    if (hasRole('Super Admin')) {
        menu.push({
            type: 'dropdown',
            name: 'Master Data',
            icon: BuildingStorefrontIcon,
            current: 'admin.vendors.* admin.kas-bank.* admin.akun-gl.* admin.jenis-cuti.* admin.tax-types.* admin.departemen.* admin.program-kerja.* admin.pos-anggaran.*',
            children: [
                { 
                    name: 'Chart of Accounts', 
                    route: 'admin.akun-gl.index', 
                    icon: BookOpenIcon, 
                    current: 'admin.akun-gl.*'
                },
                { 
                    name: 'Master Pajak', 
                    route: 'admin.tax-types.index', 
                    icon: BanknotesIcon, 
                    current: 'admin.tax-types.*'
                },
                { 
                    name: 'Kas & Bank', 
                    route: 'admin.kas-bank.index', 
                    icon: BanknotesIcon, 
                    current: 'admin.kas-bank.*'
                },
                { 
                    name: 'Vendor', 
                    route: 'admin.vendors.index', 
                    icon: BuildingStorefrontIcon, 
                    current: 'admin.vendors.*'
                },
                { 
                    name: 'Departemen', 
                    route: 'admin.departemen.index', 
                    icon: BuildingOfficeIcon, 
                    current: 'admin.departemen.*'
                },
                { 
                    name: 'Program Kerja', 
                    route: 'admin.program-kerja.index', 
                    icon: BriefcaseIcon, 
                    current: 'admin.program-kerja.*'
                },
                { 
                    name: 'Pos Anggaran', 
                    route: 'admin.pos-anggaran.index', 
                    icon: BanknotesIcon, 
                    current: 'admin.pos-anggaran.*'
                },
                { 
                    name: 'Jenis Cuti', 
                    route: 'admin.jenis-cuti.index', 
                    icon: CalendarIcon, 
                    current: 'admin.jenis-cuti.*'
                }
            ]
        });
    }

    // GROUP: IT SUPPORT MANAGEMENT (IT ROLE ONLY)
    if (hasRole('Super Admin') || hasRole('IT Support')) {
        menu.push({ type: 'heading', label: 'IT Management' });
        menu.push({ 
            type: 'link', 
            name: 'Helpdesk Manager', 
            route: 'admin.tickets.index', // Points to Full Index
            icon: LifebuoyIcon, 
            current: 'admin.tickets.index', // Exact match to avoid conflict with my-requests
            badge: openTicketsCount.value // Pass badge value
        });
    }

    // GROUP: SYSTEM SETTINGS
    if (hasRole('Super Admin')) {
        const systemChildren = [];
        
        if (hasPermission('user.manage')) {
            systemChildren.push({ 
                name: 'Users', 
                route: 'admin.users.index', 
                icon: UsersIcon, 
                current: 'admin.users.*'
            });
        }
        
        if (hasPermission('role.manage')) {
            systemChildren.push({ 
                name: 'Roles', 
                route: 'admin.roles.index', 
                icon: UserCircleIcon, 
                current: 'admin.roles.*'
            });
            systemChildren.push({ 
                name: 'Permissions', 
                route: 'admin.permissions.index', 
                icon: ShieldCheckIcon, 
                current: 'admin.permissions.*'
            });
        }
        
        if (hasPermission('user.manage')) {
            systemChildren.push({ 
                name: 'Pengaturan Aplikasi', 
                route: 'admin.settings.index', 
                icon: Cog6ToothIcon, 
                current: 'admin.settings.*'
            });
        }

        // Added System Logs
        systemChildren.push({ 
            name: 'System Logs', 
            route: 'admin.activity-logs.index', 
            icon: ClockIcon, 
            current: 'admin.activity-logs.*'
        });

        if (systemChildren.length > 0) {
            menu.push({
                type: 'dropdown',
                name: 'System Settings',
                icon: Cog6ToothIcon,
                current: 'admin.users.* admin.roles.* admin.permissions.* admin.settings.* admin.activity-logs.*',
                children: systemChildren
            });
        }
    }
    
    // GROUP: UTILITAS
    menu.push({ type: 'heading', label: 'Utilitas' });
    menu.push({ 
        type: 'link', 
        name: 'Alat PDF', 
        route: 'admin.pdf-tools.index', 
        icon: DocumentTextIcon, 
        current: 'admin.pdf-tools.*' 
    });
    menu.push({ 
        type: 'link', 
        name: 'Alat Gambar', 
        route: 'admin.image-tools.index', 
        icon: PhotoIcon, 
        current: 'admin.image-tools.*' 
    });
    menu.push({ 
        type: 'link', 
        name: 'Dokumentasi', 
        icon: BookOpenIcon, 
        route: 'admin.documentation', 
        current: 'admin.documentation' 
    });
    
    return menu;
});
// Auto-open submenu based on active route (Run immediately to prevent re-animation on page load)
watch(sidebarMenu, (menu) => {
    menu.forEach(item => {
        if (item.type === 'dropdown' && isLinkActive(item.current)) {
            openSubmenu.value = item.name;
        }
    });
}, { immediate: true });
// --- AKHIR PERBAIKAN MENU ---

</script>

<template>
    <div>
        <!-- <Toast :message="$page.props.flash.message" :type="$page.props.flash.type" /> Replaced by SweetAlert2 -->
        
        <div class="h-screen flex bg-gray-100 dark:bg-gray-900">
            <!-- Mobile sidebar overlay -->
            <div v-if="mobileSidebarOpen" @click="mobileSidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-50 z-20 transition-opacity md:hidden" aria-hidden="true"></div>

            <!-- Sidebar -->
            <aside :class="[
                        'fixed inset-y-0 left-0 z-30 bg-primary-800 text-gray-300 transform transition-transform duration-300 ease-in-out md:relative md:translate-x-0 flex flex-col',
                        mobileSidebarOpen ? 'translate-x-0 w-64 sm:w-72' : '-translate-x-full w-64 sm:w-72',
                        desktopSidebarOpen ? 'md:w-64' : 'md:w-20'
                    ]">
                
                <div class="relative flex-shrink-0 h-full flex flex-col">
                    <!-- Grid pattern overlay -->
                    <div class="absolute inset-0 pointer-events-none"
                         style="background-image:
                                linear-gradient(to right, rgba(255, 255, 255, 0.07) 1px, transparent 1px),
                                linear-gradient(to bottom, rgba(255, 255, 255, 0.07) 1px, transparent 1px);
                                background-size: 24px 24px;">
                    </div>
                    
                    <div class="relative z-10 flex flex-col h-full">
                        <!-- Sidebar Header -->
                        <div class="h-16 flex items-center justify-between px-4 bg-black/20 flex-shrink-0">
                            <!-- PERBAIKAN: Tautan logo selalu ke admin.dashboard -->
                            <Link :href="route('admin.dashboard')" @click="mobileSidebarOpen = false" class="flex items-center overflow-hidden">
                                <img v-if="appLogo" :src="`/storage/${appLogo}`" alt="App Logo" class="block h-9 w-auto">
                                <ApplicationLogo v-else class="block h-9 w-auto fill-current text-white" />
                                <span v-show="desktopSidebarOpen || mobileSidebarOpen" class="ml-3 text-white text-lg font-semibold truncate">{{ appName }}</span>
                            </Link>
                            <button @click="mobileSidebarOpen = false" class="text-gray-400 hover:text-white md:hidden">
                                <span class="sr-only">Close sidebar</span>
                                <XMarkIcon class="h-6 w-6" />
                            </button>
                        </div>

                        <!-- Navigasi Sidebar -->
                        <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                            <template v-for="(item, index) in sidebarMenu" :key="index">
                                <!-- Tampilkan Heading -->
                                <h3 v-if="item.type === 'heading'" v-show="desktopSidebarOpen || mobileSidebarOpen" class="px-2 pt-4 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    {{ item.label }}
                                </h3>
                                
                                <!-- Tampilkan Link, PERBAIKAN: Cek permission di sini -->
                                <Link v-if="item.type === 'link' && (!item.permission || hasPermission(item.permission))"
                                      :href="item.route ? route(item.route) : '#'"
                                      @click="mobileSidebarOpen = false"
                                      :class="['flex items-center px-2 py-2 text-sm font-medium rounded-md group', isLinkActive(item.current) ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-primary-700 hover:text-white']">
                                    <component :is="item.icon" class="mr-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                                    <span v-show="desktopSidebarOpen || mobileSidebarOpen" class="flex-1">{{ item.name }}</span>
                                    <span v-if="item.badge && (desktopSidebarOpen || mobileSidebarOpen)" class="ml-auto inline-flex items-center justify-center bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded-full min-w-[1.25rem] leading-none shadow-sm">
                                        {{ item.badge }}
                                    </span>
                                </Link>

                                <!-- Tampilkan Dropdown, PERBAIKAN: Cek permission di sini -->
                                <div v-if="item.type === 'dropdown' && (!item.permission || hasPermission(item.permission))">
                                    <button @click="toggleSubmenu(item.name)" :class="['w-full flex items-center px-2 py-2 text-sm font-medium rounded-md group', isLinkActive(item.current) ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-primary-700 hover:text-white']">
                                        <component :is="item.icon" class="mr-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                                        <span class="flex-1 text-left" v-show="desktopSidebarOpen || mobileSidebarOpen">{{ item.name }}</span>
                                        <ChevronDownIcon v-show="desktopSidebarOpen || mobileSidebarOpen" :class="['h-5 w-5 transform transition-transform duration-200', openSubmenu === item.name ? 'rotate-180' : '']" />
                                    </button>
                                    <Transition
                                        @enter="enter"
                                        @after-enter="afterEnter"
                                        @leave="leave"
                                    >
                                        <div v-show="openSubmenu === item.name && (desktopSidebarOpen || mobileSidebarOpen)" class="mt-1 space-y-1 overflow-hidden">
                                            <template v-for="child in item.children" :key="child.name">
                                                <Link v-if="!child.permission || hasPermission(child.permission)"
                                                      :href="child.route ? route(child.route) : '#'"
                                                      @click="mobileSidebarOpen = false"
                                                      :class="['pl-11 pr-2 py-2 text-sm font-medium rounded-md group w-full flex items-center', isLinkActive(child.current) ? 'bg-primary-600 text-white' : 'text-gray-300 hover:bg-primary-700 hover:text-white']">
                                                    <component :is="child.icon" class="mr-3 flex-shrink-0 h-5 w-5" aria-hidden="true" />
                                                    <span>{{ child.name }}</span>
                                                </Link>
                                            </template>
                                        </div>
                                    </Transition>
                                </div>
                            </template>
                        </nav>
                    </div>
                </div>
            </aside>

            <!-- Konten Utama -->
            <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
                <!-- Header Konten -->
                <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-10 flex-shrink-0 border-b border-gray-200 dark:border-gray-700">
                    <div class="mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-between items-center h-16">
                            <!-- Tombol Toggle Sidebar -->
                            <div class="flex items-center flex-1 min-w-0">
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

                                <div class="ml-4 flex-1 min-w-0">
                                    <slot name="header" />
                                </div>
                            </div>

                            <!-- Menu Kanan (Notifikasi & User) -->
                            <div class="flex items-center space-x-3 ml-2 flex-shrink-0">
                                <NavbarNotification />

                                <!-- Dropdown Profil Pengguna -->
                                <div class="relative">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button class="flex items-center text-sm font-medium text-gray-500 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition duration-150 ease-in-out">
                                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900 mr-2">
                                                    <span class="text-sm font-medium leading-none text-primary-700 dark:text-primary-300">{{ userInitial }}</span>
                                                </span>
                                                <div class="hidden md:block">{{ userName }}</div>
                                                <div class="ml-1 hidden md:block">
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
                                                    <button @click="currentTheme = 'indigo'" title="Indigo" class="h-6 w-6 rounded-full bg-indigo-500 focus:outline-none ring-2 ring-offset-2 dark:ring-offset-gray-800" :class="currentTheme === 'indigo' ? 'ring-primary-500' : 'ring-transparent'"></button>
                                                    <button @click="currentTheme = 'blue'" title="Blue" class="h-6 w-6 rounded-full bg-blue-500 focus:outline-none ring-2 ring-offset-2 dark:ring-offset-gray-800" :class="currentTheme === 'blue' ? 'ring-blue-500' : 'ring-transparent'"></button>

                                                    <button @click="currentTheme = 'teal'" title="Teal" class="h-6 w-6 rounded-full bg-teal-500 focus:outline-none ring-2 ring-offset-2 dark:ring-offset-gray-800" :class="currentTheme === 'teal' ? 'ring-teal-500' : 'ring-transparent'"></button>
                                                    <button @click="currentTheme = 'rose'" title="Rose" class="h-6 w-6 rounded-full bg-rose-500 focus:outline-none ring-2 ring-offset-2 dark:ring-offset-gray-800" :class="currentTheme === 'rose' ? 'ring-rose-500' : 'ring-transparent'"></button>
                                                </div>
                                            </div>
                                            <!-- PERBAIKAN: Tautan logout ke admin.logout -->
                                            <button @click="confirmLogout" class="block w-full text-left px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out">
                                                <ArrowLeftStartOnRectangleIcon class="mr-2 h-4 w-4 inline-block text-gray-400" /> Keluar
                                            </button>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Slot Konten Utama -->
                <main>
                    <div class="p-6">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
        
        <!-- Toast Notifikasi -->
        <JobStatusToast :show="showJobToast" :status="jobStatus" :message="jobMessage" :progress="jobProgress" @close="showJobToast = false" />

        <!-- Logout Confirmation Modal -->
        <Modal :show="showLogoutModal" @close="showLogoutModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Konfirmasi Keluar
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin keluar dari aplikasi?
                </p>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showLogoutModal = false">
                        Batal
                    </SecondaryButton>

                    <DangerButton
                        class="ml-3"
                        @click="logout"
                    >
                        Keluar
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </div>
</template>

<!-- Styles (Tidak Berubah) -->
<style scoped>
aside::-webkit-scrollbar { width: 6px; }
aside::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.2); border-radius: 3px; }
aside::-webkit-scrollbar-track { background-color: transparent; }

.flex-1.overflow-y-auto::-webkit-scrollbar { width: 8px; }
.flex-1.overflow-y-auto::-webkit-scrollbar-thumb { background-color: #a0aec0; border-radius: 4px; }
.flex-1.overflow-y-auto::-webkit-scrollbar-track { background-color: transparent; }

.dark .flex-1.overflow-y-auto::-webkit-scrollbar-thumb { background-color: #4a5568; }
</style>