<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    UsersIcon, 
    ClipboardDocumentCheckIcon, 
    CurrencyDollarIcon, 
    BriefcaseIcon,
    ClockIcon,
    UserGroupIcon,
    DocumentTextIcon,
    CalendarIcon,
    CheckBadgeIcon,
    BanknotesIcon,
    ArrowRightIcon,
    PlusCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    dashboardData: Object
});

const { role, stats, hr_stats, finance_stats, my_stats, action_counts, my_active_tasks } = props.dashboardData;
const user = usePage().props.auth.user;

// Date Formatter
const currentDate = computed(() => {
    const date = new Date();
    return date.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

// Greeting based on time
const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 11) return 'Selamat Pagi';
    if (hour < 15) return 'Selamat Siang';
    if (hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <!-- HEADER SECTION -->
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard
            </h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- GREETING SECTION (Moved from Header) -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-6">
                    <div>
                        <h2 class="text-2xl font-bold leading-tight text-gray-800 dark:text-gray-200">
                            {{ greeting }}, {{ user.name }}!
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ currentDate }}</p>
                    </div>
                </div>

                <!-- UNCONFIGURED BANKS NOTIFICATION -->
                <div v-if="dashboardData.unconfigured_banks && dashboardData.unconfigured_banks.length > 0" class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-amber-800">
                                Perhatian: Setup Bank Belum Selesai
                            </h3>
                            <div class="mt-2 text-sm text-amber-700">
                                <p>Sistem mendeteksi bahwa rekening untuk <strong>{{ dashboardData.unconfigured_banks.join(', ') }}</strong> masih menggunakan konfigurasi bawaan (belum diatur). Harap segera atur di menu <Link :href="route('admin.kas-bank.index')" class="font-bold underline hover:text-amber-900">Master Data &rarr; Kas & Bank</Link> agar transaksi bisa berjalan lancar.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QUICK ACTIONS SECTION -->
                <div class="space-y-3 pb-2">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center">
                        <PlusCircleIcon class="w-4 h-4 mr-2 text-blue-500"/> Aksi Cepat
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Link :href="route('admin.pengajuan.create')" class="group relative flex items-center gap-4 px-5 py-4 bg-gradient-to-br from-emerald-50/60 to-white dark:from-emerald-900/20 dark:to-gray-800 rounded-2xl border border-emerald-100/50 dark:border-emerald-800/30 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] dark:hover:shadow-[0_8px_30px_rgb(0,0,0,0.3)] hover:border-emerald-200 dark:hover:border-emerald-700 transition-all duration-300 hover:-translate-y-1 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-emerald-100/50 to-transparent dark:from-emerald-800/20 dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-100/80 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shadow-sm group-hover:shadow-emerald-500/30">
                                <DocumentTextIcon class="w-6 h-6" />
                            </div>
                            <div class="relative">
                                <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-emerald-700 dark:group-hover:text-emerald-400 transition-colors">Pengajuan Dana</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">Klaim & pengeluaran</p>
                            </div>
                        </Link>
                        
                        <Link :href="route('admin.cuti.my-requests')" class="group relative flex items-center gap-4 px-5 py-4 bg-gradient-to-br from-indigo-50/60 to-white dark:from-indigo-900/20 dark:to-gray-800 rounded-2xl border border-indigo-100/50 dark:border-indigo-800/30 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] dark:hover:shadow-[0_8px_30px_rgb(0,0,0,0.3)] hover:border-indigo-200 dark:hover:border-indigo-700 transition-all duration-300 hover:-translate-y-1 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-100/50 to-transparent dark:from-indigo-800/20 dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-100/80 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300 shadow-sm group-hover:shadow-indigo-500/30">
                                <CalendarIcon class="w-6 h-6" />
                            </div>
                            <div class="relative">
                                <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-indigo-700 dark:group-hover:text-indigo-400 transition-colors">Buat Cuti Baru</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">Tahunan & izin</p>
                            </div>
                        </Link>

                        <Link :href="route('admin.tasks.index')" class="group relative flex items-center gap-4 px-5 py-4 bg-gradient-to-br from-amber-50/60 to-white dark:from-amber-900/20 dark:to-gray-800 rounded-2xl border border-amber-100/50 dark:border-amber-800/30 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] dark:hover:shadow-[0_8px_30px_rgb(0,0,0,0.3)] hover:border-amber-200 dark:hover:border-amber-700 transition-all duration-300 hover:-translate-y-1 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-amber-100/50 to-transparent dark:from-amber-800/20 dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative flex items-center justify-center w-12 h-12 rounded-xl bg-amber-100/80 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shadow-sm group-hover:shadow-amber-500/30">
                                <BriefcaseIcon class="w-6 h-6" />
                            </div>
                            <div class="relative">
                                <h4 class="font-bold text-gray-800 dark:text-gray-100 group-hover:text-amber-700 dark:group-hover:text-amber-500 transition-colors">Buat Tugas</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-medium">Catat pekerjaan baru</p>
                            </div>
                        </Link>
                    </div>
                </div>


                <!-- 1. PRIORITY SECTION: APPROVALS & TASKS (TOP) -->
                <!-- Show if there are counts or if user has roles that manage these -->
                <div v-if="action_counts && (action_counts.leave_approvals !== undefined || action_counts.expense_approvals !== undefined || action_counts.active_tasks_count !== undefined)" class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                        <CheckBadgeIcon class="w-5 h-5 mr-2 text-indigo-500"/> Menunggu Tindakan
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Card: Persetujuan Cuti -->
                        <div v-if="action_counts.leave_approvals !== undefined" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 relative overflow-hidden group hover:shadow-md transition-all">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <CalendarIcon class="w-24 h-24 text-indigo-500" />
                            </div>
                            <div class="relative z-5">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-3 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                        <CalendarIcon class="w-8 h-8" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Permintaan Cuti</p>
                                        <h4 class="text-3xl font-bold text-gray-900 dark:text-white">{{ action_counts.leave_approvals || 0 }}</h4>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mt-6">
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded dark:bg-gray-700 dark:text-gray-300">
                                        {{ action_counts.leave_approvals > 0 ? 'Perlu ditinjau segera' : 'Semua aman terkendali' }}
                                    </span>
                                    <Link :href="route('admin.cuti.approvals')" class="inline-flex items-center text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition">
                                        Proses Cuti <ArrowRightIcon class="w-4 h-4 ml-1"/>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Persetujuan Pengajuan -->
                        <div v-if="action_counts.expense_approvals !== undefined" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 relative overflow-hidden group hover:shadow-md transition-all">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <BanknotesIcon class="w-24 h-24 text-emerald-500" />
                            </div>
                            <div class="relative z-5">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <DocumentTextIcon class="w-8 h-8" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pengajuan Dana & Payment</p>
                                        <h4 class="text-3xl font-bold text-gray-900 dark:text-white">{{ action_counts.expense_approvals || 0 }}</h4>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mt-6">
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded dark:bg-gray-700 dark:text-gray-300">
                                        {{ action_counts.expense_approvals > 0 ? 'Menunggu persetujuan' : 'Tidak ada antrian' }}
                                    </span>
                                    <Link :href="route('admin.pengajuan.approvals')" class="inline-flex items-center text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition">
                                        Proses Pengajuan <ArrowRightIcon class="w-4 h-4 ml-1"/>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Tugas Aktif Saya -->
                        <div v-if="action_counts.active_tasks_count !== undefined" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 relative overflow-hidden group hover:shadow-md transition-all">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <BriefcaseIcon class="w-24 h-24 text-amber-500" />
                            </div>
                            <div class="relative z-5">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="p-3 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                        <CheckBadgeIcon class="w-8 h-8" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tugas Aktif Saya</p>
                                        <div class="flex items-baseline gap-2">
                                            <h4 class="text-3xl font-bold text-gray-900 dark:text-white">{{ action_counts.active_tasks_count || 0 }}</h4>
                                            <span v-if="action_counts.overdue_tasks_count > 0" class="text-xs font-semibold text-red-600 bg-red-100 px-2 py-0.5 rounded-full">{{ action_counts.overdue_tasks_count }} Terlewat</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Task List -->
                                <div v-if="my_active_tasks && my_active_tasks.length > 0" class="mt-4 space-y-2 max-h-32 overflow-y-auto pr-2 custom-scrollbar">
                                    <div v-for="task in my_active_tasks" :key="task.id" class="flex justify-between items-center text-sm p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 transition">
                                        <div class="truncate mr-2 flex-grow">
                                            <span class="font-medium text-gray-800 dark:text-gray-200 block truncate">{{ task.title }}</span>
                                            <span class="text-xs text-gray-500">{{ new Date(task.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</span>
                                        </div>
                                        <div class="w-2 h-2 rounded-full flex-shrink-0" :class="{
                                            'bg-red-500': new Date(task.due_date) < new Date(),
                                            'bg-amber-400': new Date(task.due_date) >= new Date()
                                        }"></div>
                                    </div>
                                </div>
                                <div v-else class="mt-4 py-4 text-center text-sm text-gray-500 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    Tidak ada tugas aktif
                                </div>

                                <div class="flex items-center justify-between mt-4">
                                    <Link :href="route('admin.tasks.index')" class="inline-flex items-center text-sm font-semibold text-amber-600 hover:text-amber-700 transition">
                                        Lihat Papan Tugas <ArrowRightIcon class="w-4 h-4 ml-1"/>
                                    </Link>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 2. SYSTEM OVERVIEW (STATS) -->
                <div v-if="(stats && Object.keys(stats).length > 0) || hr_stats || finance_stats" class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                        <ClipboardDocumentCheckIcon class="w-5 h-5 mr-2 text-gray-400"/> Overview & Statistik
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        
                        <!-- Global Stats (Admin) -->
                        <template v-if="stats">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Karyawan</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total_employees }}</p>
                                </div>
                                <div class="p-3 bg-blue-50 text-blue-600 rounded-lg dark:bg-blue-900/30 dark:text-blue-400">
                                    <UsersIcon class="w-6 h-6" />
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total User</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total_users }}</p>
                                </div>
                                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg dark:bg-indigo-900/30 dark:text-indigo-400">
                                    <UserGroupIcon class="w-6 h-6" />
                                </div>
                            </div>
                        </template>

                        <!-- HR Stats -->
                        <template v-if="hr_stats">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Sedang Cuti</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ hr_stats.employees_on_leave }} <span class="text-sm font-normal text-gray-500">Orang</span></p>
                                </div>
                                <div class="p-3 bg-purple-50 text-purple-600 rounded-lg dark:bg-purple-900/30 dark:text-purple-400">
                                    <BriefcaseIcon class="w-6 h-6" />
                                </div>
                            </div>
                             <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Karyawan Baru</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">+{{ hr_stats.new_hires_month }}</p>
                                </div>
                                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg dark:bg-emerald-900/30 dark:text-emerald-400">
                                    <UserGroupIcon class="w-6 h-6" />
                                </div>
                            </div>
                        </template>

                        <!-- Finance Stats -->
                        <template v-if="finance_stats">
                             <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Invoice Unpaid</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ finance_stats.unpaid_invoices }}</p>
                                </div>
                                <div class="p-3 bg-rose-50 text-rose-600 rounded-lg dark:bg-rose-900/30 dark:text-rose-400">
                                    <DocumentTextIcon class="w-6 h-6" />
                                </div>
                            </div>
                             <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Need Settlement</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ finance_stats.need_settlement }}</p>
                                </div>
                                <div class="p-3 bg-amber-50 text-amber-600 rounded-lg dark:bg-amber-900/30 dark:text-amber-400">
                                    <CurrencyDollarIcon class="w-6 h-6" />
                                </div>
                            </div>
                        </template>

                    </div>
                </div>

                <!-- 3. PERSONAL STATS (ALL USERS) -->
                <div v-if="my_stats" class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                        <UsersIcon class="w-5 h-5 mr-2 text-gray-400"/> Informasi Saya
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Card: Sisa Cuti -->
                        <div class="bg-gradient-to-br from-white to-indigo-50 border border-indigo-100 rounded-2xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
                            <!-- Background Icon Overlay -->
                            <BriefcaseIcon class="absolute -bottom-6 -right-6 w-40 h-40 text-indigo-500 opacity-10 transform -rotate-12 group-hover:scale-110 group-hover:rotate-0 transition-all duration-500" />
                            
                            <div class="relative z-10">
                                <p class="text-indigo-600 text-sm font-medium mb-1">Sisa Cuti Tahunan</p>
                                <h4 class="text-4xl font-bold text-gray-800 dark:text-gray-100">{{ my_stats.leave_balance }} <span class="text-lg font-normal text-gray-500">Hari</span></h4>
                                <Link :href="route('admin.cuti.my-requests')" class="mt-6 inline-flex items-center text-xs text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-lg transition font-medium">
                                    Riwayat Cuti <ArrowRightIcon class="w-3 h-3 ml-1"/>
                                </Link>
                            </div>
                        </div>

                        <!-- Card: Pengajuan Pending -->
                        <div class="bg-gradient-to-br from-white to-orange-50 border border-orange-100 rounded-2xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-all">
                            <!-- Background Icon Overlay -->
                            <ClockIcon class="absolute -bottom-6 -right-6 w-40 h-40 text-orange-500 opacity-10 transform -rotate-12 group-hover:scale-110 group-hover:rotate-0 transition-all duration-500" />

                            <div class="relative z-10">
                                <p class="text-orange-600 text-sm font-medium mb-1">Pengajuan Saya (Pending)</p>
                                <h4 class="text-4xl font-bold text-gray-800 dark:text-gray-100">{{ my_stats.my_pending_requests }} <span class="text-lg font-normal text-gray-500">Item</span></h4>
                                <Link :href="route('admin.pengajuan.my-requests')" class="mt-6 inline-flex items-center text-xs text-orange-600 bg-orange-50 hover:bg-orange-100 px-4 py-2 rounded-lg transition font-medium">
                                    Lihat Status <ArrowRightIcon class="w-3 h-3 ml-1"/>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
