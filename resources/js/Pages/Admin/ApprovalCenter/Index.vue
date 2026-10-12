<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    CheckBadgeIcon, 
    BanknotesIcon, 
    CalendarDaysIcon, 
    DocumentTextIcon,
    ChevronRightIcon,
    ClipboardDocumentCheckIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    MagnifyingGlassIcon,
    UserCircleIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    CreditCardIcon,
    FunnelIcon,
    ArrowTopRightOnSquareIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    approvals: {
        type: Object,
        required: true
    },
    counts: {
        type: Object,
        default: () => ({
            pending: 0,
            approved: 0,
            rejected: 0,
            all: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            status: 'Pending',
            type: 'all',
            search: '',
            per_page: 15
        })
    },
});

const currentStatus = ref(props.filters?.status || 'Pending');
const currentType = ref(props.filters?.type || 'all');
const searchQuery = ref(props.filters?.search || '');

const applyFilters = () => {
    router.get(route('admin.approval-center.index'), {
        status: currentStatus.value,
        type: currentType.value,
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const setStatus = (status) => {
    currentStatus.value = status;
    applyFilters();
};

const setType = (type) => {
    currentType.value = type;
    applyFilters();
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};

const getDocumentTypeIcon = (type) => {
    switch(type) {
        case 'Pengajuan': return BanknotesIcon;
        case 'Cuti': return CalendarDaysIcon;
        case 'Pinjaman': return CreditCardIcon;
        case 'Invoice': return DocumentTextIcon;
        default: return ClipboardDocumentCheckIcon;
    }
};

const getTypeTheme = (type) => {
    switch(type) {
        case 'Pengajuan':
            return {
                bg: 'bg-blue-50 dark:bg-blue-950/40',
                text: 'text-blue-700 dark:text-blue-300',
                border: 'border-blue-200 dark:border-blue-800'
            };
        case 'Cuti':
            return {
                bg: 'bg-teal-50 dark:bg-teal-950/40',
                text: 'text-teal-700 dark:text-teal-300',
                border: 'border-teal-200 dark:border-teal-800'
            };
        case 'Pinjaman':
            return {
                bg: 'bg-purple-50 dark:bg-purple-950/40',
                text: 'text-purple-700 dark:text-purple-300',
                border: 'border-purple-200 dark:border-purple-800'
            };
        case 'Invoice':
            return {
                bg: 'bg-sky-50 dark:bg-sky-950/40',
                text: 'text-sky-700 dark:text-sky-300',
                border: 'border-sky-200 dark:border-sky-800'
            };
        default:
            return {
                bg: 'bg-gray-50 dark:bg-gray-800',
                text: 'text-gray-700 dark:text-gray-300',
                border: 'border-gray-200 dark:border-gray-700'
            };
    }
};

const getStatusTheme = (status) => {
    switch(status) {
        case 'Pending':
            return {
                // Orange / Kuning (Amber/Yellow/Orange)
                badgeBg: 'bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700',
                cardBorder: 'border-l-4 border-l-amber-500',
                pillDot: 'bg-amber-500',
                text: 'text-amber-600 dark:text-amber-400',
                bgSoft: 'bg-amber-50 dark:bg-amber-950/30',
                label: 'Butuh Disetujui',
                icon: ClockIcon
            };
        case 'Approved':
            return {
                // Hijau (Emerald/Green)
                badgeBg: 'bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-700',
                cardBorder: 'border-l-4 border-l-emerald-500',
                pillDot: 'bg-emerald-500',
                text: 'text-emerald-600 dark:text-emerald-400',
                bgSoft: 'bg-emerald-50 dark:bg-emerald-950/30',
                label: 'Disetujui',
                icon: CheckCircleIcon
            };
        case 'Rejected':
            return {
                // Merah (Red)
                badgeBg: 'bg-red-100 text-red-900 border-red-300 dark:bg-red-950/60 dark:text-red-300 dark:border-red-700',
                cardBorder: 'border-l-4 border-l-red-500',
                pillDot: 'bg-red-500',
                text: 'text-red-600 dark:text-red-400',
                bgSoft: 'bg-red-50 dark:bg-red-950/30',
                label: 'Ditolak',
                icon: XCircleIcon
            };
        default:
            return {
                badgeBg: 'bg-gray-100 text-gray-800 border-gray-300 dark:bg-gray-800 dark:text-gray-300',
                cardBorder: 'border-l-4 border-l-gray-400',
                pillDot: 'bg-gray-400',
                text: 'text-gray-600 dark:text-gray-400',
                bgSoft: 'bg-gray-50 dark:bg-gray-800',
                label: status,
                icon: ClipboardDocumentCheckIcon
            };
    }
};

const formatDate = (dateString) => {
    if (!dateString) return null;
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Approval Center - Persetujuan Terpadu" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <CheckBadgeIcon class="w-6 h-6" />
                        </div>
                        Approval Center Terpadu
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Pusat kendali seluruh dokumen dan pengajuan yang membutuhkan verifikasi atau otorisasi Anda.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Metric Summary Cards with Color Coding Rules -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                    <!-- Butuh Disetujui (Kuning / Orange) -->
                    <button 
                        type="button"
                        @click="setStatus('Pending')"
                        :class="[
                            'text-left relative overflow-hidden rounded-2xl p-5 sm:p-6 transition-all duration-200 border cursor-pointer group',
                            currentStatus === 'Pending'
                                ? 'bg-amber-50/90 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700 shadow-md ring-2 ring-amber-500'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-amber-200 hover:bg-amber-50/40 dark:hover:bg-amber-950/20'
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                                    Butuh Disetujui
                                </span>
                                <div class="mt-2 text-3xl sm:text-4xl font-extrabold text-amber-900 dark:text-amber-100 flex items-baseline gap-2">
                                    {{ counts.pending }}
                                    <span v-if="counts.pending > 0" class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                                </div>
                                <p class="mt-1 text-xs text-amber-700/80 dark:text-amber-300/80">
                                    Menunggu tindakan persetujuan Anda
                                </p>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300 shadow-inner group-hover:scale-105 transition-transform">
                                <ClockIcon class="w-8 h-8" />
                            </div>
                        </div>
                    </button>

                    <!-- Disetujui (Hijau) -->
                    <button 
                        type="button"
                        @click="setStatus('Approved')"
                        :class="[
                            'text-left relative overflow-hidden rounded-2xl p-5 sm:p-6 transition-all duration-200 border cursor-pointer group',
                            currentStatus === 'Approved'
                                ? 'bg-emerald-50/90 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-700 shadow-md ring-2 ring-emerald-500'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-emerald-200 hover:bg-emerald-50/40 dark:hover:bg-emerald-950/20'
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                    Disetujui
                                </span>
                                <div class="mt-2 text-3xl sm:text-4xl font-extrabold text-emerald-900 dark:text-emerald-100">
                                    {{ counts.approved }}
                                </div>
                                <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">
                                    Riwayat dokumen yang telah Anda setujui
                                </p>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300 shadow-inner group-hover:scale-105 transition-transform">
                                <CheckCircleIcon class="w-8 h-8" />
                            </div>
                        </div>
                    </button>

                    <!-- Ditolak (Merah) -->
                    <button 
                        type="button"
                        @click="setStatus('Rejected')"
                        :class="[
                            'text-left relative overflow-hidden rounded-2xl p-5 sm:p-6 transition-all duration-200 border cursor-pointer group',
                            currentStatus === 'Rejected'
                                ? 'bg-red-50/90 dark:bg-red-950/40 border-red-300 dark:border-red-700 shadow-md ring-2 ring-red-500'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-red-200 hover:bg-red-50/40 dark:hover:bg-red-950/20'
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-red-700 dark:text-red-400">
                                    Ditolak
                                </span>
                                <div class="mt-2 text-3xl sm:text-4xl font-extrabold text-red-900 dark:text-red-100">
                                    {{ counts.rejected }}
                                </div>
                                <p class="mt-1 text-xs text-red-700/80 dark:text-red-300/80">
                                    Riwayat dokumen yang ditolak dengan catatan
                                </p>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-300 shadow-inner group-hover:scale-105 transition-transform">
                                <XCircleIcon class="w-8 h-8" />
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Filter & Search Toolbar -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-5 space-y-4">
                    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                        
                        <!-- Status Tabs -->
                        <div class="flex items-center gap-1.5 p-1 bg-gray-100 dark:bg-gray-900/80 rounded-xl overflow-x-auto">
                            <button
                                @click="setStatus('Pending')"
                                :class="[
                                    'px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all whitespace-nowrap flex items-center gap-2',
                                    currentStatus === 'Pending'
                                        ? 'bg-amber-500 text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                <span class="h-2 w-2 rounded-full" :class="currentStatus === 'Pending' ? 'bg-white' : 'bg-amber-500'"></span>
                                Butuh Disetujui
                                <span class="text-xs px-1.5 py-0.2 rounded-full" :class="currentStatus === 'Pending' ? 'bg-amber-600 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'">
                                    {{ counts.pending }}
                                </span>
                            </button>
                            <button
                                @click="setStatus('Approved')"
                                :class="[
                                    'px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all whitespace-nowrap flex items-center gap-2',
                                    currentStatus === 'Approved'
                                        ? 'bg-emerald-600 text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                <span class="h-2 w-2 rounded-full" :class="currentStatus === 'Approved' ? 'bg-white' : 'bg-emerald-500'"></span>
                                Disetujui
                                <span class="text-xs px-1.5 py-0.2 rounded-full" :class="currentStatus === 'Approved' ? 'bg-emerald-700 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'">
                                    {{ counts.approved }}
                                </span>
                            </button>
                            <button
                                @click="setStatus('Rejected')"
                                :class="[
                                    'px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all whitespace-nowrap flex items-center gap-2',
                                    currentStatus === 'Rejected'
                                        ? 'bg-red-600 text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                <span class="h-2 w-2 rounded-full" :class="currentStatus === 'Rejected' ? 'bg-white' : 'bg-red-500'"></span>
                                Ditolak
                                <span class="text-xs px-1.5 py-0.2 rounded-full" :class="currentStatus === 'Rejected' ? 'bg-red-700 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'">
                                    {{ counts.rejected }}
                                </span>
                            </button>
                            <button
                                @click="setStatus('all')"
                                :class="[
                                    'px-3.5 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all whitespace-nowrap flex items-center gap-2',
                                    currentStatus === 'all'
                                        ? 'bg-gray-800 dark:bg-gray-700 text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                Semua Riwayat
                                <span class="text-xs px-1.5 py-0.2 rounded-full" :class="currentStatus === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'">
                                    {{ counts.all }}
                                </span>
                            </button>
                        </div>

                        <!-- Search Box -->
                        <div class="relative w-full lg:w-80">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Cari nomor dokumen, pemohon..."
                                class="w-full pl-10 pr-9 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:text-white"
                            />
                            <button 
                                v-if="searchQuery" 
                                @click="clearSearch"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            >
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Module / Type Filter Pills -->
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700/60 overflow-x-auto text-xs font-medium">
                        <span class="text-gray-400 dark:text-gray-500 flex items-center gap-1 shrink-0">
                            <FunnelIcon class="w-3.5 h-3.5" /> Filter Modul:
                        </span>
                        <button
                            @click="setType('all')"
                            :class="[
                                'px-3 py-1.5 rounded-lg border transition-colors whitespace-nowrap',
                                currentType === 'all'
                                    ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-transparent font-semibold'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                            ]"
                        >
                            Semua Modul
                        </button>
                        <button
                            @click="setType('Pengajuan')"
                            :class="[
                                'px-3 py-1.5 rounded-lg border transition-colors whitespace-nowrap flex items-center gap-1.5',
                                currentType === 'Pengajuan'
                                    ? 'bg-blue-600 text-white border-transparent font-semibold'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                            ]"
                        >
                            <BanknotesIcon class="w-3.5 h-3.5" />
                            Pengajuan Dana
                        </button>
                        <button
                            @click="setType('Cuti')"
                            :class="[
                                'px-3 py-1.5 rounded-lg border transition-colors whitespace-nowrap flex items-center gap-1.5',
                                currentType === 'Cuti'
                                    ? 'bg-teal-600 text-white border-transparent font-semibold'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                            ]"
                        >
                            <CalendarDaysIcon class="w-3.5 h-3.5" />
                            Cuti Karyawan
                        </button>
                        <button
                            @click="setType('Pinjaman')"
                            :class="[
                                'px-3 py-1.5 rounded-lg border transition-colors whitespace-nowrap flex items-center gap-1.5',
                                currentType === 'Pinjaman'
                                    ? 'bg-purple-600 text-white border-transparent font-semibold'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                            ]"
                        >
                            <CreditCardIcon class="w-3.5 h-3.5" />
                            Pinjaman Karyawan
                        </button>
                        <button
                            @click="setType('Invoice')"
                            :class="[
                                'px-3 py-1.5 rounded-lg border transition-colors whitespace-nowrap flex items-center gap-1.5',
                                currentType === 'Invoice'
                                    ? 'bg-sky-600 text-white border-transparent font-semibold'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                            ]"
                        >
                            <DocumentTextIcon class="w-3.5 h-3.5" />
                            Invoice Penjualan
                        </button>
                    </div>
                </div>

                <!-- Approval Items List -->
                <div v-if="approvals.data.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center shadow-sm">
                    <div class="mx-auto w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-700/60 flex items-center justify-center text-gray-400 dark:text-gray-500 mb-4">
                        <ClipboardDocumentCheckIcon class="w-8 h-8" />
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Tidak Ada Dokumen Approval
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                        <span v-if="currentStatus === 'Pending'">
                            Semua dokumen telah diproses. Saat ini tidak ada yang membutuhkan persetujuan Anda.
                        </span>
                        <span v-else>
                            Tidak ditemukan data dokumen pada filter status ini.
                        </span>
                    </p>
                </div>

                <div v-else class="space-y-4">
                    <div 
                        v-for="item in approvals.data" 
                        :key="item.id"
                        :class="[
                            'bg-white dark:bg-gray-800 rounded-2xl border shadow-sm transition-all duration-200 overflow-hidden',
                            getStatusTheme(item.status).cardBorder,
                            item.status === 'Pending' ? 'border-amber-200/80 dark:border-amber-900/40 hover:shadow-md' : 'border-gray-200 dark:border-gray-700 hover:shadow-md'
                        ]"
                    >
                        <div class="p-5 sm:p-6 space-y-4">
                            <!-- Card Header: Document Type, Code, Level Order, Status Badge -->
                            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700/70">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <!-- Type Badge -->
                                    <span :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border', getTypeTheme(item.type).bg, getTypeTheme(item.type).text, getTypeTheme(item.type).border]">
                                        <component :is="getDocumentTypeIcon(item.type)" class="w-3.5 h-3.5" />
                                        {{ item.type_label || item.type }}
                                    </span>

                                    <!-- Document Code -->
                                    <span class="font-mono text-xs font-bold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 rounded-md">
                                        {{ item.document_number }}
                                    </span>

                                    <!-- Step / Action level -->
                                    <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                        • {{ item.label_aksi }} (Tahap {{ item.level_order }})
                                    </span>
                                </div>

                                <!-- Status Badge with specific color coding -->
                                <div>
                                    <span :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-xs', getStatusTheme(item.status).badgeBg]">
                                        <span class="w-2 h-2 rounded-full" :class="getStatusTheme(item.status).pillDot"></span>
                                        {{ getStatusTheme(item.status).label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Card Body: Applicant Info & Document Details -->
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                                
                                <!-- Left Section: Applicant & Title -->
                                <div class="lg:col-span-8 space-y-3">
                                    <!-- Applicant Info -->
                                    <div class="flex items-center gap-3">
                                        <div class="relative shrink-0">
                                            <img 
                                                v-if="item.applicant_avatar" 
                                                :src="item.applicant_avatar" 
                                                alt="Avatar" 
                                                class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-gray-700" 
                                            />
                                            <div v-else class="w-10 h-10 rounded-full bg-gradient-to-tr from-amber-500 to-amber-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                                                {{ (item.applicant || 'K')[0].toUpperCase() }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                    {{ item.applicant }}
                                                </h4>
                                                <span v-if="item.applicant_nik && item.applicant_nik !== '-'" class="text-[11px] text-gray-400 font-mono">
                                                    ({{ item.applicant_nik }})
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                <span class="flex items-center gap-1">
                                                    <BuildingOfficeIcon class="w-3.5 h-3.5 text-gray-400" />
                                                    {{ item.applicant_department }}
                                                </span>
                                                <span v-if="item.applicant_position && item.applicant_position !== '-'">
                                                    • {{ item.applicant_position }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Document Title & Details -->
                                    <div class="pt-1">
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                            {{ item.details?.title || item.document?.title }}
                                        </h3>
                                        <p v-if="item.details?.description" class="mt-1 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                            {{ item.details.description }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Right Section: Metric / Amount Box -->
                                <div class="lg:col-span-4 bg-gray-50 dark:bg-gray-900/60 rounded-xl p-4 border border-gray-100 dark:border-gray-800 space-y-2.5">
                                    <!-- Amount if available -->
                                    <div v-if="item.details?.formatted_amount">
                                        <div class="text-[11px] font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Total Nominal
                                        </div>
                                        <div class="text-xl font-extrabold text-gray-900 dark:text-white mt-0.5">
                                            {{ item.details.formatted_amount }}
                                        </div>
                                    </div>

                                    <!-- Duration if cuti -->
                                    <div v-if="item.details?.days">
                                        <div class="text-[11px] font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Durasi Cuti
                                        </div>
                                        <div class="text-lg font-bold text-teal-700 dark:text-teal-300 mt-0.5">
                                            {{ item.details.days }} Hari Kerja
                                        </div>
                                        <div v-if="item.details?.start_date && item.details?.end_date" class="text-xs text-gray-500 mt-0.5">
                                            {{ formatDate(item.details.start_date) }} s/d {{ formatDate(item.details.end_date) }}
                                        </div>
                                    </div>

                                    <!-- Tenor / Installment if Pinjaman -->
                                    <div v-if="item.details?.tenor">
                                        <div class="text-xs text-gray-600 dark:text-gray-400 flex items-center justify-between">
                                            <span>Tenor:</span>
                                            <span class="font-bold text-gray-900 dark:text-white">{{ item.details.tenor }} Bulan</span>
                                        </div>
                                        <div v-if="item.details?.monthly_installment" class="text-xs text-gray-600 dark:text-gray-400 flex items-center justify-between pt-1">
                                            <span>Angsuran:</span>
                                            <span class="font-bold text-purple-700 dark:text-purple-300">{{ item.details.monthly_installment }}</span>
                                        </div>
                                    </div>

                                    <!-- Due date if Invoice -->
                                    <div v-if="item.details?.due_date">
                                        <div class="text-xs text-gray-600 dark:text-gray-400 flex items-center justify-between">
                                            <span>Jatuh Tempo:</span>
                                            <span class="font-semibold text-rose-600 dark:text-rose-400">{{ formatDate(item.details.due_date) }}</span>
                                        </div>
                                    </div>

                                    <!-- Badge Info (e.g. Petty Cash / Reimbursement / Kasbon) -->
                                    <div v-if="item.details?.badge_info && !item.details?.days" class="pt-1 border-t border-gray-200 dark:border-gray-800">
                                        <span class="inline-flex items-center text-xs font-medium text-gray-600 dark:text-gray-400">
                                            Info: {{ item.details.badge_info }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan Penolakan / Persetujuan (If exists) -->
                            <div v-if="item.catatan" class="rounded-xl p-3.5 text-xs space-y-1" :class="item.status === 'Rejected' ? 'bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 text-red-900 dark:text-red-200' : 'bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 text-emerald-900 dark:text-emerald-200'">
                                <div class="font-bold flex items-center gap-1.5">
                                    <component :is="item.status === 'Rejected' ? XCircleIcon : CheckCircleIcon" class="w-4 h-4" />
                                    <span>Catatan {{ item.status === 'Rejected' ? 'Penolakan' : 'Persetujuan' }}:</span>
                                </div>
                                <p class="pl-5 italic">
                                    "{{ item.catatan }}"
                                </p>
                                <div v-if="item.action_employee" class="pl-5 text-[11px] opacity-80 pt-0.5">
                                    Oleh: {{ item.action_employee }} <span v-if="item.acted_at">pada {{ item.acted_at }}</span>
                                </div>
                            </div>

                            <!-- Card Footer: Timestamps & Action Buttons -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-gray-100 dark:border-gray-700/70 text-xs">
                                <div class="text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <span>Diajukan: <strong class="font-medium text-gray-700 dark:text-gray-300">{{ item.created_at || '-' }}</strong></span>
                                    <span v-if="item.created_at_human" class="text-gray-400">({{ item.created_at_human }})</span>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2">
                                    <Link 
                                        :href="item.route_url || '#'"
                                        :class="[
                                            'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-xs',
                                            item.status === 'Pending'
                                                ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-amber-500/20 hover:shadow-md'
                                                : item.status === 'Approved'
                                                    ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                                    : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200'
                                        ]"
                                    >
                                        <span v-if="item.status === 'Pending'">Tinjau & Proses Dokumen</span>
                                        <span v-else>Lihat Detail Dokumen</span>
                                        <ChevronRightIcon class="w-4 h-4" />
                                    </Link>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-8" v-if="approvals.links && approvals.data.length > 0">
                    <Pagination :links="approvals.links" />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
