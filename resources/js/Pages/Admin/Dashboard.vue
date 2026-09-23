<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
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
    PlusIcon,
    ArrowDownLeftIcon,
    ArrowUpRightIcon,
    MapPinIcon,
    CameraIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    dashboardData: Object
});

const { role, stats, hr_stats, finance_stats, my_stats, action_counts, my_active_tasks } = props.dashboardData;
const user = usePage().props.auth.user;
const showQuickActions = ref(false);
const locationName = ref('');
const isLoadingLocation = ref(true);

onMounted(() => {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(async (position) => {
            try {
                const { latitude, longitude } = position.coords;
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
                const data = await response.json();
                
                if (data && data.address) {
                    const kelurahan = data.address.village || data.address.suburb || data.address.neighbourhood || data.address.city_district || data.address.town || data.address.city;
                    const provinsi = data.address.state || data.address.region || data.address.county;
                    locationName.value = kelurahan && provinsi ? `${kelurahan}, ${provinsi}` : (kelurahan || provinsi || 'Lokasi Ditemukan');
                }
            } catch (error) {
                locationName.value = 'Lokasi Tidak Diketahui';
            } finally {
                isLoadingLocation.value = false;
            }
        }, () => {
            fetch('https://ipapi.co/json/')
                .then(res => res.json())
                .then(data => {
                    if (data.city && data.region) {
                        locationName.value = `${data.city}, ${data.region}`;
                    } else {
                        locationName.value = 'Jakarta, Indonesia';
                    }
                })
                .catch(() => locationName.value = 'Jakarta, Indonesia')
                .finally(() => isLoadingLocation.value = false);
        }, { maximumAge: 60000, timeout: 5000, enableHighAccuracy: false });
    } else {
        locationName.value = 'Jakarta, Indonesia';
        isLoadingLocation.value = false;
    }

    const updateTime = () => {
        const date = new Date();
        currentTime.value = date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    };
    updateTime();
    setInterval(updateTime, 60000);
});

const currentTime = ref('');

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
                
                <!-- GREETING & DATE & LOCATION SECTION -->
                <div class="flex flex-col gap-2 md:gap-4 mb-4">
                    <h2 class="text-xl md:text-3xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                        {{ user.name }}
                    </h2>
                    
                    <div class="flex flex-col gap-1 md:gap-2">
                        <div class="flex items-center justify-between gap-2 md:gap-4">
                            <span class="text-gray-600 dark:text-gray-400 font-medium text-xs md:text-lg">{{ currentDate }}</span>
                            
                            <div v-if="isLoadingLocation" class="bg-gray-200 animate-pulse px-3 py-1.5 md:px-5 md:py-2.5 rounded-full shadow-sm w-28 md:w-48 h-7 md:h-10"></div>
                            <div v-else class="bg-[#5B7B6B] text-white px-3 py-1.5 md:px-5 md:py-2.5 rounded-full flex items-center gap-1.5 text-[10px] md:text-sm shadow-sm font-medium transition-all duration-300">
                                <MapPinIcon class="w-3.5 h-3.5 md:w-4 md:h-4 flex-shrink-0" /> <span class="truncate max-w-[150px] sm:max-w-[200px] md:max-w-xs">{{ locationName }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-900 dark:text-gray-100 font-black text-2xl md:text-3xl tracking-tight">{{ currentTime }}</span>
                        </div>
                    </div>
                </div>

                <!-- 0. ABSENSI WIDGET -->
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-6 mb-6">
                    
                    <!-- Check In Card -->
                    <template v-if="!dashboardData.my_attendance_today || !dashboardData.my_attendance_today.waktu_masuk">
                        <Link :href="route('admin.absensi.clock')" class="bg-white dark:bg-gray-800 rounded-2xl md:rounded-[2rem] p-4 md:p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-center items-center h-32 md:h-40 hover:bg-gray-50 dark:hover:bg-gray-750 transition group cursor-pointer">
                            <div class="bg-[#5B7B6B] text-white w-10 h-10 md:w-14 md:h-14 rounded-full flex items-center justify-center mb-2 md:mb-3 shadow-md group-hover:scale-110 transition-transform">
                                <ArrowDownLeftIcon class="w-5 h-5 md:w-6 md:h-6" stroke-width="2.5" />
                            </div>
                            <span class="font-bold text-gray-800 dark:text-gray-200 text-sm md:text-lg">Check In</span>
                        </Link>
                    </template>
                    <template v-else>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl md:rounded-[2rem] p-4 md:p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between h-32 md:h-40">
                            <div class="flex items-center gap-2 md:gap-4">
                                <div class="bg-[#5B7B6B] text-white w-8 h-8 md:w-12 md:h-12 flex-shrink-0 rounded-full flex items-center justify-center">
                                    <ArrowDownLeftIcon class="w-4 h-4 md:w-6 md:h-6" stroke-width="2.5" />
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-gray-100 text-sm md:text-lg leading-tight">Check In</div>
                                    <div class="text-[10px] md:text-sm text-gray-400">Recorded</div>
                                </div>
                            </div>
                            <div class="text-3xl md:text-[2.5rem] leading-none font-black text-gray-900 dark:text-gray-100">
                                {{ new Date(dashboardData.my_attendance_today.waktu_masuk).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}) }}
                            </div>
                        </div>
                    </template>

                    <!-- Check Out Card -->
                    <template v-if="!dashboardData.my_attendance_today || !dashboardData.my_attendance_today.waktu_masuk">
                        <!-- Not yet checked in, so check out is disabled -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl md:rounded-[2rem] p-4 md:p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between h-32 md:h-40 opacity-70">
                            <div class="flex items-center gap-2 md:gap-4">
                                <div class="bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 w-8 h-8 md:w-12 md:h-12 flex-shrink-0 rounded-full flex items-center justify-center">
                                    <ArrowUpRightIcon class="w-4 h-4 md:w-6 md:h-6" stroke-width="2.5" />
                                </div>
                                <div>
                                    <div class="font-bold text-gray-400 dark:text-gray-500 text-sm md:text-lg leading-tight">Check Out</div>
                                    <div class="text-[10px] md:text-sm text-gray-300 dark:text-gray-600">Not Yet</div>
                                </div>
                            </div>
                            <div class="text-3xl md:text-[2.5rem] leading-none font-black text-gray-300 dark:text-gray-600">
                                --:--
                            </div>
                        </div>
                    </template>
                    <template v-else-if="dashboardData.my_attendance_today && !dashboardData.my_attendance_today.waktu_keluar">
                        <!-- Checked in, need to check out -->
                        <Link :href="route('admin.absensi.clock')" class="bg-white dark:bg-gray-800 rounded-2xl md:rounded-[2rem] p-4 md:p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-center items-center h-32 md:h-40 hover:bg-gray-50 dark:hover:bg-gray-750 transition group cursor-pointer border-2 !border-gray-200">
                            <div class="bg-gray-200 text-gray-600 w-10 h-10 md:w-14 md:h-14 rounded-full flex items-center justify-center mb-2 md:mb-3 shadow-md group-hover:scale-110 transition-transform">
                                <ArrowUpRightIcon class="w-5 h-5 md:w-6 md:h-6" stroke-width="2.5" />
                            </div>
                            <span class="font-bold text-gray-800 dark:text-gray-200 text-sm md:text-lg">Check Out</span>
                        </Link>
                    </template>
                    <template v-else>
                        <!-- Checked out -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl md:rounded-[2rem] p-4 md:p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between h-32 md:h-40">
                            <div class="flex items-center gap-2 md:gap-4">
                                <div class="bg-gray-200 text-gray-600 w-8 h-8 md:w-12 md:h-12 flex-shrink-0 rounded-full flex items-center justify-center">
                                    <ArrowUpRightIcon class="w-4 h-4 md:w-6 md:h-6" stroke-width="2.5" />
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-gray-100 text-sm md:text-lg leading-tight">Check Out</div>
                                    <div class="text-[10px] md:text-sm text-gray-400">Recorded</div>
                                </div>
                            </div>
                            <div class="text-3xl md:text-[2.5rem] leading-none font-black text-gray-900 dark:text-gray-100">
                                {{ new Date(dashboardData.my_attendance_today.waktu_keluar).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}) }}
                            </div>
                        </div>
                    </template>

                </div>

                <!-- 1. PRIORITY SECTION: APPROVALS & TASKS (TOP) -->
                <!-- Show if there are counts or if user has roles that manage these -->
                <div v-if="action_counts && (action_counts.leave_approvals !== undefined || action_counts.expense_approvals !== undefined || action_counts.active_tasks_count !== undefined)" class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                        <CheckBadgeIcon class="w-5 h-5 mr-2 text-indigo-500"/> Menunggu Tindakan
                    </h3>
                    <!-- Mobile View: Simple Cards -->
                    <div class="grid md:hidden grid-cols-2 gap-4">
                        <!-- Card: Persetujuan Cuti -->
                        <Link v-if="action_counts.leave_approvals !== undefined" :href="route('admin.cuti.approvals')" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex flex-col justify-between h-32 hover:shadow-md transition-shadow group">
                            <div>
                                <CalendarIcon class="w-6 h-6 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Persetujuan Cuti</p>
                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ action_counts.leave_approvals || 0 }}</h4>
                            </div>
                        </Link>

                        <!-- Card: Persetujuan Pengajuan -->
                        <Link v-if="action_counts.expense_approvals !== undefined" :href="route('admin.pengajuan.approvals')" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex flex-col justify-between h-32 hover:shadow-md transition-shadow group">
                            <div>
                                <BanknotesIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pengajuan Dana</p>
                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ action_counts.expense_approvals || 0 }}</h4>
                            </div>
                        </Link>

                        <!-- Card: Tugas Aktif Saya -->
                        <Link v-if="action_counts.active_tasks_count !== undefined" :href="route('admin.tasks.index')" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex flex-col justify-between h-32 hover:shadow-md transition-shadow group">
                            <div>
                                <CheckBadgeIcon class="w-6 h-6 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Tugas Aktif</p>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ action_counts.active_tasks_count || 0 }}</h4>
                                    <span v-if="action_counts.overdue_tasks_count > 0" class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-bold">{{ action_counts.overdue_tasks_count }}</span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Desktop View: Detailed Cards -->
                    <div class="hidden md:grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                        
                        <!-- Card: Persetujuan Cuti -->
                        <div v-if="action_counts.leave_approvals !== undefined" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 md:p-6 relative overflow-hidden group hover:shadow-md transition-all">
                            <div class="hidden md:block absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <CalendarIcon class="w-24 h-24 text-indigo-500" />
                            </div>
                            <div class="relative z-5">
                                <div class="flex items-center gap-3 md:gap-4 mb-3 md:mb-4">
                                    <div class="p-2 md:p-3 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                        <CalendarIcon class="w-6 h-6 md:w-8 md:h-8" />
                                    </div>
                                    <div>
                                        <p class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Permintaan Cuti</p>
                                        <h4 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ action_counts.leave_approvals || 0 }}</h4>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-between mt-4 md:mt-6 gap-2">
                                    <span class="text-[10px] md:text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded dark:bg-gray-700 dark:text-gray-300">
                                        {{ action_counts.leave_approvals > 0 ? 'Perlu ditinjau segera' : 'Semua aman terkendali' }}
                                    </span>
                                    <Link :href="route('admin.cuti.approvals')" class="inline-flex items-center text-[10px] md:text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition">
                                        Proses Cuti <ArrowRightIcon class="w-3 h-3 md:w-4 md:h-4 ml-1"/>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Persetujuan Pengajuan -->
                        <div v-if="action_counts.expense_approvals !== undefined" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 md:p-6 relative overflow-hidden group hover:shadow-md transition-all">
                            <div class="hidden md:block absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <BanknotesIcon class="w-24 h-24 text-emerald-500" />
                            </div>
                            <div class="relative z-5">
                                <div class="flex items-center gap-3 md:gap-4 mb-3 md:mb-4">
                                    <div class="p-2 md:p-3 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <DocumentTextIcon class="w-6 h-6 md:w-8 md:h-8" />
                                    </div>
                                    <div>
                                        <p class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Pengajuan Dana & Payment</p>
                                        <h4 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ action_counts.expense_approvals || 0 }}</h4>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center justify-between mt-4 md:mt-6 gap-2">
                                    <span class="text-[10px] md:text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded dark:bg-gray-700 dark:text-gray-300">
                                        {{ action_counts.expense_approvals > 0 ? 'Menunggu persetujuan' : 'Tidak ada antrian' }}
                                    </span>
                                    <Link :href="route('admin.pengajuan.approvals')" class="inline-flex items-center text-[10px] md:text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition">
                                        Proses Pengajuan <ArrowRightIcon class="w-3 h-3 md:w-4 md:h-4 ml-1"/>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Tugas Aktif Saya -->
                        <div v-if="action_counts.active_tasks_count !== undefined" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 md:p-6 relative overflow-hidden group hover:shadow-md transition-all">
                            <div class="hidden md:block absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <BriefcaseIcon class="w-24 h-24 text-amber-500" />
                            </div>
                            <div class="relative z-5">
                                <div class="flex items-center gap-3 md:gap-4 mb-3 md:mb-4">
                                    <div class="p-2 md:p-3 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                        <CheckBadgeIcon class="w-6 h-6 md:w-8 md:h-8" />
                                    </div>
                                    <div>
                                        <p class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Tugas Aktif Saya</p>
                                        <div class="flex items-baseline gap-2">
                                            <h4 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ action_counts.active_tasks_count || 0 }}</h4>
                                            <span v-if="action_counts.overdue_tasks_count > 0" class="text-[10px] md:text-xs font-semibold text-red-600 bg-red-100 px-2 py-0.5 rounded-full">{{ action_counts.overdue_tasks_count }} Terlewat</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Task List -->
                                <div v-if="my_active_tasks && my_active_tasks.length > 0" class="mt-3 md:mt-4 space-y-1.5 md:space-y-2 max-h-32 overflow-y-auto pr-2 custom-scrollbar">
                                    <div v-for="task in my_active_tasks" :key="task.id" class="flex justify-between items-center text-xs md:text-sm p-2 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 transition">
                                        <div class="truncate mr-2 flex-grow">
                                            <span class="font-medium text-gray-800 dark:text-gray-200 block truncate">{{ task.title }}</span>
                                            <span class="text-[10px] md:text-xs text-gray-500">{{ new Date(task.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</span>
                                        </div>
                                        <div class="w-2 h-2 rounded-full flex-shrink-0" :class="{
                                            'bg-red-500': new Date(task.due_date) < new Date(),
                                            'bg-amber-400': new Date(task.due_date) >= new Date()
                                        }"></div>
                                    </div>
                                </div>
                                <div v-else class="mt-3 md:mt-4 py-3 md:py-4 text-center text-xs md:text-sm text-gray-500 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    Tidak ada tugas aktif
                                </div>

                                <div class="flex items-center justify-between mt-4">
                                    <Link :href="route('admin.tasks.index')" class="inline-flex items-center text-[10px] md:text-sm font-semibold text-amber-600 hover:text-amber-700 transition">
                                        Lihat Papan Tugas <ArrowRightIcon class="w-3 h-3 md:w-4 md:h-4 ml-1"/>
                                    </Link>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 3. PERSONAL STATS (ALL USERS) -->
                <div v-if="my_stats" class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                        <UsersIcon class="w-5 h-5 mr-2 text-gray-400"/> Informasi Saya
                    </h3>
                    <!-- Mobile View: Simple Cards -->
                    <div class="grid md:hidden grid-cols-2 gap-4">
                        <!-- Card: Sisa Cuti -->
                        <Link :href="route('admin.cuti.my-requests')" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex flex-col justify-between h-32 hover:shadow-md transition-shadow group">
                            <div>
                                <BriefcaseIcon class="w-6 h-6 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Sisa Cuti</p>
                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ my_stats.leave_balance }}</h4>
                            </div>
                        </Link>

                        <!-- Card: Pengajuan Pending -->
                        <Link :href="route('admin.pengajuan.my-requests')" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex flex-col justify-between h-32 hover:shadow-md transition-shadow group">
                            <div>
                                <ClockIcon class="w-6 h-6 text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pengajuan Pending</p>
                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ my_stats.my_pending_requests }}</h4>
                            </div>
                        </Link>
                    </div>

                    <!-- Desktop View: Detailed Cards -->
                    <div class="hidden md:grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                        <!-- Card: Sisa Cuti -->
                        <div class="bg-gradient-to-br from-white to-indigo-50 border border-indigo-100 rounded-2xl shadow-sm p-4 md:p-6 relative overflow-hidden group hover:shadow-md transition-all">
                            <!-- Background Icon Overlay -->
                            <BriefcaseIcon class="hidden md:block absolute -bottom-6 -right-6 w-40 h-40 text-indigo-500 opacity-10 transform -rotate-12 group-hover:scale-110 group-hover:rotate-0 transition-all duration-500" />
                            
                            <div class="relative z-10">
                                <p class="text-indigo-600 text-xs md:text-sm font-medium mb-1">Sisa Cuti Tahunan</p>
                                <h4 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-gray-100">{{ my_stats.leave_balance }} <span class="text-base md:text-lg font-normal text-gray-500">Hari</span></h4>
                                <Link :href="route('admin.cuti.my-requests')" class="mt-4 md:mt-6 inline-flex items-center text-[10px] md:text-xs text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 md:px-4 md:py-2 rounded-lg transition font-medium">
                                    Riwayat Cuti <ArrowRightIcon class="w-3 h-3 ml-1"/>
                                </Link>
                            </div>
                        </div>

                        <!-- Card: Pengajuan Pending -->
                        <div class="bg-gradient-to-br from-white to-orange-50 border border-orange-100 rounded-2xl shadow-sm p-4 md:p-6 relative overflow-hidden group hover:shadow-md transition-all">
                            <!-- Background Icon Overlay -->
                            <ClockIcon class="hidden md:block absolute -bottom-6 -right-6 w-40 h-40 text-orange-500 opacity-10 transform -rotate-12 group-hover:scale-110 group-hover:rotate-0 transition-all duration-500" />

                            <div class="relative z-10">
                                <p class="text-orange-600 text-xs md:text-sm font-medium mb-1">Pengajuan Saya (Pending)</p>
                                <h4 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-gray-100">{{ my_stats.my_pending_requests }} <span class="text-base md:text-lg font-normal text-gray-500">Item</span></h4>
                                <Link :href="route('admin.pengajuan.my-requests')" class="mt-4 md:mt-6 inline-flex items-center text-[10px] md:text-xs text-orange-600 bg-orange-50 hover:bg-orange-100 px-3 py-1.5 md:px-4 md:py-2 rounded-lg transition font-medium">
                                    Lihat Status <ArrowRightIcon class="w-3 h-3 ml-1"/>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. SYSTEM OVERVIEW (STATS) -->
                <div v-if="(stats && Object.keys(stats).length > 0) || hr_stats || finance_stats" class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 flex items-center">
                        <ClipboardDocumentCheckIcon class="w-5 h-5 mr-2 text-gray-400"/> Overview & Statistik
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
                        
                        <!-- Global Stats (Admin) -->
                        <template v-if="stats">
                            <div class="bg-white dark:bg-gray-800 p-3 md:p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-[10px] md:text-xs font-bold text-gray-500 uppercase tracking-wider">Total Karyawan</p>
                                    <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total_employees }}</p>
                                </div>
                                <div class="p-2 md:p-3 bg-blue-50 text-blue-600 rounded-lg dark:bg-blue-900/30 dark:text-blue-400">
                                    <UsersIcon class="w-5 h-5 md:w-6 md:h-6" />
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-3 md:p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-[10px] md:text-xs font-bold text-gray-500 uppercase tracking-wider">Total User</p>
                                    <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total_users }}</p>
                                </div>
                                <div class="p-2 md:p-3 bg-indigo-50 text-indigo-600 rounded-lg dark:bg-indigo-900/30 dark:text-indigo-400">
                                    <UserGroupIcon class="w-5 h-5 md:w-6 md:h-6" />
                                </div>
                            </div>
                        </template>

                        <!-- HR Stats -->
                        <template v-if="hr_stats">
                            <div class="bg-white dark:bg-gray-800 p-3 md:p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-[10px] md:text-xs font-bold text-gray-500 uppercase tracking-wider">Sedang Cuti</p>
                                    <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ hr_stats.employees_on_leave }} <span class="text-[10px] md:text-sm font-normal text-gray-500">Orang</span></p>
                                </div>
                                <div class="p-2 md:p-3 bg-purple-50 text-purple-600 rounded-lg dark:bg-purple-900/30 dark:text-purple-400">
                                    <BriefcaseIcon class="w-5 h-5 md:w-6 md:h-6" />
                                </div>
                            </div>
                             <div class="bg-white dark:bg-gray-800 p-3 md:p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-[10px] md:text-xs font-bold text-gray-500 uppercase tracking-wider">Karyawan Baru</p>
                                    <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mt-1">+{{ hr_stats.new_hires_month }}</p>
                                </div>
                                <div class="p-2 md:p-3 bg-emerald-50 text-emerald-600 rounded-lg dark:bg-emerald-900/30 dark:text-emerald-400">
                                    <UserGroupIcon class="w-5 h-5 md:w-6 md:h-6" />
                                </div>
                            </div>
                        </template>

                        <!-- Finance Stats -->
                        <template v-if="finance_stats">
                             <div class="bg-white dark:bg-gray-800 p-3 md:p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-[10px] md:text-xs font-bold text-gray-500 uppercase tracking-wider">Invoice Unpaid</p>
                                    <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ finance_stats.unpaid_invoices }}</p>
                                </div>
                                <div class="p-2 md:p-3 bg-rose-50 text-rose-600 rounded-lg dark:bg-rose-900/30 dark:text-rose-400">
                                    <DocumentTextIcon class="w-5 h-5 md:w-6 md:h-6" />
                                </div>
                            </div>
                             <div class="bg-white dark:bg-gray-800 p-3 md:p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between transition hover:shadow-md">
                                <div>
                                    <p class="text-[10px] md:text-xs font-bold text-gray-500 uppercase tracking-wider">Need Settlement</p>
                                    <p class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ finance_stats.need_settlement }}</p>
                                </div>
                                <div class="p-2 md:p-3 bg-amber-50 text-amber-600 rounded-lg dark:bg-amber-900/30 dark:text-amber-400">
                                    <CurrencyDollarIcon class="w-5 h-5 md:w-6 md:h-6" />
                                </div>
                            </div>
                        </template>

                    </div>
                </div>

            </div>
        </div>

        <!-- FLOATING QUICK ACTIONS -->
        <div class="fixed bottom-8 right-8 z-50 flex flex-col items-end space-y-4">
            <!-- Action Menu (pops up above the button) -->
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-4 scale-95"
            >
                <div v-show="showQuickActions" class="flex flex-col gap-2 bg-white dark:bg-gray-800 p-3 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 w-64 origin-bottom-right">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-2 py-1 mb-1 border-b border-gray-100 dark:border-gray-700">Aksi Cepat</h3>
                    
                    <Link :href="route('admin.absensi.clock')" class="flex items-center gap-3 p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-xl group transition-colors">
                        <div class="p-2 rounded-lg bg-blue-100/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 group-hover:bg-blue-500 group-hover:text-white transition-colors">
                            <ClockIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Live Absensi</h4>
                            <p class="text-[10px] text-gray-500">Clock In / Out</p>
                        </div>
                    </Link>
                    
                    <Link :href="route('admin.pengajuan.create')" class="flex items-center gap-3 p-2 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-xl group transition-colors">
                        <div class="p-2 rounded-lg bg-emerald-100/80 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                            <DocumentTextIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Pengajuan Dana</h4>
                            <p class="text-[10px] text-gray-500">Klaim & pengeluaran</p>
                        </div>
                    </Link>

                    <Link :href="route('admin.cuti.my-requests')" class="flex items-center gap-3 p-2 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl group transition-colors">
                        <div class="p-2 rounded-lg bg-indigo-100/80 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
                            <CalendarIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Buat Cuti Baru</h4>
                            <p class="text-[10px] text-gray-500">Tahunan & izin</p>
                        </div>
                    </Link>

                    <Link :href="route('admin.tasks.index')" class="flex items-center gap-3 p-2 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-xl group transition-colors">
                        <div class="p-2 rounded-lg bg-amber-100/80 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                            <BriefcaseIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Buat Tugas</h4>
                            <p class="text-[10px] text-gray-500">Catat pekerjaan baru</p>
                        </div>
                    </Link>
                </div>
            </transition>

            <!-- FAB Button -->
            <button @click="showQuickActions = !showQuickActions" class="flex items-center justify-center w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform" :class="{'rotate-45 bg-gray-600 hover:bg-gray-700': showQuickActions, 'scale-110': showQuickActions}">
                <PlusIcon class="w-7 h-7 transition-transform" />
            </button>
        </div>
    </AuthenticatedLayout>
</template>
