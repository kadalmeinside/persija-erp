<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ActivityLogList from '@/Components/ActivityLogList.vue';
import { 
    ChevronLeftIcon, ClockIcon, DocumentTextIcon, 
    CheckCircleIcon, XCircleIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    pinjaman: Object,
    activities: Array,
    permissions: Object
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    // Gunakan format timestamp lengkap bila memungkinkan
    return new Date(dateString).toLocaleString('id-ID', { 
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const approvalForm = useForm({
    status: '',
    catatan: ''
});

const showRejectModal = ref(false);

const submitApprove = () => {
    if (confirm('Setujui pinjaman ini?')) {
        approvalForm.status = 'Approved';
        approvalForm.post(route('admin.pinjaman.action', props.pinjaman.id));
    }
};

const submitReject = () => {
    if (!approvalForm.catatan) return alert('Mohon isi alasan penolakan/revisi.');
    
    // Status can be 'Rejected' or 'Revision', handled dynamically based on what user clicked
    if (confirm(`Apakah Anda yakin ingin memproses aksi ini?`)) {
        approvalForm.post(route('admin.pinjaman.action', props.pinjaman.id), {
            onSuccess: () => { showRejectModal.value = false; }
        });
    }
};

const triggerReject = () => {
    approvalForm.status = 'Rejected';
    showRejectModal.value = true;
};

const triggerRevision = () => {
    approvalForm.status = 'Revision';
    showRejectModal.value = true;
};
</script>

<template>
    <Head title="Detail Pinjaman" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('admin.pinjaman.index')" class="mr-4 text-gray-500 hover:text-gray-700">
                    <ChevronLeftIcon class="w-5 h-5" />
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Detail Pinjaman #{{ pinjaman.id }}</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- INFO CARD -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Informasi Peminjam</h3>
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Nama Karyawan</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ pinjaman.karyawan?.nama_lengkap }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Departemen</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ pinjaman.karyawan?.departemen?.nama_departemen || '-' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Tanggal Pengajuan</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(pinjaman.tanggal_pengajuan) }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="mt-1">
                                            <span :class="{
                                                'px-2 py-1 text-xs rounded-full': true,
                                                'bg-yellow-100 text-yellow-800': pinjaman.status === 'Draft',
                                                'bg-green-100 text-green-800': pinjaman.status === 'Approved',
                                                'bg-blue-100 text-blue-800': pinjaman.status === 'Paid Off',
                                            }">
                                                {{ pinjaman.status }}
                                            </span>
                                        </dd>
                                    </div>
                                     <div class="sm:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">Keterangan</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ pinjaman.keterangan || '-' }}</dd>
                                    </div>
                                </dl>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Rincian Keuangan</h3>
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Jumlah Pinjaman</dt>
                                        <dd class="mt-1 text-lg font-bold text-gray-900">{{ formatCurrency(pinjaman.jumlah_pinjaman) }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Tenor</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ pinjaman.tenor_bulan }} Bulan</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Angsuran per Bulan</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatCurrency(pinjaman.jumlah_angsuran_per_bulan) }}</dd>
                                    </div>
                                    <div class="sm:col-span-1" v-if="pinjaman.approved_by">
                                        <dt class="text-sm font-medium text-gray-500">Disetujui Oleh</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ pinjaman.approver?.name }} <br>
                                            <span class="text-xs text-gray-500">{{ formatDate(pinjaman.approved_at) }}</span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- INSTALLMENT SCHEDULE -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Jadwal Angsuran</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payroll Link</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="angsuran in pinjaman.angsuran" :key="angsuran.id">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ angsuran.bulan_periode }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(angsuran.jumlah_angsuran) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'px-2 py-1 text-xs rounded-full': true,
                                                'bg-yellow-100 text-yellow-800': angsuran.status_bayar === 'Pending',
                                                'bg-green-100 text-green-800': angsuran.status_bayar === 'Paid',
                                            }">
                                                {{ angsuran.status_bayar }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span v-if="angsuran.id_payroll_detail">Linked (Payroll)</span>
                                            <span v-else>-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- APPROVAL TIMELINE -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b bg-gray-50 font-bold text-gray-700 flex items-center">
                        <ClockIcon class="w-5 h-5 mr-2 text-gray-400"/> Log Persetujuan
                    </div>
                    <div class="p-6">
                        <ol class="relative border-l border-gray-200 ml-3">
                            <li class="mb-6 ml-6">
                                <span class="absolute flex items-center justify-center w-6 h-6 bg-green-100 rounded-full -left-3 ring-4 ring-white"><DocumentTextIcon class="w-3 h-3 text-green-600"/></span>
                                <h3 class="flex items-center mb-1 text-sm font-semibold text-gray-900">Pinjaman Dibuat</h3>
                                <time class="block mb-2 text-xs font-normal leading-none text-gray-400">{{ formatDate(pinjaman.created_at) }}</time>
                                <p class="text-xs text-gray-500">Oleh: {{ pinjaman.karyawan?.nama_lengkap }} ({{ pinjaman.karyawan?.departemen?.nama_departemen }})</p>
                            </li>
                            <li v-if="!pinjaman.approval_process?.length" class="ml-6">
                                <span class="absolute flex items-center justify-center w-6 h-6 bg-gray-100 rounded-full -left-3 ring-4 ring-white"><span class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"></span></span>
                                <h3 class="text-sm font-medium text-red-500">Data Approval Tidak Ditemukan</h3>
                            </li>
                            <li v-else v-for="log in pinjaman.approval_process" :key="log.id" class="mb-6 ml-6">
                                <span class="absolute flex items-center justify-center w-6 h-6 rounded-full -left-3 ring-4 ring-white" :class="log.status === 'Approved' ? 'bg-green-100' : (log.status === 'Rejected' ? 'bg-red-100' : 'bg-yellow-100')">
                                    <component :is="log.status === 'Approved' ? CheckCircleIcon : (log.status === 'Rejected' ? XCircleIcon : ClockIcon)" class="w-3 h-3" :class="log.status === 'Approved' ? 'text-green-600' : (log.status === 'Rejected' ? 'text-red-600' : 'text-yellow-600')" />
                                </span>
                                <h3 class="mb-1 text-sm font-semibold text-gray-900">{{ log.label_aksi }}</h3>
                                <div v-if="['Approved','Rejected'].includes(log.status)">
                                    <time class="block mb-1 text-xs font-normal leading-none text-gray-400">{{ formatDate(log.tgl_aksi) }}</time>
                                    <p class="text-xs text-gray-500">Oleh: {{ log.action_karyawan?.nama_lengkap || log.target_karyawan?.nama_lengkap }}</p>
                                    <p v-if="log.catatan" class="text-xs italic text-gray-600 bg-gray-50 p-2 mt-1 rounded border">"{{ log.catatan }}"</p>
                                </div>
                                <div v-else-if="log.status === 'Pending'"><p class="text-xs text-gray-500 mb-1">Menunggu:</p><p class="text-xs font-medium text-gray-900">{{ log.target_karyawan?.nama_lengkap }}</p><div class="text-xs text-yellow-600 font-medium animate-pulse mt-1">Sedang diproses...</div></div>
                                <div v-else class="text-xs text-gray-400">Menunggu giliran...</div>
                            </li>
                        </ol>
                    </div>
                </div>

                <!-- AUDIT LOGS -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Riwayat Log (Audit Trail)</h3>
                        <ActivityLogList :activities="activities" />
                    </div>
                </div>

            </div>
        </div>
        
        <!-- FLOATING ACTION BAR FOR APPROVER -->
        <div v-if="permissions?.canApprove" class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-md border-t border-gray-200 p-4 shadow-2xl z-50 flex justify-between md:justify-end items-center gap-4 md:pr-12 animate-slide-up">
            <div class="hidden md:flex items-center text-gray-700 bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200">
                <span class="text-sm font-bold mr-2">Giliran Anda:</span>
                <span class="text-xs text-gray-500">Silakan tinjau pinjaman ini.</span>
            </div>
            
            <div class="flex gap-2 w-full md:w-auto">
                <button @click="triggerReject" class="flex-1 md:flex-none flex items-center justify-center px-4 py-2.5 bg-red-50 text-red-600 font-bold rounded-xl border border-red-100 hover:bg-red-100 transition-all">
                    <XCircleIcon class="w-5 h-5 mr-1.5"/> Tolak
                </button>
                <button @click="triggerRevision" class="flex-1 md:flex-none flex items-center justify-center px-4 py-2.5 bg-yellow-50 text-yellow-600 font-bold rounded-xl border border-yellow-100 hover:bg-yellow-100 transition-all">
                    Minta Revisi
                </button>
                <button @click="submitApprove" class="flex-1 md:flex-none flex items-center justify-center px-6 py-2.5 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 transition-all">
                    <CheckCircleIcon class="w-5 h-5 mr-1.5"/> Setujui
                </button>
            </div>
        </div>

        <!-- REJECT/REVISION MODAL -->
        <Modal :show="showRejectModal" @close="showRejectModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-red-600 mb-4">{{ approvalForm.status === 'Revision' ? 'Minta Revisi' : 'Tolak Pinjaman' }}</h2>
                <div class="mb-4">
                    <InputLabel value="Alasan" />
                    <textarea v-model="approvalForm.catatan" class="w-full border-gray-300 rounded mt-1" rows="3"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="showRejectModal = false">Batal</SecondaryButton>
                    <DangerButton @click="submitReject" :disabled="approvalForm.processing">Kirim</DangerButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
