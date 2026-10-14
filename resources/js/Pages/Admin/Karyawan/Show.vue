<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { 
    UserIcon, BriefcaseIcon, MapPinIcon, CalendarIcon, 
    BanknotesIcon, PencilSquareIcon, ArrowLeftIcon, ChevronLeftIcon, ChevronRightIcon,
    IdentificationIcon, CheckBadgeIcon, AtSymbolIcon, BuildingOfficeIcon, CameraIcon,
    UserPlusIcon, CheckCircleIcon, XCircleIcon, ClockIcon, DocumentTextIcon,
    ShieldCheckIcon, EllipsisVerticalIcon, EnvelopeIcon,
    ClipboardDocumentCheckIcon, ExclamationTriangleIcon, TrashIcon
} from '@heroicons/vue/24/outline';
import CareerHistoryModal from '@/Components/CareerHistoryModal.vue';
import EditProfileModal from '@/Components/Employee/EditProfileModal.vue';
import EditEmploymentModal from '@/Components/Employee/EditEmploymentModal.vue';
import EditFinancialModal from '@/Components/Employee/EditFinancialModal.vue';

const props = defineProps({
    karyawan: Object,
    leaveBalances: Array,
    departemens: Array,
    lokasi_kantors: Array,
    lastClockIn: String,
    navigation: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
};

// Tabs: matching modern ERP reference layout
// Options: 'contract' (default), 'profile', 'payroll', 'time', 'history', 'tasks'
const activeTab = ref('contract');

// Dropdown Action Menu
const showActionMenu = ref(false);
const toggleActionMenu = () => {
    showActionMenu.value = !showActionMenu.value;
};

const handleClickOutside = (e) => {
    if (!e.target.closest('#employee-action-menu-btn') && !e.target.closest('#employee-action-menu-dropdown')) {
        showActionMenu.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    window.removeEventListener('click', handleClickOutside);
});

// Edit Modals
const showEditProfile = ref(false);
const showEditEmployment = ref(false);
const showEditFinancial = ref(false);
const showCareerHistoryModal = ref(false);

const openEditProfile = () => { showActionMenu.value = false; showEditProfile.value = true; };
const openEditEmployment = () => { showActionMenu.value = false; showEditEmployment.value = true; };
const openEditFinancial = () => { showActionMenu.value = false; showEditFinancial.value = true; };
const closeCareerHistoryModal = () => { showCareerHistoryModal.value = false; };
const refreshData = () => { router.reload(); };

// Photo Upload
const photoInput = ref(null);
const triggerPhotoUpload = () => {
    photoInput.value.click();
};
const handlePhotoUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    router.post(route('admin.karyawan.update', props.karyawan.id), {
        _method: 'PUT',
        foto: file
    }, {
        forceFormData: true,
        preserveScroll: true,
    });
};

// Terminate Employee
const handleTerminate = () => {
    showActionMenu.value = false;
    if (confirm(`Apakah Anda yakin ingin menonaktifkan karyawan "${props.karyawan.nama_lengkap}"? Karyawan ini akan diubah statusnya menjadi non-aktif.`)) {
        router.delete(route('admin.karyawan.destroy', props.karyawan.id));
    }
};

// Extend Contract
const handleExtendContract = () => {
    showCareerHistoryModal.value = true;
};

// Send Email
const sendEmail = () => {
    if (props.karyawan.user?.email) {
        window.location.href = `mailto:${props.karyawan.user.email}`;
    } else {
        alert('Karyawan belum memiliki email akun terdaftar.');
    }
};

// Contract Duration Calculations
const contractInfo = computed(() => {
    const startStr = props.karyawan.tgl_bergabung;
    const endStr = props.karyawan.tanggal_berakhir_kontrak;

    if (!endStr) {
        return {
            isPermanent: true,
            daysRemaining: null,
            progressPercent: 100,
            textLabel: 'Karyawan Tetap (Permanen)',
            statusColor: 'emerald'
        };
    }

    const start = new Date(startStr || Date.now());
    const end = new Date(endStr);
    const today = new Date();

    const totalDays = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)));
    const elapsedDays = Math.ceil((today - start) / (1000 * 60 * 60 * 24));
    const remaining = Math.ceil((end - today) / (1000 * 60 * 60 * 24));

    const percent = Math.min(100, Math.max(0, Math.round((elapsedDays / totalDays) * 100)));

    return {
        isPermanent: false,
        daysRemaining: remaining,
        progressPercent: percent,
        textLabel: remaining > 0 ? `${remaining} Hari Tersisa` : 'Masa Kontrak Berakhir',
        statusColor: remaining > 30 ? 'emerald' : (remaining > 0 ? 'amber' : 'rose')
    };
});

// Relative format for Last Clocked In
const formatLastClockIn = (val) => {
    if (!val) return 'Belum absen hari ini';
    try {
        const d = new Date(val);
        const timeStr = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        const dateStr = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        return `${dateStr}, ${timeStr} WIB`;
    } catch (_) {
        return val;
    }
};
</script>

<template>
    <Head :title="karyawan.nama_lengkap + ' - Detail Karyawan'" />

    <AuthenticatedLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- ═════════════════════════════════════════════════════════════════ -->
            <!-- 1. TOP PROFILE HEADER BAR (Reference Design)                    -->
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-4 md:p-5 shadow-sm mb-6 transition-colors">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    
                    <!-- Left: Back Button, Avatar, Name & Metadata -->
                    <div class="flex items-center space-x-3.5 flex-1 min-w-0">
                        <!-- Back Button Circle -->
                        <Link 
                            :href="route('admin.karyawan.index')" 
                            class="w-10 h-10 shrink-0 rounded-full border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors shadow-2xs"
                            title="Kembali ke Daftar Karyawan"
                        >
                            <ArrowLeftIcon class="w-5 h-5" />
                        </Link>

                        <!-- Avatar with Photo Upload overlay -->
                        <div class="relative shrink-0 group cursor-pointer" @click="triggerPhotoUpload" title="Klik untuk ganti foto">
                            <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-gray-100 dark:border-gray-700 bg-gray-100 dark:bg-gray-700 shadow-xs">
                                <img :src="karyawan.foto_url" :alt="karyawan.nama_lengkap" class="w-full h-full object-cover" />
                            </div>
                            <div class="absolute inset-0 bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                <CameraIcon class="w-5 h-5" />
                            </div>
                            <input type="file" ref="photoInput" class="hidden" accept="image/*" @change="handlePhotoUpload" />
                        </div>

                        <!-- Name & Badge -->
                        <div class="min-w-0 pr-3">
                            <div class="flex items-center space-x-2">
                                <h1 class="text-base md:text-lg font-bold text-gray-900 dark:text-white truncate">
                                    {{ karyawan.nama_lengkap }}
                                </h1>
                                <!-- Status Badge -->
                                <span 
                                    v-if="!karyawan.deleted_at"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                                <span 
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Non-Aktif
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                {{ karyawan.jabatan }} • {{ karyawan.departemen?.nama_departemen || 'Semua Departemen' }}
                            </p>
                        </div>

                        <!-- Divider -->
                        <div class="hidden xl:block h-10 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>

                        <!-- Metadata Badges -->
                        <div class="hidden xl:flex items-center space-x-6 text-xs">
                            <div>
                                <p class="text-gray-400 dark:text-gray-400 font-medium">Last Clocked In</p>
                                <p class="text-gray-800 dark:text-gray-200 font-semibold mt-0.5">{{ formatLastClockIn(lastClockIn) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-400 dark:text-gray-400 font-medium">Email / Akun</p>
                                <p class="text-gray-800 dark:text-gray-200 font-semibold mt-0.5 truncate max-w-[170px]" :title="karyawan.user?.email || '-'">
                                    {{ karyawan.user?.email || 'Belum Terhubung' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-400 dark:text-gray-400 font-medium">Employee ID</p>
                                <p class="text-gray-800 dark:text-gray-200 font-semibold mt-0.5">#{{ karyawan.nomor_induk_karyawan }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Navigation Pagination, More Menu & Action Buttons -->
                    <div class="flex items-center space-x-2 shrink-0 self-end lg:self-center">
                        <!-- Pagination Buttons: < 1 of 32 > -->
                        <div v-if="navigation" class="flex items-center bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-xl p-1 text-xs text-gray-600 dark:text-gray-300 mr-1">
                            <Link 
                                v-if="navigation.prev_id" 
                                :href="route('admin.karyawan.show', navigation.prev_id)"
                                class="p-1.5 rounded-lg hover:bg-white dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-colors"
                                title="Karyawan Sebelumnya"
                            >
                                <ChevronLeftIcon class="w-4 h-4" />
                            </Link>
                            <span v-else class="p-1.5 opacity-30 cursor-not-allowed">
                                <ChevronLeftIcon class="w-4 h-4" />
                            </span>

                            <span class="px-2 font-semibold">
                                {{ navigation.current }} of {{ navigation.total }}
                            </span>

                            <Link 
                                v-if="navigation.next_id" 
                                :href="route('admin.karyawan.show', navigation.next_id)"
                                class="p-1.5 rounded-lg hover:bg-white dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-colors"
                                title="Karyawan Berikutnya"
                            >
                                <ChevronRightIcon class="w-4 h-4" />
                            </Link>
                            <span v-else class="p-1.5 opacity-30 cursor-not-allowed">
                                <ChevronRightIcon class="w-4 h-4" />
                            </span>
                        </div>

                        <!-- Dropdown Menu (...) -->
                        <div class="relative">
                            <button 
                                id="employee-action-menu-btn"
                                @click.stop="toggleActionMenu"
                                class="w-9 h-9 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-2xs"
                                title="Aksi Tambahan"
                            >
                                <EllipsisVerticalIcon class="w-5 h-5" />
                            </button>

                            <!-- Dropdown Content -->
                            <div 
                                id="employee-action-menu-dropdown"
                                v-if="showActionMenu"
                                class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 py-2 z-50 animate-in fade-in zoom-in-95 duration-150"
                            >
                                <button 
                                    @click="openEditProfile"
                                    class="w-full text-left px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/60 flex items-center"
                                >
                                    <PencilSquareIcon class="w-4 h-4 mr-2.5 text-gray-400" />
                                    Edit Informasi Personal
                                </button>
                                <button 
                                    @click="openEditEmployment"
                                    class="w-full text-left px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/60 flex items-center"
                                >
                                    <BriefcaseIcon class="w-4 h-4 mr-2.5 text-gray-400" />
                                    Edit Kepegawaian & Jabatan
                                </button>
                                <button 
                                    @click="openEditFinancial"
                                    class="w-full text-left px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/60 flex items-center"
                                >
                                    <BanknotesIcon class="w-4 h-4 mr-2.5 text-gray-400" />
                                    Edit Gaji & Rekening
                                </button>
                                <button 
                                    @click="handleExtendContract"
                                    class="w-full text-left px-4 py-2.5 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/60 flex items-center"
                                >
                                    <DocumentTextIcon class="w-4 h-4 mr-2.5 text-gray-400" />
                                    Perpanjang Kontrak / Riwayat
                                </button>

                                <div v-if="!karyawan.user" class="border-t border-gray-100 dark:border-gray-700/60 my-1"></div>
                                <Link 
                                    v-if="!karyawan.user"
                                    :href="route('admin.karyawan.generate-user', karyawan.id)" 
                                    method="post" 
                                    as="button" 
                                    class="w-full text-left px-4 py-2.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 flex items-center"
                                >
                                    <UserPlusIcon class="w-4 h-4 mr-2.5" />
                                    Buat Akun Login ESS
                                </Link>

                                <div class="border-t border-gray-100 dark:border-gray-700/60 my-1"></div>
                                <button 
                                    v-if="!karyawan.deleted_at"
                                    @click="handleTerminate"
                                    class="w-full text-left px-4 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center"
                                >
                                    <TrashIcon class="w-4 h-4 mr-2.5" />
                                    Nonaktifkan Karyawan (PHK/Resign)
                                </button>
                            </div>
                        </div>

                        <!-- Send Email Primary Button -->
                        <button 
                            @click="sendEmail"
                            class="inline-flex items-center px-4 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition-all shadow-xs"
                            :title="karyawan.user?.email ? `Kirim email ke ${karyawan.user.email}` : 'Kirim Email'"
                        >
                            <EnvelopeIcon class="w-4 h-4 mr-2" />
                            Send Email
                        </button>
                    </div>

                </div>
            </div>

            <!-- Non-Aktif Alert Banner (if terminated) -->
            <div v-if="karyawan.deleted_at" class="mb-6 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 rounded-2xl p-4 flex items-center space-x-3 text-red-800 dark:text-red-300">
                <ExclamationTriangleIcon class="w-5 h-5 shrink-0 text-red-600 dark:text-red-400" />
                <p class="text-xs md:text-sm font-medium">
                    <strong>Status: Karyawan Non-Aktif (Terminated).</strong> Akses sistem telah dicabut dan data tidak lagi muncul pada operasional harian.
                </p>
            </div>

            <!-- ═════════════════════════════════════════════════════════════════ -->
            <!-- 2. MODERN HORIZONTAL TAB NAVIGATION BAR                         -->
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <div class="border-b border-gray-200 dark:border-gray-700/70 mb-6 overflow-x-auto">
                <nav class="flex space-x-1 md:space-x-4 min-w-max pb-1" aria-label="Tabs">
                    <!-- Personal Information -->
                    <button 
                        @click="activeTab = 'profile'"
                        :class="[
                            activeTab === 'profile'
                                ? 'text-gray-900 dark:text-white border-b-2 border-slate-900 dark:border-indigo-500 font-bold'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 font-medium',
                            'inline-flex items-center py-2.5 px-3 text-xs md:text-sm transition-colors'
                        ]"
                    >
                        <UserIcon class="w-4 h-4 mr-2" />
                        Personal Information
                    </button>

                    <!-- Contract (Default in Screenshot) -->
                    <button 
                        @click="activeTab = 'contract'"
                        :class="[
                            activeTab === 'contract'
                                ? 'text-gray-900 dark:text-white border-b-2 border-slate-900 dark:border-indigo-500 font-bold'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 font-medium',
                            'inline-flex items-center py-2.5 px-3 text-xs md:text-sm transition-colors'
                        ]"
                    >
                        <DocumentTextIcon class="w-4 h-4 mr-2" />
                        Contract
                    </button>

                    <!-- Payroll -->
                    <button 
                        @click="activeTab = 'payroll'"
                        :class="[
                            activeTab === 'payroll'
                                ? 'text-gray-900 dark:text-white border-b-2 border-slate-900 dark:border-indigo-500 font-bold'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 font-medium',
                            'inline-flex items-center py-2.5 px-3 text-xs md:text-sm transition-colors'
                        ]"
                    >
                        <BanknotesIcon class="w-4 h-4 mr-2" />
                        Payroll
                    </button>

                    <!-- Time Management (Cuti / Absensi) -->
                    <button 
                        @click="activeTab = 'time'"
                        :class="[
                            activeTab === 'time'
                                ? 'text-gray-900 dark:text-white border-b-2 border-slate-900 dark:border-indigo-500 font-bold'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 font-medium',
                            'inline-flex items-center py-2.5 px-3 text-xs md:text-sm transition-colors'
                        ]"
                    >
                        <ClockIcon class="w-4 h-4 mr-2" />
                        Time Management
                    </button>

                    <!-- Career History -->
                    <button 
                        @click="activeTab = 'history'"
                        :class="[
                            activeTab === 'history'
                                ? 'text-gray-900 dark:text-white border-b-2 border-slate-900 dark:border-indigo-500 font-bold'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 font-medium',
                            'inline-flex items-center py-2.5 px-3 text-xs md:text-sm transition-colors'
                        ]"
                    >
                        <BriefcaseIcon class="w-4 h-4 mr-2" />
                        Career History
                        <span v-if="karyawan.riwayat_karir?.length" class="ml-2 px-1.5 py-0.5 text-[10px] rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-bold">
                            {{ karyawan.riwayat_karir.length }}
                        </span>
                    </button>

                    <!-- Tasks -->
                    <button 
                        @click="activeTab = 'tasks'"
                        :class="[
                            activeTab === 'tasks'
                                ? 'text-gray-900 dark:text-white border-b-2 border-slate-900 dark:border-indigo-500 font-bold'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 font-medium',
                            'inline-flex items-center py-2.5 px-3 text-xs md:text-sm transition-colors'
                        ]"
                    >
                        <ClipboardDocumentCheckIcon class="w-4 h-4 mr-2" />
                        Tasks
                        <span v-if="karyawan.tasks_assigned?.length" class="ml-2 px-1.5 py-0.5 text-[10px] rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-bold">
                            {{ karyawan.tasks_assigned.length }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- ═════════════════════════════════════════════════════════════════ -->
            <!-- 3. TAB 1: CONTRACT (Matches the User's Screenshot Attachment)   -->
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'contract'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Left Column (lg:col-span-8): Duration, Position, Compensation, Clauses -->
                <div class="lg:col-span-8 space-y-6">

                    <!-- Card 1: Contract Duration -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-5 md:p-6 shadow-2xs">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                            <div class="flex items-center space-x-2.5">
                                <DocumentTextIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                                <h2 class="font-bold text-base md:text-lg text-gray-900 dark:text-white">Contract Duration</h2>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button 
                                    v-if="!karyawan.deleted_at"
                                    @click="handleTerminate"
                                    class="px-3.5 py-1.5 border border-gray-300 dark:border-gray-600 hover:border-red-400 hover:text-red-600 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold transition-colors"
                                >
                                    Terminate
                                </button>
                                <button 
                                    @click="handleExtendContract"
                                    class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-2xs"
                                >
                                    Extend Contract
                                </button>
                            </div>
                        </div>

                        <!-- Dates Row -->
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div class="flex items-center space-x-4 md:space-x-8">
                                <div>
                                    <p class="text-xs text-gray-400 font-medium">Contract Start</p>
                                    <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">
                                        {{ formatDate(karyawan.tgl_bergabung) }}
                                    </p>
                                </div>
                                <span class="text-gray-300 dark:text-gray-600 text-lg">→</span>
                                <div>
                                    <p class="text-xs text-gray-400 font-medium">Contract End</p>
                                    <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">
                                        {{ formatDate(karyawan.tanggal_berakhir_kontrak) || 'Karyawan Tetap' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Remaining Days with Clock Icon -->
                            <div class="flex items-center text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <ClockIcon class="w-4 h-4 mr-1.5 text-gray-400" />
                                <span>{{ contractInfo.textLabel }}</span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                            <div 
                                class="h-2 rounded-full transition-all duration-700"
                                :class="[
                                    contractInfo.statusColor === 'emerald' ? 'bg-emerald-500' : (contractInfo.statusColor === 'amber' ? 'bg-amber-500' : 'bg-rose-500')
                                ]"
                                :style="{ width: `${contractInfo.progressPercent}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Card 2: Contract Position Details -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-5 md:p-6 shadow-2xs">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center space-x-2.5">
                                <BriefcaseIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                                <h2 class="font-bold text-base md:text-lg text-gray-900 dark:text-white">Contract Position Details</h2>
                            </div>
                            <button 
                                @click="openEditEmployment"
                                class="inline-flex items-center px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <PencilSquareIcon class="w-3.5 h-3.5 mr-1" />
                                Edit
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-5 gap-x-8">
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Job Role</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">{{ karyawan.jabatan }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Job Level</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">
                                    {{ karyawan.status_karyawan === 'Tetap' ? 'Senior / Tetap' : 'Staff Level' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Expected Work hours per week</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">40 hours</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Supervisor</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">
                                    {{ karyawan.departemen?.kepala?.nama_lengkap || 'Manajer Departemen' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Employment Status</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">{{ karyawan.status_karyawan }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Department</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">{{ karyawan.departemen?.nama_departemen || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Compensation & Benefit -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-5 md:p-6 shadow-2xs">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center space-x-2.5">
                                <ShieldCheckIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                                <h2 class="font-bold text-base md:text-lg text-gray-900 dark:text-white">Compensation & Benefit</h2>
                            </div>
                            <button 
                                @click="openEditFinancial"
                                class="inline-flex items-center px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <PencilSquareIcon class="w-3.5 h-3.5 mr-1" />
                                Edit
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-5 gap-x-8">
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Payment Type</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">Monthly Salary</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Basic Salary</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">
                                    {{ formatCurrency(karyawan.gaji_pokok || 0) }} / month
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Status PTKP</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">{{ karyawan.status_ptkp || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Allowed to Submit Expenses</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">Yes (Reimbursement & ESS)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Contract Clauses -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-5 md:p-6 shadow-2xs">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center space-x-2.5">
                                <ExclamationTriangleIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                                <h2 class="font-bold text-base md:text-lg text-gray-900 dark:text-white">Contract Clauses</h2>
                            </div>
                            <button 
                                @click="openEditEmployment"
                                class="inline-flex items-center px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <PencilSquareIcon class="w-3.5 h-3.5 mr-1" />
                                Edit
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-5 gap-x-8">
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Period of Notice</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">1 Month (30 Days)</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Probation Period</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">3 Months (Selesai)</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Work Location</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">
                                    {{ karyawan.lokasi_kantor?.nama_kantor || 'Kantor Pusat / Bebas' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Attendance Policy</p>
                                <p class="text-sm md:text-base font-bold text-gray-900 dark:text-white mt-1">
                                    {{ karyawan.is_strict_location ? 'STRICT (Wajib di radius kantor)' : 'ROAMING (Bebas di semua cabang)' }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column (lg:col-span-4): Working Scope & Contract Details -->
                <div class="lg:col-span-4 space-y-6">

                    <!-- Working Scope Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-5 md:p-6 shadow-2xs">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <CheckBadgeIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                                <h2 class="font-bold text-base text-gray-900 dark:text-white">Working Scope</h2>
                            </div>
                            <button 
                                @click="openEditEmployment"
                                class="inline-flex items-center px-2.5 py-1 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <PencilSquareIcon class="w-3.5 h-3.5 mr-1" />
                                Edit
                            </button>
                        </div>

                        <!-- Scope pill items (Reference style) -->
                        <div class="space-y-3">
                            <div class="p-3.5 bg-gray-50/70 dark:bg-gray-900/40 border border-gray-200/70 dark:border-gray-700 rounded-xl text-xs md:text-sm text-gray-700 dark:text-gray-300 leading-relaxed hover:border-indigo-300 dark:hover:border-indigo-800 transition-colors">
                                Menjalankan tugas dan tanggung jawab profesional sesuai jabatan <strong>{{ karyawan.jabatan }}</strong>.
                            </div>
                            <div class="p-3.5 bg-gray-50/70 dark:bg-gray-900/40 border border-gray-200/70 dark:border-gray-700 rounded-xl text-xs md:text-sm text-gray-700 dark:text-gray-300 leading-relaxed hover:border-indigo-300 dark:hover:border-indigo-800 transition-colors">
                                Berkoordinasi secara aktif dengan tim kerja di bawah naungan <strong>{{ karyawan.departemen?.nama_departemen || 'departemen terkait' }}</strong>.
                            </div>
                            <div class="p-3.5 bg-gray-50/70 dark:bg-gray-900/40 border border-gray-200/70 dark:border-gray-700 rounded-xl text-xs md:text-sm text-gray-700 dark:text-gray-300 leading-relaxed hover:border-indigo-300 dark:hover:border-indigo-800 transition-colors">
                                Mematuhi SOP operasional, kode etik, dan peraturan internal PT Persija Jaya Jakarta.
                            </div>
                            <div class="p-3.5 bg-gray-50/70 dark:bg-gray-900/40 border border-gray-200/70 dark:border-gray-700 rounded-xl text-xs md:text-sm text-gray-700 dark:text-gray-300 leading-relaxed hover:border-indigo-300 dark:hover:border-indigo-800 transition-colors">
                                Melaporkan perkembangan tugas dan performa kerja secara berkala kepada atasan langsung.
                            </div>
                            <div class="p-3.5 bg-gray-50/70 dark:bg-gray-900/40 border border-gray-200/70 dark:border-gray-700 rounded-xl text-xs md:text-sm text-gray-700 dark:text-gray-300 leading-relaxed hover:border-indigo-300 dark:hover:border-indigo-800 transition-colors">
                                Mengelola kerahasiaan data serta aset perusahaan dengan penuh integritas dan tanggung jawab.
                            </div>
                        </div>
                    </div>

                    <!-- Contract Details Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-5 md:p-6 shadow-2xs">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <DocumentTextIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                                <h2 class="font-bold text-base text-gray-900 dark:text-white">Contract Details</h2>
                            </div>
                            <button 
                                @click="openEditEmployment"
                                class="inline-flex items-center px-2.5 py-1 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <PencilSquareIcon class="w-3.5 h-3.5 mr-1" />
                                Edit
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Contract ID / NIK</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">#{{ karyawan.nomor_induk_karyawan }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Contractor Country Tax of Residence</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">Indonesia</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Effective Date of Tax Residency</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">{{ formatDate(karyawan.tgl_bergabung) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Bank Rekening Utama</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                                    {{ karyawan.primary_bank ? `${karyawan.primary_bank.nama_bank} - ${karyawan.primary_bank.nomor_rekening}` : 'Belum Ditambahkan' }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- ═════════════════════════════════════════════════════════════════ -->
            <!-- 4. TAB 2: PERSONAL INFORMATION (BIODATA)                         -->
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'profile'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-8 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-6 shadow-2xs space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-base md:text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <UserIcon class="w-5 h-5 mr-2 text-indigo-500" />
                            Data Pribadi
                        </h2>
                        <button @click="openEditProfile" class="inline-flex items-center px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <PencilSquareIcon class="w-3.5 h-3.5 mr-1" /> Edit
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Nama Lengkap</p>
                            <p class="text-base font-bold text-gray-900 dark:text-white mt-1">{{ karyawan.nama_lengkap }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">NIK / NIP</p>
                            <p class="text-base font-bold text-gray-900 dark:text-white mt-1">{{ karyawan.nomor_induk_karyawan }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Jenis Kelamin</p>
                            <p class="text-base font-bold text-gray-900 dark:text-white mt-1">
                                {{ karyawan.jenis_kelamin === 'L' ? 'Laki-laki' : (karyawan.jenis_kelamin === 'P' ? 'Perempuan' : '-') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Tempat, Tanggal Lahir</p>
                            <p class="text-base font-bold text-gray-900 dark:text-white mt-1">
                                {{ karyawan.tempat_lahir || '-' }}, {{ formatDate(karyawan.tgl_lahir) }}
                            </p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs text-gray-400 font-medium">Alamat Domisili</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1 leading-relaxed">
                                {{ karyawan.alamat || 'Belum ada data alamat' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-6 shadow-2xs space-y-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center">
                        <AtSymbolIcon class="w-5 h-5 mr-2 text-indigo-500" />
                        Akun Login Sistem
                    </h2>

                    <div v-if="karyawan.user" class="space-y-3">
                        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                            <div class="flex items-center text-xs font-bold text-emerald-800 dark:text-emerald-300">
                                <CheckCircleIcon class="w-4 h-4 mr-1.5 text-emerald-600" />
                                Terhubung ke Akun User
                            </div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mt-1 truncate">
                                {{ karyawan.user.email }}
                            </p>
                        </div>
                    </div>
                    <div v-else class="text-center py-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Karyawan belum memiliki akun login ESS.</p>
                        <Link 
                            :href="route('admin.karyawan.generate-user', karyawan.id)" 
                            method="post" 
                            as="button" 
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs"
                        >
                            <UserPlusIcon class="w-4 h-4 mr-1.5" />
                            Buat Akun Login
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════════ -->
            <!-- 5. TAB 3: PAYROLL & FINANCIAL                                   -->
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'payroll'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-7 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-6 shadow-2xs space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-base md:text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <BanknotesIcon class="w-5 h-5 mr-2 text-emerald-600" />
                            Finansial & Gaji Pokok
                        </h2>
                        <button @click="openEditFinancial" class="inline-flex items-center px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <PencilSquareIcon class="w-3.5 h-3.5 mr-1" /> Edit Finansial
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/50 p-5 rounded-2xl">
                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Gaji Pokok</p>
                            <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                                {{ formatCurrency(karyawan.gaji_pokok || 0) }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">per bulan</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 p-5 rounded-2xl">
                            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status PTKP</p>
                            <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                                {{ karyawan.status_ptkp || '-' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">Penghasilan Tidak Kena Pajak</p>
                        </div>
                    </div>
                </div>

                <!-- Bank Card Style (Right) -->
                <div class="lg:col-span-5 relative overflow-hidden rounded-3xl border border-gray-800 bg-gradient-to-br from-slate-900 via-gray-900 to-black p-7 text-white shadow-xl">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Rekening Pencairan</p>
                            <h4 class="text-xl font-bold tracking-wider mt-1">
                                {{ karyawan.primary_bank ? karyawan.primary_bank.nama_bank : 'TIDAK ADA DATA' }}
                            </h4>
                        </div>
                        <div class="w-9 h-6 bg-amber-400/80 rounded-sm"></div>
                    </div>

                    <div v-if="karyawan.primary_bank">
                        <p class="font-mono text-2xl tracking-widest my-4 opacity-95">
                            {{ karyawan.primary_bank.nomor_rekening.replace(/(.{4})/g, '$1 ').trim() }}
                        </p>
                        <div class="flex justify-between items-end mt-6">
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Atas Nama</p>
                                <p class="font-bold text-base tracking-wide uppercase">{{ karyawan.primary_bank.atas_nama_rekening }}</p>
                            </div>
                            <div class="w-10 h-7 flex relative">
                                <div class="w-7 h-7 rounded-full bg-red-500/80 absolute right-3 mix-blend-screen"></div>
                                <div class="w-7 h-7 rounded-full bg-yellow-500/80 absolute right-0 mix-blend-screen"></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-gray-400 text-xs italic">
                        Belum ada data rekening bank. Silakan klik tombol Edit Finansial.
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════════ -->
            <!-- 6. TAB 4: TIME MANAGEMENT (SALDO CUTI & ABSENSI)                -->
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'time'" class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Saldo Kuota Cuti Karyawan</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Periode Tahun {{ new Date().getFullYear() }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div 
                        v-for="balance in leaveBalances" 
                        :key="balance.id"
                        class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700 p-5 rounded-2xl shadow-2xs hover:shadow-md transition-shadow"
                    >
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                                {{ balance.jenis_cuti?.nama_cuti }}
                            </h3>
                            <ClockIcon class="w-4 h-4 text-gray-400" />
                        </div>

                        <div v-if="!balance.jenis_cuti?.is_unlimited" class="space-y-3">
                            <div class="flex items-baseline">
                                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ balance.saldo_akhir }}</span>
                                <span class="ml-1.5 text-xs text-gray-400 font-semibold">hari tersisa</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                <div 
                                    class="h-2 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500"
                                    :style="{ width: `${Math.min(100, Math.max(0, (balance.saldo_terpakai / (balance.saldo_awal || 1)) * 100))}%` }"
                                ></div>
                            </div>
                            <div class="flex justify-between text-[11px] text-gray-500 dark:text-gray-400">
                                <span>Terpakai: {{ balance.saldo_terpakai }} hari</span>
                                <span>Total: {{ balance.saldo_awal }} hari</span>
                            </div>
                        </div>

                        <div v-else class="space-y-3">
                            <div class="flex items-baseline">
                                <span class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ balance.saldo_terpakai }}</span>
                                <span class="ml-1.5 text-xs text-gray-400 font-semibold">hari terpakai</span>
                            </div>
                            <div class="w-full bg-emerald-100 dark:bg-emerald-950/40 rounded-full h-2">
                                <div class="h-2 rounded-full bg-emerald-500" style="width: 100%"></div>
                            </div>
                            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">Kuota: Unlimited (Tidak Dibatasi)</p>
                        </div>
                    </div>

                    <div v-if="!leaveBalances || leaveBalances.length === 0" class="col-span-full py-12 text-center text-gray-400 text-xs italic bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
                        Belum ada data saldo cuti untuk karyawan ini.
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════════ -->
            <!-- 7. TAB 5: CAREER HISTORY (TIMELINE)                             -->
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'history'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-6 shadow-2xs space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-base md:text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <BriefcaseIcon class="w-5 h-5 mr-2 text-indigo-500" />
                        Riwayat Karir, Mutasi & Kontrak
                    </h2>
                    <button @click="showCareerHistoryModal = true" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Tambah Riwayat
                    </button>
                </div>

                <div class="relative border-l-2 border-indigo-100 dark:border-indigo-900/30 ml-4 space-y-6 pb-4">
                    <div v-if="!karyawan.riwayat_karir || karyawan.riwayat_karir.length === 0" class="pl-6 text-gray-400 italic text-xs">
                        Belum ada riwayat karir tercatat.
                    </div>

                    <div v-else v-for="histori in karyawan.riwayat_karir" :key="histori.id" class="relative pl-6">
                        <div class="absolute -left-[9px] mt-1.5 w-4 h-4 rounded-full bg-indigo-500 ring-4 ring-white dark:ring-gray-800 shadow-xs"></div>
                        <div class="bg-gray-50 dark:bg-gray-900/40 p-4 rounded-xl border border-gray-200/70 dark:border-gray-700 space-y-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ histori.tipe_peristiwa }}</h3>
                                    <p class="text-xs text-gray-400 mt-0.5">Efektif: {{ formatDate(histori.tanggal_efektif) }}</p>
                                </div>
                                <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300">
                                    {{ histori.status_karyawan }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-xs pt-2">
                                <div>
                                    <span class="text-gray-400 font-medium">Jabatan:</span>
                                    <p class="font-semibold text-gray-800 dark:text-gray-200">{{ histori.jabatan }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-400 font-medium">Departemen:</span>
                                    <p class="font-semibold text-gray-800 dark:text-gray-200">{{ histori.departemen?.nama_departemen || '-' }}</p>
                                </div>
                            </div>
                            <p v-if="histori.catatan" class="text-xs italic text-gray-500 dark:text-gray-400 pt-1">
                                "{{ histori.catatan }}"
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═════════════════════════════════════════════════════════════════ -->
            <!-- 8. TAB 6: TASKS                                                 -->
            <!-- ═════════════════════════════════════════════════════════════════ -->
            <div v-else-if="activeTab === 'tasks'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700/70 p-6 shadow-2xs space-y-4">
                <h2 class="text-base md:text-lg font-bold text-gray-900 dark:text-white flex items-center pb-4 border-b border-gray-100 dark:border-gray-700">
                    <ClipboardDocumentCheckIcon class="w-5 h-5 mr-2 text-indigo-500" />
                    Tugas Yang Ditugaskan
                </h2>

                <div v-if="karyawan.tasks_assigned && karyawan.tasks_assigned.length > 0" class="space-y-3">
                    <div 
                        v-for="task in karyawan.tasks_assigned" 
                        :key="task.id"
                        class="p-4 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-xl flex items-center justify-between"
                    >
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ task.title }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Tenggat: {{ formatDate(task.due_date) }}</p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                            {{ task.status }}
                        </span>
                    </div>
                </div>
                <div v-else class="py-12 text-center text-gray-400 text-xs italic">
                    Belum ada tugas yang ditugaskan ke karyawan ini.
                </div>
            </div>

        </div>

        <!-- ═════════════════════════════════════════════════════════════════ -->
        <!-- 9. MODALS                                                       -->
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <EditProfileModal 
            :show="showEditProfile" 
            :employee="karyawan" 
            @close="showEditProfile = false"
            @saved="refreshData"
        />

        <EditEmploymentModal 
            :show="showEditEmployment" 
            :employee="karyawan" 
            :departemens="departemens"
            :lokasiKantors="lokasi_kantors"
            @close="showEditEmployment = false"
            @saved="refreshData"
        />

        <EditFinancialModal 
            :show="showEditFinancial" 
            :employee="karyawan" 
            @close="showEditFinancial = false"
            @saved="refreshData"
        />

        <CareerHistoryModal
            :show="showCareerHistoryModal"
            :karyawan="karyawan"
            :departemens="departemens"
            @close="closeCareerHistoryModal"
            @saved="refreshData"
        />
    </AuthenticatedLayout>
</template>
