<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    PrinterIcon,
    ArrowDownTrayIcon,
    ArrowLeftIcon,
    PaperClipIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon
} from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    cuti: Object,
    qrPemohon: String
});

const formatDate = (date) => {
    if (!date) return '-';
    return dayjs(date).format('D MMMM YYYY');
};

const decodeBase64 = (str) => {
    try {
        return typeof window !== 'undefined' ? atob(str) : '';
    } catch (e) {
        return '';
    }
};

const getStatusColor = (status) => {
    if (status === 'Approved') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
    if (status === 'Rejected') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
};
</script>

<template>
    <Head title="Detail Cuti" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.cuti.my-requests')" class="hidden sm:inline-flex p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors">
                        <ArrowLeftIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Detail Cuti</h2>
                </div>
                
                <div class="hidden sm:flex items-center gap-2">
                    <!-- Export PDF Button -->
                    <a :href="route('admin.cuti.export-pdf', cuti.id)" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                        <ArrowDownTrayIcon class="w-4 h-4 mr-2" /> Cetak PDF
                    </a>
                    
                    <!-- Print Popup Button -->
                    <a :href="route('admin.cuti.print', cuti.id)" target="_blank" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        <PrinterIcon class="w-4 h-4 mr-2" /> Print
                    </a>
                </div>
            </div>
        </template>

        <div class="pt-4 pb-12 sm:py-12">
            <div class="max-w-4xl mx-auto space-y-6">
                <!-- Mobile Action Buttons -->
                <div class="flex sm:hidden items-center justify-between gap-2">
                    <Link :href="route('admin.cuti.my-requests')" class="flex-none inline-flex items-center justify-center p-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 h-full">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <a :href="route('admin.cuti.export-pdf', cuti.id)" target="_blank" class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 shadow-sm">
                        <ArrowDownTrayIcon class="w-4 h-4 mr-2" /> PDF
                    </a>
                    <a :href="route('admin.cuti.print', cuti.id)" target="_blank" class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                        <PrinterIcon class="w-4 h-4 mr-2" /> Print
                    </a>
                </div>

                <!-- Status Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700">
                    <div class="p-6 sm:p-8">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Status Pengajuan</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Diajukan pada {{ formatDate(cuti.created_at) }}</p>
                                <span :class="getStatusColor(cuti.status)" class="inline-block mt-3 px-4 py-2 rounded-lg font-bold text-sm text-center">
                                    {{ cuti.status }}
                                </span>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <!-- Detail Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                    <div class="p-6 sm:p-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">Informasi Cuti</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Jenis Cuti</p>
                                <p class="font-medium text-gray-900 dark:text-white mt-1">{{ cuti.jenis_cuti.nama_cuti }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Durasi</p>
                                <p class="font-medium text-gray-900 dark:text-white mt-1">{{ cuti.jumlah_hari }} Hari Kerja</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal Pelaksanaan</p>
                                <p class="font-medium text-gray-900 dark:text-white mt-1">
                                    {{ formatDate(cuti.tgl_mulai) }} s/d {{ formatDate(cuti.tgl_selesai) }}
                                </p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Alasan</p>
                                <div class="mt-1 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg text-gray-900 dark:text-gray-100">
                                    {{ cuti.alasan }}
                                </div>
                            </div>
                            <div class="md:col-span-2" v-if="cuti.lampiran_path">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Lampiran (Surat Dokter / Bukti)</p>
                                <a :href="`/storage/${cuti.lampiran_path}`" target="_blank" class="inline-flex items-center gap-2 p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                                    <PaperClipIcon class="w-5 h-5" />
                                    <span class="font-medium text-sm">Lihat Lampiran</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approval Process Timeline -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-700" v-if="cuti.approval_process && cuti.approval_process.length > 0">
                    <div class="p-6 sm:p-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">Proses Persetujuan (Approval)</h3>
                        
                        <div class="relative border-l border-gray-200 dark:border-gray-700 ml-3 space-y-8">
                            <div v-for="(step, index) in cuti.approval_process" :key="step.id" class="pl-6 relative">
                                <!-- Marker -->
                                <div class="absolute w-6 h-6 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center -left-3 top-0">
                                    <CheckCircleIcon v-if="step.status === 'Approved'" class="w-6 h-6 text-green-500" />
                                    <XCircleIcon v-else-if="step.status === 'Rejected'" class="w-6 h-6 text-red-500" />
                                    <ClockIcon v-else class="w-6 h-6 text-yellow-500" />
                                </div>
                                
                                <div>
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-bold text-gray-900 dark:text-white">{{ step.target_karyawan?.nama_lengkap || 'Manajer' }}</h4>
                                        <span class="text-xs text-gray-500">{{ step.tanggal_proses ? formatDate(step.tanggal_proses) : '-' }}</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Tahap {{ step.tahap }}</p>
                                    
                                    <div v-if="step.status === 'Approved'" class="mt-2 text-sm text-green-600 bg-green-50 dark:bg-green-900/20 p-2 rounded">
                                        Disetujui. {{ step.catatan ? `Catatan: ${step.catatan}` : '' }}
                                    </div>
                                    <div v-else-if="step.status === 'Rejected'" class="mt-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 p-2 rounded">
                                        Ditolak. {{ step.catatan ? `Alasan: ${step.catatan}` : '' }}
                                    </div>
                                    <div v-else class="mt-2 text-sm text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20 p-2 rounded">
                                        Menunggu Persetujuan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Tanda Tangan & Verifikasi -->
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 pb-12">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                <div class="p-6 sm:p-8">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">Tanda Tangan & Verifikasi Dokumen</h3>
                    
                    <div class="flex flex-wrap gap-8 justify-around items-end text-center">
                        <!-- Kolom Pemohon -->
                        <div class="flex flex-col items-center">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2">Dibuat / Pemohon</p>
                            <div class="bg-white p-2 rounded-lg border border-gray-200 shadow-sm mb-2 inline-flex items-center justify-center" v-html="decodeBase64(qrPemohon)"></div>
                            <span class="text-xs text-gray-400 dark:text-gray-500 italic mb-1">Diajukan: {{ formatDate(cuti.created_at) }}</span>
                            <span class="font-bold text-gray-900 dark:text-white underline">{{ cuti.karyawan.nama_lengkap }}</span>
                        </div>

                        <!-- Kolom Approvers -->
                        <template v-if="cuti.approval_process && cuti.approval_process.length > 0">
                            <div v-for="step in cuti.approval_process" :key="'ttd-'+step.id" class="flex flex-col items-center">
                                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2">
                                    {{ step.status === 'Approved' ? 'Disetujui' : (step.status === 'Rejected' ? 'Ditolak' : 'Mengetahui') }} (Tahap {{ step.tahap }})
                                </p>
                                
                                <div v-if="step.status === 'Approved' || step.status === 'Rejected'" class="bg-white p-2 rounded-lg border border-gray-200 shadow-sm mb-2 inline-flex items-center justify-center" v-html="decodeBase64(step.qr_base64)"></div>
                                <div v-else class="w-[116px] h-[116px] border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg mb-2 flex items-center justify-center text-gray-400">
                                    <ClockIcon class="w-8 h-8" />
                                </div>
                                
                                <span class="text-xs text-gray-400 dark:text-gray-500 italic mb-1">{{ step.tanggal_proses ? formatDate(step.tanggal_proses) : 'Menunggu' }}</span>
                                <span class="font-bold text-gray-900 dark:text-white underline">{{ step.target_karyawan?.nama_lengkap || 'Manajer / Atasan' }}</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
