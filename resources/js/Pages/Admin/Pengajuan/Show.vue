<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import FilePreviewModal from '@/Components/FilePreviewModal.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue'; 
import PaymentModal from '@/Components/PaymentModal.vue'; 
import PinModal from '@/Components/PinModal.vue'; 
import ActivityLogList from '@/Components/ActivityLogList.vue'; // Import ActivityLogList
import { Head, Link, useForm } from '@inertiajs/vue3'; 
import { ref, computed, onMounted } from 'vue'; 
import axios from 'axios';
import { 
    ArrowLeftIcon, PrinterIcon, PencilSquareIcon, TrashIcon, 
    DocumentTextIcon, CreditCardIcon, UserCircleIcon, CheckCircleIcon, 
    XCircleIcon, ClockIcon, PaperClipIcon, BanknotesIcon, EyeIcon,
    BuildingStorefrontIcon, ChatBubbleLeftRightIcon, ExclamationCircleIcon,
    ReceiptPercentIcon, CheckBadgeIcon, PlusIcon
} from '@heroicons/vue/24/solid';

import { formatCurrency, formatDate } from '@/utils/helpers';

const props = defineProps({
    pengajuan: Object,
    canApprove: Boolean,
    canPay: Boolean,
    canSettle: Boolean,
    canEditSettlement: Boolean,
    canVerifySettlement: Boolean, 
    listKasBank: Array,
    pin_session_valid: Boolean, // Prop dari backend
});

// --- TABS STATE ---
const activeTab = ref(props.pengajuan.laporan_penggunaan ? 'finance' : 'detail'); // 'detail' | 'finance' | 'activity'

// --- STATUS COLOR ---
const getStatusBadgeClass = (status) => {
    switch(status) {
        case 'Draft': return 'bg-gray-100 text-gray-800 border-gray-200';
        case 'Pending Approval': return 'bg-yellow-50 text-yellow-700 border-yellow-200';
        case 'Approved': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'Paid': return 'bg-green-50 text-green-700 border-green-200';
        case 'Verification': return 'bg-orange-50 text-orange-700 border-orange-200';
        case 'Settled': return 'bg-green-50 text-green-700 border-green-200';
        case 'Rejected': return 'bg-red-50 text-red-700 border-red-200';
        case 'Revision': return 'bg-pink-50 text-pink-700 border-pink-200';
        default: return 'bg-gray-50 text-gray-600 border-gray-200';
    }
};

// --- LOGIC FILE PREVIEW ---
const showFilePreview = ref(false);
const activeFileUrl = ref('');
const activeFileType = ref('');
const activeFileName = ref('');
const showPaymentModal = ref(false);
const showRejectModal = ref(false);
const showPinModal = ref(false);
const showSettlementRejectModal = ref(false);

const settlementRejectForm = useForm({
    tipe_revisi: 'partial',
    catatan: ''
});

// PIN Logic
const hasPin = ref(false);
const pinMode = ref('verify'); // 'verify' or 'create'
const pendingAction = ref(null);

const attachmentName = computed(() => {
    return props.pengajuan.attachment_path 
        ? props.pengajuan.attachment_path.split('/').pop() 
        : '';
});

const checkPinStatus = async () => {
    try {
        const res = await axios.get(route('admin.pin.status'));
        hasPin.value = res.data.has_pin;
    } catch (e) {
        console.error(e);
    }
};

onMounted(() => {
    checkPinStatus();
});

const form = useForm({});
const approvalForm = useForm({ 
    status: '', // Add status field
    catatan: '' 
});

const originalTotal = computed(() => {
    if (props.pengajuan.tipe_pengajuan === 'UangMuka' && props.pengajuan.laporan_penggunaan) {
        return parseFloat(props.pengajuan.laporan_penggunaan.total_realisasi_aktual) + parseFloat(props.pengajuan.laporan_penggunaan.selisih);
    }
    return parseFloat(props.pengajuan.total_nominal_diajukan);
});

const setPreviewFile = (path) => {
    if (!path) return;
    activeFileUrl.value = `/storage/${path}`;
    activeFileName.value = path.split('/').pop();
    activeFileType.value = path.toLowerCase().endsWith('.pdf') ? 'pdf' : 'image';
    showFilePreview.value = true;
};

// Wrapper functions untuk berbagai jenis file
const openMainAttachment = () => setPreviewFile(props.pengajuan.attachment_path);
const openPaymentProof = (payment) => setPreviewFile(payment.bukti_bayar_path);
const openSettlementProof = (item) => setPreviewFile(item.bukti_path);
const openRefundProof = () => setPreviewFile(props.pengajuan.laporan_penggunaan?.bukti_pengembalian_path);

// --- ACTIONS ---
const deletePengajuan = () => {
    if (confirm('Apakah Anda yakin ingin menghapus pengajuan ini? Saldo anggaran yang terpakai akan dikembalikan.')) {
        form.delete(route('admin.pengajuan.destroy', props.pengajuan.id));
    }
};

const toggleOpenCoa = () => {
    const action = props.pengajuan.is_open_coa ? 'mengunci' : 'membuka';
    if (confirm(`Apakah Anda yakin ingin ${action} akses semua COA untuk laporan pengajuan ini?`)) {
        form.post(route('admin.pengajuan.toggle-open-coa', props.pengajuan.id), {
            preserveScroll: true
        });
    }
};

const submitApprove = () => {
    approvalForm.status = 'Approved'; // Set status

    if (!props.pin_session_valid) {
        pendingAction.value = 'approve';
        showPinModal.value = true;
        return;
    }

    if (confirm('Setujui pengajuan ini?')) {
        approvalForm.post(route('admin.pengajuan.action', props.pengajuan.id), {
             onSuccess: () => {
                // Optional: Refresh page handled by Inertia
             }
        });
    }
};

const submitReject = () => {
    approvalForm.status = 'Rejected'; // Set status
    if (!approvalForm.catatan) return alert('Mohon isi alasan penolakan.');
    
    if (!props.pin_session_valid) {
        pendingAction.value = 'reject';
        showPinModal.value = true;
        return;
    }

    if (confirm('Tolak pengajuan ini?')) {
        // Use action route because controller handles both based on status
        approvalForm.post(route('admin.pengajuan.action', props.pengajuan.id), {
            onSuccess: () => showRejectModal.value = false 
        });
    }
};

const onPinSuccess = () => {
    try {
        if (pendingAction.value === 'approve') {
            if (confirm('PIN Terverifikasi. Setujui pengajuan ini?')) {
                approvalForm.status = 'Approved'; // Ensure status is set
                approvalForm.post(route('admin.pengajuan.action', props.pengajuan.id));
            }
        } else if (pendingAction.value === 'reject') {
            if (confirm('PIN Terverifikasi. Tolak pengajuan ini?')) {
                approvalForm.status = 'Rejected'; // Ensure status is set
                approvalForm.post(route('admin.pengajuan.action', props.pengajuan.id), {
                    onSuccess: () => showRejectModal.value = false 
                });
            }
        }
        pendingAction.value = null;
    } catch (error) {
        console.error("Error in onPinSuccess:", error);
        alert("Terjadi kesalahan setelah verifikasi PIN: " + error.message);
    }
};

// --- FINANCE VERIFY ACTIONS ---
const verifySettlement = () => {
    if(confirm('Apakah laporan ini sudah valid dan lengkap? Status akan menjadi Settled.')) {
        form.post(route('admin.settlement.verify', props.pengajuan.id));
    }
};

const rejectSettlement = () => {
    showSettlementRejectModal.value = true;
};

const submitSettlementReject = () => {
    if (!settlementRejectForm.catatan) return alert('Mohon isi catatan penolakan laporan.');
    settlementRejectForm.post(route('admin.settlement.reject', props.pengajuan.id), {
        onSuccess: () => {
            showSettlementRejectModal.value = false;
            settlementRejectForm.reset();
        }
    });
};
</script>

<template>
    <Head title="Detail Pengajuan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.pengajuan.index')" class="p-2 rounded-full hover:bg-gray-100 transition">
                        <ArrowLeftIcon class="w-5 h-5 text-gray-600"/>
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Pengajuan</h2>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">#{{ pengajuan.nomor_pengajuan }}</p>
                    </div>
                </div>
            </div>
        </template>

        <div class="pb-32">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- 1. STATUS BANNER (Sticky Info) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 md:p-6">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-4 w-full md:w-auto">
                            <div class="p-3 rounded-full bg-indigo-50 text-indigo-600 hidden md:block">
                                <DocumentTextIcon class="w-8 h-8" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Status</p>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span :class="getStatusBadgeClass(pengajuan.status_global)" class="px-3 py-1 rounded-full text-sm font-bold border shadow-sm">
                                        {{ pengajuan.status_global }}
                                    </span>
                                    
                                    <!-- Laporan Badge -->
                                    <span v-if="pengajuan.tipe_pengajuan === 'UangMuka' && ['Paid', 'Revision', 'Verification', 'Settled'].includes(pengajuan.status_global)" 
                                          class="px-2 py-1 text-xs font-bold rounded-full border shadow-sm flex items-center"
                                          :class="pengajuan.is_butuh_laporan ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-green-50 text-green-700 border-green-200'">
                                        <ExclamationCircleIcon v-if="pengajuan.is_butuh_laporan" class="w-3.5 h-3.5 mr-1"/>
                                        <CheckCircleIcon v-else class="w-3.5 h-3.5 mr-1"/>
                                        {{ pengajuan.is_butuh_laporan ? 'Butuh Laporan' : 'Sudah Dilaporkan' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="w-full md:w-auto text-right">
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Nilai</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(pengajuan.total_nominal_diajukan) }}</p>
                        </div>
                    </div>

                    <!-- ALERTS STATUS -->
                    <div v-if="pengajuan.status_global === 'Paid' && pengajuan.tipe_pengajuan === 'UangMuka' && !canSettle" class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded text-orange-800 text-sm flex items-center animate-pulse">
                        <ExclamationCircleIcon class="w-5 h-5 mr-2 flex-shrink-0"/> 
                        <span>Menunggu laporan pertanggungjawaban dari <b>{{ pengajuan.karyawan_penerima?.nama_lengkap || pengajuan.pengaju?.nama_lengkap }}</b>.</span>
                    </div>
                    <div v-if="pengajuan.status_global === 'Verification'" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded text-blue-800 text-sm flex items-center">
                        <CheckBadgeIcon class="w-5 h-5 mr-2 flex-shrink-0"/> Laporan telah disubmit. Menunggu verifikasi Finance.
                    </div>
                    <div v-if="pengajuan.status_global === 'Revision'" class="mt-4 p-3 bg-pink-50 border border-pink-200 rounded text-pink-800 text-sm flex flex-col gap-1">
                        <div class="flex items-center font-bold"><ExclamationCircleIcon class="w-5 h-5 mr-2 flex-shrink-0"/> Laporan Perlu Direvisi</div>
                        <div class="ml-7 text-sm italic">"{{ pengajuan.catatan_header }}"</div>
                    </div>

                    <!-- HEADER ACTIONS (EDIT/DELETE/PRINT) -->
                    <div class="flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-gray-100 mt-4">
                        <button v-if="pengajuan.tipe_pengajuan === 'UangMuka' && ['Paid', 'Revision'].includes(pengajuan.status_global) && ($page.props.auth.user.roles.includes('Finance Manager') || $page.props.auth.user.roles.includes('Finance Staff') || $page.props.auth.user.roles.includes('Super Admin'))" 
                                @click="toggleOpenCoa"
                                class="flex-1 md:flex-none inline-flex items-center justify-center px-4 py-2 border rounded-lg font-semibold text-xs uppercase tracking-widest shadow-sm transition"
                                :class="pengajuan.is_open_coa ? 'bg-orange-50 border-orange-200 text-orange-700 hover:bg-orange-100' : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100'">
                            <svg v-if="pengajuan.is_open_coa" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            {{ pengajuan.is_open_coa ? 'Kunci Akses COA' : 'Buka Semua COA' }}
                        </button>
                        <a :href="route('admin.pengajuan.print', pengajuan.id)" target="_blank" class="flex-1 md:flex-none inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition">
                            <PrinterIcon class="w-4 h-4 mr-2"/> {{ pengajuan.tipe_pengajuan === 'UangMuka' ? 'PDF Uang Muka' : 'PDF' }}
                        </a>

                        <a v-if="pengajuan.laporan_penggunaan" :href="route('admin.settlement.print', pengajuan.id)" target="_blank" class="flex-1 md:flex-none inline-flex items-center justify-center px-4 py-2 bg-indigo-50 border border-indigo-200 rounded-lg font-semibold text-xs text-indigo-700 uppercase tracking-widest shadow-sm hover:bg-indigo-100 transition">
                            <PrinterIcon class="w-4 h-4 mr-2"/> PDF Laporan
                        </a>

                        <!-- NEW: Final Voucher PDF (100% Digital) -->
                        <a v-if="['Paid', 'Settled'].includes(pengajuan.status_global)" :href="route('admin.pengajuan.print-voucher', pengajuan.id)" target="_blank" class="flex-1 md:flex-none inline-flex items-center justify-center px-4 py-2 bg-green-50 border border-green-200 rounded-lg font-semibold text-xs text-green-700 uppercase tracking-widest shadow-sm hover:bg-green-100 transition">
                            <PrinterIcon class="w-4 h-4 mr-2"/> Dokumen Lengkap (Final)
                        </a>
                        <template v-if="(pengajuan.status_global === 'Pending Approval' || pengajuan.status_global === 'Draft' || pengajuan.status_global === 'Revision') && (!pengajuan.pembayaran || pengajuan.pembayaran.length === 0)">
                            <div v-if="$page.props.auth.user.roles.includes('Super Admin') || $page.props.auth.user.id === pengajuan.id_pengaju" class="flex flex-1 md:flex-none gap-2">
                                <Link :href="route('admin.pengajuan.edit', pengajuan.id)" class="flex-1 md:flex-none">
                                    <PrimaryButton class="w-full justify-center bg-yellow-600 hover:bg-yellow-700 border-yellow-600 gap-2">
                                        <PencilSquareIcon class="w-4 h-4"/> Edit
                                    </PrimaryButton>
                                </Link>
                                <DangerButton @click="deletePengajuan" class="flex-1 md:flex-none justify-center gap-2">
                                    <TrashIcon class="w-4 h-4"/> Hapus
                                </DangerButton>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 2. TABS NAVIGATION -->
                <div class="border-b border-gray-200 overflow-x-auto hide-scrollbar">
                    <nav class="-mb-px flex space-x-8 min-w-max px-4 md:px-0" aria-label="Tabs">
                        <a href="#" @click.prevent="activeTab = 'detail'" 
                           :class="[activeTab === 'detail' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center transition']">
                           <DocumentTextIcon class="w-4 h-4 mr-2"/> Detail Pengajuan
                        </a>
                        <a href="#" @click.prevent="activeTab = 'finance'" 
                           :class="[activeTab === 'finance' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center transition']">
                           <BanknotesIcon class="w-4 h-4 mr-2"/> Keuangan & Realisasi
                        </a>
                        <a href="#" @click.prevent="activeTab = 'activity'" 
                           :class="[activeTab === 'activity' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center transition']">
                           <ClockIcon class="w-4 h-4 mr-2"/> Riwayat Log
                        </a>
                    </nav>
                </div>

                <!-- 3. TAB CONTENT: DETAIL -->
                <div v-show="activeTab === 'detail'" class="space-y-6">
                    
                    <!-- Row 1: Info Pemohon & Instruksi Bayar -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Kiri: Info Pemohon -->
                        <div class="lg:col-span-2">
                            <!-- Card Info Pemohon -->
                            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden h-full">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                                    <h3 class="font-semibold text-gray-800 flex items-center">
                                        <UserCircleIcon class="w-5 h-5 mr-2 text-gray-400"/> Informasi Pemohon
                                    </h3>
                                    <span class="px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wide border shadow-sm"
                                        :class="pengajuan.tipe_pengajuan === 'Langsung' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-purple-50 text-purple-700 border-purple-200'">
                                        {{ pengajuan.tipe_pengajuan === 'Langsung' ? 'Langsung' : 'Uang Muka' }}
                                    </span>
                                </div>
                                <div class="p-4 md:p-6 space-y-5">
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <label class="text-[10px] uppercase font-bold text-gray-500 tracking-wide">Keperluan</label>
                                            <p class="text-lg font-medium text-gray-900 leading-snug mt-1">{{ pengajuan.judul_pengajuan }}</p>
                                        </div>
                                        <div>
                                            <label class="text-[10px] uppercase font-bold text-gray-500 tracking-wide">Tanggal</label>
                                            <div class="flex items-center mt-1 text-gray-900 text-sm">
                                                <ClockIcon class="w-4 h-4 mr-1.5 text-gray-400"/> {{ formatDate(pengajuan.tgl_pengajuan) }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start gap-3 p-3 rounded-lg bg-indigo-50 border border-indigo-100">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-full bg-indigo-200 flex items-center justify-center text-indigo-700 font-bold shadow-sm border border-indigo-200 text-sm">
                                                {{ pengajuan.pengaju?.nama_lengkap.charAt(0) }}
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">{{ pengajuan.pengaju?.nama_lengkap }}</p>
                                            <p class="text-xs text-indigo-700 font-medium">{{ pengajuan.pengaju?.jabatan }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ pengajuan.departemen?.nama_departemen }}</p>
                                        </div>
                                    </div>

                                    <div v-if="pengajuan.catatan_header">
                                        <label class="text-[10px] uppercase font-bold text-gray-500 tracking-wide">Catatan</label>
                                        <div class="mt-1 text-sm text-gray-600 bg-gray-50 p-3 rounded border border-gray-100 italic relative">
                                            <ChatBubbleLeftRightIcon class="w-5 h-5 text-gray-200 absolute top-2 right-2 -z-0"/>
                                            <span class="relative z-10">"{{ pengajuan.catatan_header }}"</span>
                                        </div>
                                    </div>

                                    <div v-if="pengajuan.attachment_path">
                                        <label class="text-[10px] uppercase font-bold text-gray-500 tracking-wide">Dokumen Lampiran</label>
                                        <button @click="openMainAttachment" class="mt-2 flex items-center w-full p-3 bg-white border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition group text-left shadow-sm">
                                            <div class="p-2 bg-indigo-100 rounded-full mr-3 group-hover:bg-indigo-200 text-indigo-600"><PaperClipIcon class="w-5 h-5"/></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ attachmentName }}</p>
                                                <p class="text-xs text-gray-500 group-hover:text-indigo-600">Klik untuk melihat pratinjau</p>
                                            </div>
                                            <EyeIcon class="w-5 h-5 text-gray-300 group-hover:text-indigo-500"/>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kanan: Instruksi Bayar -->
                        <div class="lg:col-span-1">
                            <!-- Card Instruksi Bayar -->
                            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden h-full">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center">
                                    <CreditCardIcon class="w-5 h-5 mr-2 text-gray-400"/>
                                    <h3 class="font-semibold text-gray-800">Instruksi Bayar</h3>
                                </div>
                                <div class="p-4 md:p-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="text-xs font-bold text-gray-500 uppercase">Metode</span>
                                        <span class="font-bold text-gray-900">{{ pengajuan.metode_pembayaran }}</span>
                                    </div>
                                    <div v-if="pengajuan.metode_pembayaran === 'Transfer'" class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-lg p-4 relative overflow-hidden">
                                        <div class="relative z-5">
                                            <div class="flex items-start mb-3">
                                                <div class="mt-1 mr-3 text-gray-400">
                                                    <BuildingStorefrontIcon v-if="pengajuan.vendor_penerima" class="w-5 h-5"/>
                                                    <UserCircleIcon v-else class="w-5 h-5"/>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider block mb-0.5">
                                                        {{ pengajuan.vendor_penerima ? 'VENDOR REKANAN' : 'KARYAWAN PENERIMA' }}
                                                    </span>
                                                    <span class="font-bold text-gray-900 text-sm leading-tight block truncate">
                                                        {{ pengajuan.vendor_penerima ? pengajuan.vendor_penerima.nama_vendor : (pengajuan.karyawan_penerima ? pengajuan.karyawan_penerima.nama_lengkap : pengajuan.pengaju?.nama_lengkap) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-if="pengajuan.bank_tujuan" class="border-t border-dashed border-gray-300 pt-3 space-y-2">
                                                <div class="flex justify-between text-sm"><span class="text-gray-500">Bank</span><span class="font-bold">{{ pengajuan.bank_tujuan }}</span></div>
                                                <div><span class="text-[10px] text-gray-500 block">No. Rekening</span><span class="font-mono font-bold text-indigo-700 bg-white px-2 py-0.5 rounded border text-sm inline-block">{{ pengajuan.no_rek_tujuan }}</span></div>
                                                <div><span class="text-[10px] text-gray-500 block">Atas Nama</span><span class="text-xs font-medium uppercase truncate block" :title="pengajuan.atas_nama_tujuan">{{ pengajuan.atas_nama_tujuan }}</span></div>
                                            </div>
                                            <div v-else class="text-xs text-red-500 bg-red-50 p-2 rounded mt-2 border border-red-100">Data rekening tidak terekam.</div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-6 text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200"><BanknotesIcon class="w-8 h-8 mx-auto mb-2 text-gray-300"/><p class="text-xs">Tunai / Kas Kecil</p></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Rincian Estimasi (Full Width) -->
                    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gray-50 font-bold text-gray-700 flex items-center">
                            <BanknotesIcon class="w-5 h-5 mr-2 text-gray-400"/>
                            {{ pengajuan.tipe_pengajuan === 'UangMuka' ? 'Rincian Awal (Estimasi)' : 'Rincian Biaya' }}
                        </div>
                        <div class="block md:hidden space-y-4 p-4 md:px-6 md:pb-6">
                            <div v-for="(item, index) in pengajuan.detail" :key="item.id" class="p-4 border border-gray-100 rounded-xl bg-white shadow-sm transition-transform active:scale-[0.99]">
                                <div class="flex justify-between items-start mb-2">
                                     <span class="text-xs font-mono text-gray-500 bg-gray-100 px-2 py-0.5 rounded">#{{ index + 1 }}</span>
                                </div>
                                <h4 class="font-medium text-gray-900 mb-1">{{ item.deskripsi_item }}</h4>
                                <div class="text-xs text-gray-500 mb-3 space-y-1">
                                    <div class="flex justify-between"><span class="text-gray-400">Program:</span> <span class="font-medium text-right text-gray-700">{{ item.program_kerja?.nama_program }}</span></div>
                                    <div class="flex justify-between"><span class="text-gray-400">Akun:</span> <span class="font-medium text-right text-gray-700">{{ item.akun_gl?.kode_akun }} - {{ item.akun_gl?.nama_akun }}</span></div>
                                </div>
                                <div class="border-t border-dashed border-gray-100 pt-2 space-y-1">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-500">Nominal:</span>
                                        <span class="font-bold text-gray-900">{{ formatCurrency(item.nominal_item) }}</span>
                                    </div>
                                    <div v-if="item.id_tax_type" class="flex justify-between text-xs">
                                        <span class="text-gray-500">Pajak ({{ item.pajak?.kode_pajak }}):</span>
                                        <span :class="item.pajak?.tipe === 'PPN' ? 'text-red-600' : 'text-green-600'">
                                            {{ item.pajak?.tipe === 'PPN' ? '+' : '-' }} {{ formatCurrency(item.nominal_pajak) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-sm mt-2 pt-2 border-t border-gray-100">
                                        <span class="font-bold text-gray-700">Total</span>
                                        <span class="font-bold text-indigo-700">{{ formatCurrency(parseFloat(item.nominal_item) + (item.pajak?.tipe === 'PPN' ? parseFloat(item.nominal_pajak) : (item.pajak?.tipe === 'PPh' ? -parseFloat(item.nominal_pajak) : 0))) }}</span>
                                    </div>
                                </div>
                            </div>
                             <div class="p-4 bg-indigo-50 rounded-xl flex justify-between items-center border border-indigo-100 shadow-sm">
                                <span class="font-bold text-indigo-800 uppercase text-xs tracking-wide">Total Pengajuan</span>
                                <span class="font-bold text-lg text-indigo-700">{{ formatCurrency(originalTotal) }}</span>
                            </div>
                        </div>

                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-bold uppercase w-12">No</th><th class="px-6 py-3 text-left text-xs font-bold uppercase">Deskripsi</th><th class="px-6 py-3 text-left text-xs font-bold uppercase">Pos Anggaran</th><th class="px-6 py-3 text-right text-xs font-bold uppercase">Nominal (DPP)</th><th class="px-6 py-3 text-right text-xs font-bold uppercase">Pajak</th><th class="px-6 py-3 text-right text-xs font-bold uppercase">Total</th></tr></thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(item, index) in pengajuan.detail" :key="item.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm text-center text-gray-500">{{ index + 1 }}</td>
                                        <td class="px-6 py-3 text-sm font-medium">{{ item.deskripsi_item }}</td>
                                        <td class="px-6 py-3 text-xs text-gray-500">{{ item.program_kerja?.nama_program }} <br> {{ item.akun_gl?.kode_akun }} - {{ item.akun_gl?.nama_akun }}</td>
                                        <td class="px-6 py-3 text-sm font-bold text-right">{{ formatCurrency(item.nominal_item) }}</td>
                                        <td class="px-6 py-3 text-xs text-right">
                                            <div v-if="item.id_tax_type">
                                                <div class="font-bold">{{ item.pajak?.kode_pajak }}</div>
                                                <div :class="item.pajak?.tipe === 'PPN' ? 'text-red-600' : 'text-green-600'">
                                                    {{ item.pajak?.tipe === 'PPN' ? '+' : '-' }} {{ formatCurrency(item.nominal_pajak) }}
                                                </div>
                                            </div>
                                            <div v-else class="text-gray-300">-</div>
                                        </td>
                                        <td class="px-6 py-3 text-sm font-bold text-right text-indigo-700">
                                            {{ formatCurrency(parseFloat(item.nominal_item) + (item.pajak?.tipe === 'PPN' ? parseFloat(item.nominal_pajak) : (item.pajak?.tipe === 'PPh' ? -parseFloat(item.nominal_pajak) : 0))) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-100 border-t border-gray-200">
                                    <tr>
                                        <td colspan="5" class="px-6 py-3 text-right font-bold text-xs uppercase tracking-wider">Total Pengajuan Awal</td>
                                        <td class="px-6 py-3 text-right font-bold text-indigo-700">{{ formatCurrency(originalTotal) }}</td>
                                    </tr>
                                    <tr v-if="originalTotal != pengajuan.total_nominal_diajukan" class="bg-yellow-50">
                                        <td colspan="5" class="px-6 py-3 text-right font-bold text-xs uppercase tracking-wider">Total Tagihan Akhir</td>
                                        <td class="px-6 py-3 text-right font-bold text-indigo-700">{{ formatCurrency(pengajuan.total_nominal_diajukan) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Row 3: Log Persetujuan (Full Width) -->
                    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gray-50 font-bold text-gray-700 flex items-center">
                            <ClockIcon class="w-5 h-5 mr-2 text-gray-400"/> Log Persetujuan
                        </div>
                        <div class="p-6">
                            <ol class="relative border-l border-gray-200 ml-3">
                                <li class="mb-6 ml-6">
                                    <span class="absolute flex items-center justify-center w-6 h-6 bg-green-100 rounded-full -left-3 ring-4 ring-white"><DocumentTextIcon class="w-3 h-3 text-green-600"/></span>
                                    <h3 class="flex items-center mb-1 text-sm font-semibold text-gray-900">Pengajuan Dibuat</h3>
                                    <time class="block mb-2 text-xs font-normal leading-none text-gray-400">{{ formatDate(pengajuan.created_at) }}</time>
                                    <p class="text-xs text-gray-500">Oleh: {{ pengajuan.pengaju?.nama_lengkap }}</p>
                                </li>
                                <li v-if="!pengajuan.approval_process?.length" class="ml-6">
                                    <span class="absolute flex items-center justify-center w-6 h-6 bg-gray-100 rounded-full -left-3 ring-4 ring-white"><span class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"></span></span>
                                    <h3 class="text-sm font-medium text-red-500">Data Approval Tidak Ditemukan</h3>
                                </li>
                                <li v-else v-for="log in pengajuan.approval_process" :key="log.id" class="mb-6 ml-6">
                                    <span class="absolute flex items-center justify-center w-6 h-6 rounded-full -left-3 ring-4 ring-white" :class="log.status === 'Approved' ? 'bg-green-100' : (log.status === 'Rejected' ? 'bg-red-100' : 'bg-yellow-100')">
                                        <component :is="log.status === 'Approved' ? CheckCircleIcon : (log.status === 'Rejected' ? XCircleIcon : ClockIcon)" class="w-3 h-3" :class="log.status === 'Approved' ? 'text-green-600' : (log.status === 'Rejected' ? 'text-red-600' : 'text-yellow-600')" />
                                    </span>
                                    <h3 class="mb-1 text-sm font-semibold text-gray-900">{{ log.label_aksi }} <span class="text-gray-500 font-normal text-xs">({{ log.jabatan_approver || log.target_karyawan?.jabatan }})</span></h3>
                                    <div v-if="['Approved','Rejected'].includes(log.status)">
                                        <time class="block mb-1 text-xs font-normal leading-none text-gray-400">{{ formatDate(log.tgl_aksi) }}</time>
                                        <p class="text-xs text-gray-500">Oleh: {{ log.action_karyawan?.nama_lengkap || log.approver?.nama_lengkap }}</p>
                                        <p v-if="log.catatan" class="text-xs italic text-gray-600 bg-gray-50 p-2 mt-1 rounded border">"{{ log.catatan }}"</p>
                                    </div>
                                    <div v-else-if="log.status === 'Pending'"><p class="text-xs text-gray-500 mb-1">Menunggu:</p><p class="text-xs font-medium text-gray-900">{{ log.target_karyawan?.nama_lengkap }}</p><div class="text-xs text-yellow-600 font-medium animate-pulse mt-1">Sedang diproses...</div></div>
                                    <div v-else class="text-xs text-gray-400">Menunggu giliran...</div>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- 4. TAB CONTENT: KEUANGAN & REALISASI -->
                <div v-show="activeTab === 'finance'" class="space-y-8">
                    
                    <!-- A. LAPORAN PERTANGGUNGJAWABAN (HERO SECTION) -->
                    <div v-if="pengajuan.laporan_penggunaan" class="relative overflow-hidden rounded-xl bg-white border border-gray-200 shadow-md">
                        <div class="px-6 py-5 border-b bg-gradient-to-r from-purple-50 to-white flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-purple-100 text-purple-700 rounded-lg"><ReceiptPercentIcon class="w-6 h-6"/></div>
                                <div><h3 class="text-lg font-bold text-gray-900">Laporan Pertanggungjawaban</h3><p class="text-xs text-gray-500">Diserahkan: {{ formatDate(pengajuan.laporan_penggunaan.tgl_laporan) }}</p></div>
                            </div>
                            <div v-if="pengajuan.status_global === 'Settled'" class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full border border-green-200 flex items-center"><CheckCircleIcon class="w-4 h-4 mr-1"/> Terverifikasi</div>
                        </div>
                        <div class="p-6">
                            <!-- Dashboard Summary -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200"><p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Total Uang Muka</p><p class="text-xl font-bold text-gray-700">{{ formatCurrency(originalTotal) }}</p></div>
                                <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-100"><p class="text-xs text-indigo-600 uppercase font-bold tracking-wider mb-1">Total Realisasi</p><p class="text-2xl font-bold text-indigo-700">{{ formatCurrency(pengajuan.laporan_penggunaan.total_realisasi_aktual) }}</p></div>
                                <div class="p-4 rounded-xl border relative overflow-hidden" :class="pengajuan.laporan_penggunaan.selisih >= 0 ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200'">
                                    <p class="text-xs uppercase font-bold tracking-wider mb-1" :class="pengajuan.laporan_penggunaan.selisih >= 0 ? 'text-green-700' : 'text-yellow-700'">{{ pengajuan.laporan_penggunaan.selisih >= 0 ? 'Sisa Uang (Kembali)' : 'Kurang Bayar (Reimburse)' }}</p>
                                    <p class="text-2xl font-bold" :class="pengajuan.laporan_penggunaan.selisih >= 0 ? 'text-green-800' : 'text-yellow-800'">{{ formatCurrency(Math.abs(pengajuan.laporan_penggunaan.selisih)) }}</p>
                                    <div v-if="pengajuan.laporan_penggunaan.bukti_pengembalian_path" class="mt-3 pt-3 border-t border-dashed" :class="pengajuan.laporan_penggunaan.selisih >= 0 ? 'border-green-200' : 'border-yellow-200'">
                                        <button @click="openRefundProof" class="flex items-center text-xs font-bold underline hover:opacity-80" :class="pengajuan.laporan_penggunaan.selisih >= 0 ? 'text-green-800' : 'text-yellow-800'"><PaperClipIcon class="w-3 h-3 mr-1"/> Lihat Bukti Transfer</button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabel Realisasi -->
                            <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center"><DocumentTextIcon class="w-4 h-4 mr-2 text-gray-400"/> Rincian Penggunaan Dana</h4>
                                <!-- Mobile Card View -->
                                <div class="block md:hidden space-y-4">
                                    <div v-for="(item, index) in pengajuan.laporan_penggunaan.detail" :key="item.id" class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                                        <h5 class="font-bold text-gray-900 text-sm mb-1">{{ item.deskripsi_bon }}</h5>
                                        <p class="text-xs text-indigo-600 font-semibold">{{ item.program_kerja?.nama_program }}</p>
                                        <p class="text-xs text-gray-500 mb-2">{{ item.akun_gl?.kode_akun }} - {{ item.akun_gl?.nama_akun }}</p>
                                        
                                        <div class="flex justify-between items-center border-t border-gray-200 pt-2 mt-2">
                                            <button v-if="item.bukti_path" @click="openSettlementProof(item)" class="text-xs bg-white border border-gray-300 px-2 py-1 rounded text-gray-600 flex items-center hover:bg-gray-100"><EyeIcon class="w-3 h-3 mr-1"/> Bukti</button>
                                            <span v-else class="text-xs text-gray-400 italic">Tanpa Bukti</span>
                                            
                                            <span class="font-mono font-bold text-gray-800">{{ formatCurrency(item.nominal_bon) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="hidden md:block bg-white overflow-hidden shadow-sm rounded-lg overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50 text-gray-600 text-xs uppercase"><tr><th class="px-4 py-2 text-left font-bold w-10">#</th><th class="px-4 py-2 text-left font-bold">Deskripsi</th><th class="px-4 py-2 text-left font-bold">Pos Anggaran</th><th class="px-4 py-2 text-center font-bold">Bukti</th><th class="px-4 py-2 text-right font-bold">Nominal</th></tr></thead><tbody class="divide-y divide-gray-100 text-sm bg-white"><tr v-for="(item, index) in pengajuan.laporan_penggunaan.detail" :key="item.id" class="hover:bg-gray-50"><td class="px-4 py-3 text-gray-500 text-center">{{ index + 1 }}</td><td class="px-4 py-3 font-medium text-gray-900">{{ item.deskripsi_bon }}</td><td class="px-4 py-3 text-xs text-gray-500"><span class="block font-semibold text-indigo-600">{{ item.program_kerja?.nama_program }}</span><span class="block">{{ item.akun_gl?.kode_akun }} - {{ item.akun_gl?.nama_akun }}</span></td><td class="px-4 py-3 text-center"><button v-if="item.bukti_path" @click="openSettlementProof(item)" class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs hover:bg-gray-200 transition"><EyeIcon class="w-3 h-3 mr-1"/> Lihat</button><span v-else class="text-gray-300">-</span></td><td class="px-4 py-3 text-right font-mono font-bold text-gray-800">{{ formatCurrency(item.nominal_bon) }}</td></tr></tbody></table>
                                </div>
                        </div>
                    </div>
                    
                    <!-- Empty State Settlement -->
                    <div v-else-if="pengajuan.tipe_pengajuan === 'UangMuka'" class="flex flex-col items-center justify-center p-10 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4"><ReceiptPercentIcon class="w-8 h-8 text-gray-400"/></div>
                        <h3 class="text-lg font-medium text-gray-900">Belum Ada Laporan</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-md">Penerima dana belum menyerahkan laporan pertanggungjawaban.</p>
                        <div v-if="canSettle" class="mt-6"><Link :href="route('admin.settlement.create', pengajuan.id)"><PrimaryButton class="bg-purple-600 hover:bg-purple-700 border-purple-600 shadow-lg"><PlusIcon class="w-5 h-5 mr-2"/> Buat Laporan Sekarang</PrimaryButton></Link></div>
                    </div>

                    <!-- B. RIWAYAT PEMBAYARAN (PAYMENT HISTORY) -->
                    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b bg-green-50 flex justify-between items-center">
                            <h3 class="font-bold text-green-800 flex items-center"><BanknotesIcon class="w-5 h-5 mr-2"/> Riwayat Pembayaran (Cash Out)</h3>
                            <span class="text-xs font-bold px-2 py-1 bg-white rounded border border-green-200 text-green-700 uppercase">Sisa Tagihan: {{ formatCurrency(pengajuan.sisa_tagihan) }}</span>
                        </div>
                        <div class="overflow-x-auto" v-if="pengajuan.pembayaran?.length">
                            <!-- Mobile Card View -->
                            <div class="block md:hidden space-y-4 px-6 pb-6 pt-2">
                                <div v-for="bayar in pengajuan.pembayaran" :key="bayar.id" class="p-4 border border-green-200 rounded-xl bg-white shadow-sm relative active:scale-[0.99] transition-transform">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-xs font-bold text-green-800 flex items-center bg-white px-2 py-0.5 rounded border border-green-100">
                                            <ClockIcon class="w-3 h-3 mr-1"/> {{ formatDate(bayar.tgl_bayar) }}
                                        </div>
                                    </div>
                                    
                                    <h4 class="font-bold text-gray-900 text-sm mb-1">{{ bayar.kas_bank?.nama_bank }}</h4>
                                    <p class="text-xs text-gray-500 font-mono mb-2">{{ bayar.kas_bank?.nomor_rekening }}</p>
                                    
                                    <p v-if="bayar.catatan" class="text-xs text-gray-600 italic bg-white p-2 rounded border border-green-100 mb-3">
                                        "{{ bayar.catatan }}"
                                    </p>

                                    <div class="flex justify-between items-center border-t border-green-200 pt-2 border-dashed">
                                        <button v-if="bayar.bukti_bayar_path" @click="openPaymentProof(bayar)" class="text-xs font-bold text-indigo-700 underline flex items-center hover:opacity-80"><PaperClipIcon class="w-3 h-3 mr-1"/> Lihat Bukti</button>
                                        <span v-else class="text-xs text-gray-400">Tanpa Bukti</span>
                                        <span class="font-bold text-lg text-green-700">{{ formatCurrency(bayar.nominal_bayar) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="hidden md:block">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold"><tr><th class="px-6 py-3 text-left">Tanggal</th><th class="px-6 py-3 text-left">Sumber Dana</th><th class="px-6 py-3 text-left">Ref</th><th class="px-6 py-3 text-left">Bukti</th><th class="px-6 py-3 text-right">Nominal</th></tr></thead>
                                    <tbody class="divide-y divide-gray-100 text-sm"><tr v-for="bayar in pengajuan.pembayaran" :key="bayar.id" class="hover:bg-gray-50"><td class="px-6 py-3">{{ formatDate(bayar.tgl_bayar) }}</td><td class="px-6 py-3 text-gray-600">{{ bayar.kas_bank?.nama_bank }} <span class="text-xs text-gray-400 block">{{ bayar.kas_bank?.nomor_rekening }}</span></td><td class="px-6 py-3 italic text-gray-500">{{ bayar.catatan || '-' }}</td><td class="px-6 py-3"><button v-if="bayar.bukti_bayar_path" @click="openPaymentProof(bayar)" class="text-indigo-600 hover:underline text-xs font-bold flex items-center"><PaperClipIcon class="w-3 h-3 mr-1"/> Lihat</button><span v-else class="text-gray-300">-</span></td><td class="px-6 py-3 text-right font-bold text-green-700">{{ formatCurrency(bayar.nominal_bayar) }}</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="p-8 text-center text-gray-400 italic">Belum ada riwayat pembayaran dari Finance.</div>
                    </div>
                </div>

                <!-- 5. TAB CONTENT: ACTIVITY LOG -->
                <div v-show="activeTab === 'activity'" class="space-y-6">
                    <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
                        <ActivityLogList :activities="pengajuan.activities" />
                    </div>
                </div>

            </div>
        </div>

        <!-- FLOATING ACTION BARS -->
        <!-- A. PENERIMA (SETTLEMENT) -->
        <div v-if="canSettle && !pengajuan.laporan_penggunaan" class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 p-4 shadow-lg z-50 flex justify-between md:justify-end items-center gap-3 md:pr-12">
            <div class="flex flex-col md:flex-row md:items-center mr-4"><span class="text-sm text-gray-600 font-medium">Status: Menunggu Laporan</span><span class="text-xs text-gray-500 ml-2 hidden md:inline">Total Uang Muka: <b class="text-indigo-600">{{ formatCurrency(pengajuan.total_nominal_diajukan) }}</b></span></div>
            <Link :href="route('admin.settlement.create', pengajuan.id)"><PrimaryButton class="bg-purple-600 hover:bg-purple-700 border-purple-600 shadow-lg"><DocumentTextIcon class="w-5 h-5 mr-2"/> Buat Laporan</PrimaryButton></Link>
        </div>
        
        <!-- A2. PENERIMA (EDIT SETTLEMENT / REVISION) -->
        <div v-else-if="canEditSettlement" class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 p-4 shadow-lg z-50 flex justify-between md:justify-end items-center gap-3 md:pr-12 bg-pink-50 border-pink-200">
            <div class="flex flex-col md:flex-row md:items-center mr-4"><span class="text-sm text-pink-800 font-bold">Status: Perlu Revisi</span><span class="text-xs text-pink-600 ml-2 hidden md:inline">Silakan perbaiki laporan Anda.</span></div>
            <Link :href="route('admin.settlement.edit', pengajuan.id)"><PrimaryButton class="bg-pink-600 hover:bg-pink-700 border-pink-600 shadow-lg"><PencilSquareIcon class="w-5 h-5 mr-2"/> Edit Laporan</PrimaryButton></Link>
        </div>
        
        <!-- B. FINANCE: VERIFY SETTLEMENT -->
        <div v-else-if="canVerifySettlement" class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 p-4 shadow-lg z-50 flex justify-between items-center gap-3 md:px-12 bg-orange-50 border-orange-200">
            <div class="flex items-center text-orange-800 font-bold text-sm"><ExclamationCircleIcon class="w-5 h-5 mr-2"/> Verifikasi Laporan Pertanggungjawaban</div>
            <div class="flex gap-2"><SecondaryButton @click="rejectSettlement" class="border-red-500 text-red-600 hover:bg-red-50">Tolak & Revisi</SecondaryButton><PrimaryButton @click="verifySettlement" class="bg-green-600 border-green-600 hover:bg-green-700">Verifikasi & Selesai</PrimaryButton></div>
        </div>

        <!-- C. FINANCE: PAY -->
        <div v-else-if="canPay" class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 p-4 shadow-lg z-50 flex justify-between md:justify-end items-center gap-3 md:pr-12">
            <div class="flex flex-col md:flex-row md:items-center mr-4"><span class="text-sm text-gray-600 font-medium">Aksi Finance:</span><span class="text-xs text-gray-500 ml-2 hidden md:inline">Sisa Tagihan: <b class="text-red-600">{{ formatCurrency(pengajuan.sisa_tagihan) }}</b></span></div>
            <PrimaryButton @click="showPaymentModal = true" class="bg-indigo-600 hover:bg-indigo-700 border-indigo-600 shadow-lg"><BanknotesIcon class="w-5 h-5 mr-2"/> Input Pembayaran</PrimaryButton>
        </div>

        <!-- D. APPROVER -->
        <!-- D. APPROVER -->
        <div v-else-if="canApprove" class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-md border-t border-gray-200 p-4 shadow-2xl z-50 flex justify-between md:justify-end items-center gap-4 md:pr-12 animate-slide-up">
            <div class="hidden md:flex items-center text-gray-700 bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200">
                <span class="text-sm font-bold mr-2">Giliran Anda:</span>
                <span class="text-xs text-gray-500">Silakan tinjau pengajuan ini.</span>
            </div>
            
            <div class="flex gap-3 w-full md:w-auto">
                <button @click="showRejectModal = true" class="flex-1 md:flex-none flex items-center justify-center px-4 py-2.5 bg-red-50 text-red-600 font-bold rounded-xl border border-red-100 hover:bg-red-100 hover:border-red-200 hover:shadow-sm transition-all duration-200 active:scale-95">
                    <XCircleIcon class="w-5 h-5 mr-1.5"/> Tolak
                </button>
                <button @click="submitApprove" class="flex-1 md:flex-none flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-green-600 to-green-500 text-white font-bold rounded-xl shadow-lg shadow-green-200 hover:shadow-green-300 hover:scale-[1.02] transition-all duration-200 active:scale-95">
                    <CheckCircleIcon class="w-5 h-5 mr-1.5"/> Setujui
                </button>
            </div>
        </div>

        <!-- MODALS -->
        <!-- Modal Reject Settlement -->
        <Modal :show="showSettlementRejectModal" @close="showSettlementRejectModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-red-600 mb-4 flex items-center">
                    <ExclamationCircleIcon class="w-6 h-6 mr-2"/> Tolak Laporan
                </h2>
                
                <div class="mb-4">
                    <InputLabel value="Tipe Penolakan" />
                    <div class="mt-2 space-y-3">
                        <label class="flex items-start">
                            <input type="radio" v-model="settlementRejectForm.tipe_revisi" value="partial" class="mt-1 mr-2 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block text-sm font-bold text-gray-900">Revisi Sebagian (Disarankan)</span>
                                <span class="block text-xs text-gray-500">User hanya mengedit bagian yang salah. Data dan berkas laporan lama tidak dihapus.</span>
                            </div>
                        </label>
                        <label class="flex items-start">
                            <input type="radio" v-model="settlementRejectForm.tipe_revisi" value="full" class="mt-1 mr-2 text-red-600 focus:ring-red-500">
                            <div>
                                <span class="block text-sm font-bold text-gray-900">Revisi Full (Hapus Semua)</span>
                                <span class="block text-xs text-gray-500">Laporan akan dihapus total. User harus menginput ulang laporan dari 0.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <InputLabel value="Catatan / Alasan Penolakan" />
                    <textarea v-model="settlementRejectForm.catatan" class="w-full border-gray-300 rounded mt-1 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" rows="3" placeholder="Sebutkan bagian mana yang perlu diperbaiki..."></textarea>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <SecondaryButton @click="showSettlementRejectModal = false">Batal</SecondaryButton>
                    <DangerButton @click="submitSettlementReject" :disabled="settlementRejectForm.processing">
                        Kirim Penolakan
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showRejectModal" @close="showRejectModal = false">
            <div class="p-6"><h2 class="text-lg font-medium text-red-600 mb-4">Tolak Pengajuan</h2><div class="mb-4"><InputLabel value="Alasan" /><textarea v-model="approvalForm.catatan" class="w-full border-gray-300 rounded mt-1" rows="3"></textarea></div><div class="flex justify-end gap-2"><SecondaryButton @click="showRejectModal = false">Batal</SecondaryButton><DangerButton @click="submitReject" :disabled="approvalForm.processing">Tolak</DangerButton></div></div>
        </Modal>
        <PinModal 
            :show="showPinModal" 
            :mode="pinMode" 
            :title="pinMode === 'create' ? 'Buat PIN Keamanan' : 'Verifikasi PIN'"
            @close="showPinModal = false" 
            @success="onPinSuccess" 
        />
        <PaymentModal :show="showPaymentModal" :pengajuanId="pengajuan.id" :sisaTagihan="parseFloat(pengajuan.sisa_tagihan)" :listKasBank="listKasBank" @close="showPaymentModal = false" />
        <FilePreviewModal :show="showFilePreview" @close="showFilePreview = false" :fileUrl="activeFileUrl" :fileType="activeFileType" :fileName="activeFileName" />
    </AuthenticatedLayout>
</template>