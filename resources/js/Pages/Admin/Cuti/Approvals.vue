<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3'; // Added router
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PinModal from '@/Components/PinModal.vue';
import axios from 'axios';
import { onMounted } from 'vue';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    approvalRequests: Object,
    approvalView: {
        type: String,
        default: 'pending'
    }
});

const currentView = ref(props.approvalView);

const switchView = (view) => {
    currentView.value = view;
    router.get(route('admin.cuti.approvals'), { 
        view: view 
    }, { 
        preserveState: true, 
        preserveScroll: true 
    });
};

const hasPin = ref(false);
const pinMode = ref('verify');

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

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Approval Logic
const showApprovalModal = ref(false);
const showPinModal = ref(false);
const selectedRequest = ref(null);
const approvalForm = useForm({
    status: '',
    catatan: ''
});
const hasCheckedAttachment = ref(false);

const openApprovalModal = (request, status) => {
    selectedRequest.value = request;
    approvalForm.status = status;
    approvalForm.catatan = '';
    hasCheckedAttachment.value = false;
    showApprovalModal.value = true;
};

const submitApproval = () => {
    // Check PIN first
    showApprovalModal.value = false; // Close approval modal temporarily
    
    if (!hasPin.value) {
        pinMode.value = 'create';
    } else {
        pinMode.value = 'verify';
    }
    showPinModal.value = true;
};



const onPinSuccess = () => {
    showPinModal.value = false;
    
    // If we just created a PIN, we should update status and maybe ask to verify again? 
    // Or just proceed? Let's proceed if it was verify.
    // If it was create, we might want them to verify once to be sure, or just proceed.
    // My PinModal 'create' mode already confirms it.
    
    if (pinMode.value === 'create') {
        hasPin.value = true;
        alert('PIN berhasil dibuat. Silakan lanjutkan approval.');
        // Re-open approval modal or just submit? 
        // Let's re-open approval modal to confirm action.
        showApprovalModal.value = true;
    } else {
        // Verify success
        if (selectedRequest.value) {
             approvalForm.post(route('admin.cuti.approve', selectedRequest.value.id), {
                onSuccess: () => showApprovalModal.value = false
            });
        }
    }
};
</script>

<template>
    <Head title="Persetujuan Cuti" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Persetujuan Cuti
            </h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">

                <!-- Tabs -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6 mx-4 md:mx-0">
                    <button 
                        @click="switchView('pending')" 
                        :class="currentView === 'pending' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                        class="w-1/2 md:w-auto py-3 px-4 text-center border-b-2 font-medium text-sm transition-colors duration-200">
                        Perlu Persetujuan
                        <span v-if="currentView === 'pending' && approvalRequests.data && approvalRequests.data.length > 0" class="ml-2 bg-indigo-100 text-indigo-600 py-0.5 px-2 rounded-full text-xs">
                            {{ approvalRequests.total || approvalRequests.data.length }}
                        </span>
                    </button>
                    <button 
                        @click="switchView('history')" 
                        :class="currentView === 'history' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                        class="w-1/2 md:w-auto py-3 px-4 text-center border-b-2 font-medium text-sm transition-colors duration-200">
                        Riwayat
                    </button>
                </div>
                
                <!-- Mobile Card View -->
                <div class="block md:hidden space-y-4">
                     <div v-if="!approvalRequests.data || approvalRequests.data.length === 0" class="text-center text-gray-500 text-sm py-4">
                        Tidak ada pengajuan untuk saat ini.
                     </div>
                     <div v-for="req in approvalRequests.data" :key="req.id" class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow space-y-3 border border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between items-start">
                             <div>
                                <div class="font-bold text-gray-900 dark:text-white mb-1">{{ req.karyawan.nama_lengkap }}</div>
                                <div class="text-xs text-gray-500">{{ req.karyawan.departemen?.nama_departemen }}</div>
                             </div>
                             <span :class="{
                                'bg-yellow-100 text-yellow-800': req.status === 'Pending' || req.status === 'Pending Approval',
                                'bg-green-100 text-green-800': req.status === 'Approved',
                                'bg-red-100 text-red-800': req.status === 'Rejected',
                            }" class="px-2 py-1 text-xs font-semibold rounded-full">
                                {{ req.status }}
                            </span>
                        </div>
                        
                        <div class="border-t pt-2 mt-2 space-y-2 dark:border-gray-700">
                             <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Jenis Cuti:</span>
                                <div class="text-right">
                                    <span class="font-medium text-gray-900 dark:text-gray-100 block">{{ req.jenis_cuti.nama_cuti }}</span>
                                    <a v-if="req.lampiran_path" :href="'/storage/' + req.lampiran_path" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 flex items-center justify-end mt-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 mr-1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                        </svg>
                                        Lampiran
                                    </a>
                                </div>
                             </div>
                             <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tanggal:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(req.tgl_mulai) }} - {{ formatDate(req.tgl_selesai) }}</span>
                             </div>
                             <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Durasi:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ req.jumlah_hari }} Hari</span>
                             </div>
                        </div>

                        <div v-if="currentView === 'pending'" class="flex justify-end gap-3 mt-2 pt-2 border-t dark:border-gray-700">
                            <button @click="openApprovalModal(req, 'Approved')" class="flex items-center gap-1 bg-green-100 text-green-700 px-3 py-1.5 rounded-lg text-sm font-medium">
                                <CheckCircleIcon class="w-5 h-5" /> Setuju
                            </button>
                            <button @click="openApprovalModal(req, 'Rejected')" class="flex items-center gap-1 bg-red-100 text-red-700 px-3 py-1.5 rounded-lg text-sm font-medium">
                                <XCircleIcon class="w-5 h-5" /> Tolak
                            </button>
                        </div>
                     </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Karyawan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Jenis Cuti</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Durasi</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="req in approvalRequests.data" :key="req.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ req.karyawan.nama_lengkap }}<br>
                                    <span class="text-xs text-gray-500">{{ req.karyawan.departemen?.nama_departemen }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ req.jenis_cuti.nama_cuti }}
                                    <div v-if="req.lampiran_path" class="mt-1">
                                        <a :href="'/storage/' + req.lampiran_path" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3 h-3 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                            </svg>
                                            Lihat Lampiran
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(req.tgl_mulai) }} - {{ formatDate(req.tgl_selesai) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-white">{{ req.jumlah_hari }} Hari</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span :class="{
                                        'bg-yellow-100 text-yellow-800': req.status === 'Pending' || req.status === 'Pending Approval',
                                        'bg-green-100 text-green-800': req.status === 'Approved',
                                        'bg-red-100 text-red-800': req.status === 'Rejected',
                                    }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ req.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <template v-if="currentView === 'pending'">
                                        <button @click="openApprovalModal(req, 'Approved')" class="text-green-600 hover:text-green-900" title="Setujui">
                                            <CheckCircleIcon class="w-6 h-6" />
                                        </button>
                                        <button @click="openApprovalModal(req, 'Rejected')" class="text-red-600 hover:text-red-900" title="Tolak">
                                            <XCircleIcon class="w-6 h-6" />
                                        </button>
                                    </template>
                                    <span v-else class="text-gray-400 text-xs italic">
                                        Sudah direspons
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!approvalRequests.data || approvalRequests.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Tidak ada pengajuan untuk saat ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination v-if="approvalRequests.data && approvalRequests.data.length > 0" :links="approvalRequests.links" class="p-6 border-t border-gray-100 dark:border-gray-700" />
                </div>

            </div>
        </div>

        <!-- Approval Modal -->
        <Modal :show="showApprovalModal" @close="showApprovalModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    {{ approvalForm.status === 'Approved' ? 'Setujui' : 'Tolak' }} Pengajuan Cuti
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Anda akan {{ approvalForm.status === 'Approved' ? 'menyetujui' : 'menolak' }} pengajuan cuti dari <b>{{ selectedRequest?.karyawan.nama_lengkap }}</b>.
                </p>
                
                <div v-if="selectedRequest?.lampiran_path" class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <p class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3">Dokumen Pendukung (Surat Dokter / Lampiran)</p>
                    <a :href="'/storage/' + selectedRequest.lampiran_path" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white hover:bg-blue-700 transition-colors mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                        </svg>
                        Buka & Lihat Lampiran
                    </a>
                    
                    <label class="flex items-start mt-2 cursor-pointer">
                        <input type="checkbox" v-model="hasCheckedAttachment" class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Saya mengonfirmasi sudah mengecek dan memvalidasi keabsahan lampiran ini.</span>
                    </label>
                </div>

                <form @submit.prevent="submitApproval">
                    <div class="mb-6">
                        <InputLabel value="Catatan (Opsional)" />
                        <TextInput v-model="approvalForm.catatan" type="text" class="mt-1 block w-full" placeholder="Alasan persetujuan/penolakan..." />
                    </div>

                    <div class="flex justify-end gap-4">
                        <SecondaryButton @click="showApprovalModal = false">Batal</SecondaryButton>
                        <PrimaryButton :class="{ 'bg-red-600 hover:bg-red-700': approvalForm.status === 'Rejected' }" :disabled="approvalForm.processing || (selectedRequest?.lampiran_path && !hasCheckedAttachment)">
                            Konfirmasi {{ approvalForm.status === 'Approved' ? 'Setuju' : 'Tolak' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- PIN Verification Modal -->
        <PinModal 
            :show="showPinModal" 
            :mode="pinMode" 
            :title="pinMode === 'create' ? 'Buat PIN Keamanan' : 'Verifikasi PIN'"
            @close="showPinModal = false" 
            @success="onPinSuccess" 
        />

    </AuthenticatedLayout>
</template>
