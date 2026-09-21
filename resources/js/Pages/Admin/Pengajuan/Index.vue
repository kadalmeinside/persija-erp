<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { EyeIcon, PencilSquareIcon, PlusIcon, MagnifyingGlassIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';

import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PinModal from '@/Components/PinModal.vue'; // Import PIN Modal
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    pengajuan: Object,
    filters: Object,
    activeTab: {
        type: String,
        default: 'my-requests'
    },
    approvalView: {
        type: String,
        default: 'pending' // pending | history
    },
    pin_session_valid: Boolean // Add Prop
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const currentApprovalView = ref(props.approvalView); // Local state for tab

// Approval Modal State
const showApprovalModal = ref(false);
const selectedPengajuan = ref(null);
const approvalForm = useForm({
    status: '',
    catatan: ''
});

// PIN State
const showPinModal = ref(false);
const isPinVerified = ref(props.pin_session_valid);

const openApprovalModal = (item, status) => {
    selectedPengajuan.value = item;
    approvalForm.status = status;
    approvalForm.catatan = '';
    showApprovalModal.value = true;
};

const closeApprovalModal = () => {
    showApprovalModal.value = false;
    selectedPengajuan.value = null;
    approvalForm.reset();
};

const handlePinSuccess = () => {
    isPinVerified.value = true;
    showPinModal.value = false;
    submitApproval(); // Retry submission
};

const submitApproval = () => {
    if (!selectedPengajuan.value) return;

    // Check PIN first
    if (!isPinVerified.value) {
        showPinModal.value = true;
        return;
    }
    
    approvalForm.post(route('admin.pengajuan.action', selectedPengajuan.value.id), {
        onSuccess: () => closeApprovalModal(),
        preserveScroll: true
    });
};

const switchApprovalView = (view) => {
    currentApprovalView.value = view;
    router.get(route('admin.pengajuan.approvals'), { 
        view: view,
        search: search.value,
        status: statusFilter.value
    }, { preserveState: true, preserveScroll: true });
};

const tipeFilter = ref(props.filters.tipe || '');
const laporanFilter = ref(props.filters.laporan || '');
const perPageFilter = ref(props.filters.per_page || '15');

// Debounce search
watch([search, statusFilter, tipeFilter, laporanFilter, perPageFilter], debounce(([newSearch, newStatus, newTipe, newLaporan, newPerPage]) => {
    // Determine route based on activeTab
    let routeName = 'admin.pengajuan.my-requests';
    let params = { search: newSearch, status: newStatus, tipe: newTipe, laporan: newLaporan, per_page: newPerPage };

    if (props.activeTab === 'approvals') {
        routeName = 'admin.pengajuan.approvals';
        params.view = currentApprovalView.value; // Maintain view state
    }
    if (props.activeTab === 'all-requests') routeName = 'admin.pengajuan.index';

    router.get(route(routeName), params, { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true 
    });
}, 300));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const statusBadge = (status) => {
    switch (status) {
        case 'Pending Approval': return 'bg-yellow-100 text-yellow-800';
        case 'Approved': return 'bg-blue-100 text-blue-800';
        case 'Paid': return 'bg-green-100 text-green-800';
        case 'Settled': return 'bg-green-100 text-green-800';
        case 'Rejected': return 'bg-red-100 text-red-800';
        case 'Revision': return 'bg-pink-100 text-pink-800';
        case 'Verification': return 'bg-indigo-100 text-indigo-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getPageTitle = () => {
    if (props.activeTab === 'my-requests') return 'Pengajuan Saya';
    if (props.activeTab === 'approvals') return 'Persetujuan Pengajuan';
    return 'Semua Pengajuan';
};
</script>

<template>
    <Head :title="getPageTitle()" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-lg md:text-xl text-gray-800 leading-tight truncate">{{ getPageTitle() }}</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <!-- Tabs for Approval Page Only -->
                        <div v-if="activeTab === 'approvals'" class="flex border-b border-gray-200 mb-6">
                            <button 
                                @click="switchApprovalView('pending')" 
                                :class="currentApprovalView === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="w-1/2 md:w-auto py-3 px-4 text-center border-b-2 font-medium text-sm transition-colors duration-200">
                                Perlu Persetujuan
                                <span v-if="currentApprovalView === 'pending' && pengajuan.total > 0" class="ml-2 bg-indigo-100 text-indigo-600 py-0.5 px-2 rounded-full text-xs">
                                    {{ pengajuan.total }}
                                </span>
                            </button>
                            <button 
                                @click="switchApprovalView('history')" 
                                :class="currentApprovalView === 'history' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="w-1/2 md:w-auto py-3 px-4 text-center border-b-2 font-medium text-sm transition-colors duration-200">
                                Riwayat
                            </button>
                        </div>

                        <!-- Toolbar: Search, Filter, Create Button -->
                        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                                <!-- Search -->
                                <div class="relative w-full md:w-64">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <MagnifyingGlassIcon class="w-4 h-4 text-gray-500" />
                                    </div>
                                    <input 
                                        v-model="search" 
                                        type="text" 
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-9 p-2" 
                                        placeholder="Cari No. Pengajuan / Nama..." 
                                    />
                                </div>
                                <!-- Filter Tipe -->
                                <select v-model="tipeFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block py-2 pl-3 pr-8">
                                    <option value="">Semua Tipe</option>
                                    <option value="Langsung">Langsung</option>
                                    <option value="UangMuka">Uang Muka</option>
                                </select>
                                <!-- Filter Laporan -->
                                <select v-model="laporanFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block py-2 pl-3 pr-8">
                                    <option value="">Status Laporan</option>
                                    <option value="butuh">Butuh Laporan</option>
                                    <option value="ada">Sudah Lapor</option>
                                </select>
                                <!-- Filter Status -->
                                <select v-model="statusFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block py-2 pl-3 pr-8">
                                    <option value="">Semua Status</option>
                                    <option value="Pending Approval">Pending Approval</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Revision">Revision</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                                <!-- Per Page -->
                                <select v-model="perPageFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block py-2 pl-3 pr-8">
                                    <option value="15">15 Baris</option>
                                    <option value="30">30 Baris</option>
                                    <option value="50">50 Baris</option>
                                    <option value="100">100 Baris</option>
                                </select>
                            </div>

                            <!-- Create Button (Only for My Requests) -->
                            <Link 
                                v-if="activeTab === 'my-requests'"
                                :href="route('admin.pengajuan.create')" 
                                class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <PlusIcon class="w-4 h-4 mr-1" />
                                Buat Pengajuan
                            </Link>
                        </div>

                        <!-- Notifikasi -->
                        <div v-if="$page.props.flash.success" class="mb-4 p-3 bg-green-100 text-green-700 text-sm rounded-md">
                            {{ $page.props.flash.success }}
                        </div>
                        <div v-if="$page.props.flash.error" class="mb-4 p-3 bg-red-100 text-red-700 text-sm rounded-md">
                            {{ $page.props.flash.error }}
                        </div>

                                <!-- Mobile Card View -->
                        <div class="block md:hidden space-y-4">
                            <div v-if="pengajuan.data.length === 0" class="text-center text-gray-500 text-sm py-4">
                                Tidak ada data ditemukan.
                            </div>
                            <div v-for="item in pengajuan.data" :key="item.id" class="bg-white p-4 rounded-lg shadow border border-gray-100 dark:border-gray-700">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1 pr-2">
                                        <!-- Main Title is now Keperluan (Judul) -->
                                        <Link :href="route('admin.pengajuan.show', item.id)" class="text-indigo-600 font-bold text-sm block leading-tight mb-1">
                                            {{ item.judul_pengajuan || '(Tanpa Judul)' }}
                                        </Link>
                                        <!-- Subtitle: No & Tanggal -->
                                        <div class="text-xs text-gray-500 font-mono">
                                            {{ item.nomor_pengajuan }} • {{ formatDate(item.tgl_pengajuan) }}
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span :class="statusBadge(item.status_global)" class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-semibold rounded-full whitespace-nowrap">
                                            {{ item.status_global }}
                                        </span>
                                        <span v-if="item.is_butuh_laporan" class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-semibold rounded-full bg-orange-100 text-orange-800 whitespace-nowrap border border-orange-200">
                                            Butuh Laporan
                                        </span>
                                    </div>
                                </div>
                                <div class="space-y-1 mb-3">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-500">Pengaju:</span>
                                        <span class="font-medium text-gray-700">{{ item.pengaju.nama_lengkap }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-500">Tipe:</span>
                                        <span class="font-medium text-gray-700">{{ item.tipe_pengajuan }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-500">Total:</span>
                                        <span class="font-bold text-gray-900">{{ formatCurrency(item.total_nominal_diajukan) }}</span>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col gap-2 mt-3 border-t pt-3 border-dashed border-gray-100">
                                    <!-- Approval Actions (Prioritized) -->
                                    <template v-if="( (activeTab === 'approvals' && currentApprovalView === 'pending') || item.can_approve ) && item.status_global === 'Pending Approval'">
                                        <div class="grid grid-cols-2 gap-3">
                                            <button @click="openApprovalModal(item, 'Rejected')" class="flex items-center justify-center py-2 px-4 bg-red-50 text-red-700 font-bold text-xs rounded-lg border border-red-100 hover:bg-red-100 transition">
                                                <XCircleIcon class="w-4 h-4 mr-1.5" /> Tolak
                                            </button>
                                            <button @click="openApprovalModal(item, 'Approved')" class="flex items-center justify-center py-2 px-4 bg-green-600 text-white font-bold text-xs rounded-lg shadow-sm hover:bg-green-700 transition">
                                                <CheckCircleIcon class="w-4 h-4 mr-1.5" /> Setujui
                                            </button>
                                        </div>
                                    </template>

                                    <!-- Other Actions -->
                                    <div class="flex gap-2">
                                        <Link :href="route('admin.pengajuan.show', item.id)" class="flex-1 flex items-center justify-center py-2 px-4 bg-white text-gray-700 font-bold text-xs rounded-lg border border-gray-300 shadow-sm hover:bg-gray-50 transition">
                                           <EyeIcon class="w-4 h-4 mr-1.5" /> Lihat Detail
                                        </Link>
                                        
                                        <Link 
                                            v-if="item.status_global === 'Pending Approval' && $page.props.auth.user.permissions.includes('pengajuan.create')" 
                                            :href="route('admin.pengajuan.edit', item.id)" 
                                            class="flex-none flex items-center justify-center py-2 px-3 bg-white text-yellow-600 font-bold text-xs rounded-lg border border-gray-300 shadow-sm hover:bg-yellow-50 transition" 
                                            title="Edit">
                                            <PencilSquareIcon class="w-4 h-4" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Compact (Desktop) -->
                        <div class="hidden md:block bg-white overflow-hidden shadow-sm rounded-lg overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <!-- Merged Nomor & Judul -->
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pengajuan</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pengaju</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Departemen</th>
                                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe</th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-if="pengajuan.data.length === 0">
                                        <td colspan="8" class="px-4 py-4 text-center text-xs text-gray-500">
                                            Tidak ada data ditemukan.
                                        </td>
                                    </tr>
                                    <tr v-for="item in pengajuan.data" :key="item.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <Link :href="route('admin.pengajuan.show', item.id)" class="text-sm font-bold text-indigo-600 hover:text-indigo-900 block truncate max-w-xs">
                                                {{ item.judul_pengajuan || '(Tanpa Judul)' }}
                                            </Link>
                                            <div class="text-xs text-gray-500 font-mono">{{ item.nomor_pengajuan }}</div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500">{{ formatDate(item.tgl_pengajuan) }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-700">{{ item.pengaju.nama_lengkap }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-500">{{ item.departemen.nama_departemen }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center text-xs text-gray-500">
                                            <span class="px-2 py-0.5 rounded border text-[10px]" :class="item.tipe_pengajuan === 'Langsung' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-purple-50 text-purple-600 border-purple-100'">
                                                {{ item.tipe_pengajuan }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-right text-xs font-bold text-gray-900">{{ formatCurrency(item.total_nominal_diajukan) }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center">
                                            <div class="flex flex-col items-center gap-1">
                                                <span :class="statusBadge(item.status_global)" class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-semibold rounded-full">
                                                    {{ item.status_global }}
                                                </span>
                                                <span v-if="item.is_butuh_laporan" class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-semibold rounded-full bg-orange-100 text-orange-800 border border-orange-200">
                                                    Butuh Laporan
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-right text-xs font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <!-- Approval Actions (Only if Authorized & Pending) -->
                                                <template v-if="( (activeTab === 'approvals' && currentApprovalView === 'pending') || item.can_approve ) && item.status_global === 'Pending Approval'">
                                                    <button @click="openApprovalModal(item, 'Approved')" class="text-green-600 hover:text-green-900" title="Setujui">
                                                        <CheckCircleIcon class="w-5 h-5" />
                                                    </button>
                                                    <button @click="openApprovalModal(item, 'Rejected')" class="text-red-600 hover:text-red-900" title="Tolak">
                                                        <XCircleIcon class="w-5 h-5" />
                                                    </button>
                                                </template>

                                                <Link :href="route('admin.pengajuan.show', item.id)" class="text-indigo-600 hover:text-indigo-900" title="Lihat Detail">
                                                    <EyeIcon class="w-5 h-5" />
                                                </Link>
                                                
                                                <Link 
                                                    v-if="item.status_global === 'Pending Approval' && $page.props.auth.user.permissions.includes('pengajuan.create')" 
                                                    :href="route('admin.pengajuan.edit', item.id)" 
                                                    class="text-gray-400 hover:text-gray-700" 
                                                    title="Edit">
                                                    <PencilSquareIcon class="w-5 h-5" />
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <Pagination :links="pengajuan.links" class="mt-4" />

                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Modal -->
        <Modal :show="showApprovalModal" @close="closeApprovalModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    {{ approvalForm.status === 'Approved' ? 'Setujui' : 'Tolak' }} Pengajuan
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Anda akan {{ approvalForm.status === 'Approved' ? 'menyetujui' : 'menolak' }} pengajuan 
                    <span class="font-bold">{{ selectedPengajuan?.nomor_pengajuan }}</span> 
                    dari <span class="font-bold">{{ selectedPengajuan?.pengaju?.nama_lengkap }}</span>.
                </p>
                
                <div class="mb-4">
                    <InputLabel value="Catatan (Opsional)" />
                    <TextInput v-model="approvalForm.catatan" type="text" class="mt-1 block w-full" placeholder="Tambahkan catatan..." />
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="closeApprovalModal">Batal</SecondaryButton>
                    <PrimaryButton 
                        :class="{ 'bg-red-600 hover:bg-red-700': approvalForm.status === 'Rejected' }"
                        :disabled="approvalForm.processing"
                        @click="submitApproval">
                        Konfirmasi {{ approvalForm.status === 'Approved' ? 'Setuju' : 'Tolak' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- PIN Verification Modal -->
        <PinModal 
            :show="showPinModal" 
            mode="verify"
            title="Verifikasi PIN"
            @close="showPinModal = false" 
            @success="handlePinSuccess" 
        />
    </AuthenticatedLayout>
</template>