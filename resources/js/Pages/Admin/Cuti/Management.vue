<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import { PlusIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { debounce } from 'lodash';

const props = defineProps({
    balancesList: Object, // Paginated
    allRequests: Object, // Paginated
    jenisCutiList: Array,
    activeTab: {
        type: String,
        default: 'balances'
    },
    filters: Object,
    summary: Object // New prop for summary metrics
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Search & Filter Logic
const search = ref(props.filters.search || '');
const year = ref(props.filters.year || new Date().getFullYear());

watch([search, year], debounce(([newSearch, newYear]) => {
    router.get(route('admin.cuti.management'), { 
        search: newSearch, 
        year: newYear,
        tab: props.activeTab
    }, { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true 
    });
}, 300));

const switchTab = (tab) => {
    router.get(route('admin.cuti.management'), { 
        tab: tab,
        search: search.value,
        year: year.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

// Generate Saldo Logic
const showGenerateModal = ref(false);
const generateForm = useForm({
    tahun: new Date().getFullYear(),
    id_jenis_cuti: ''
});

const submitGenerate = () => {
    generateForm.post(route('admin.cuti.generate'), {
        onSuccess: () => showGenerateModal.value = false
    });
};

// Edit Balance Logic
const showEditBalanceModal = ref(false);
const selectedBalance = ref(null);
const selectedKaryawan = ref(null);
const editBalanceForm = useForm({
    saldo_awal: 0,
    saldo_terpakai: 0
});

const openEditBalanceModal = (balance, karyawan) => {
    selectedBalance.value = balance;
    selectedKaryawan.value = karyawan;
    editBalanceForm.saldo_awal = balance.saldo_awal;
    editBalanceForm.saldo_terpakai = balance.saldo_terpakai;
    showEditBalanceModal.value = true;
};

const submitEditBalance = () => {
    editBalanceForm.put(route('admin.cuti.update-balance', selectedBalance.value.id), {
        onSuccess: () => showEditBalanceModal.value = false
    });
};
</script>

<template>
    <Head title="Manajemen Cuti" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Manajemen Cuti
            </h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Summary Metrics Cards -->
                <div v-if="summary" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <!-- Menunggu Persetujuan -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Menunggu Persetujuan</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ summary.pending_requests }}</p>
                            </div>
                            <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-500 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Sedang Cuti Hari Ini -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sedang Cuti (Hari Ini)</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ summary.on_leave_today }}</p>
                            </div>
                            <div class="p-3 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-500 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Disetujui Tahun Ini -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Disetujui ({{ year }})</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ summary.approved_this_year }}</p>
                            </div>
                            <div class="p-3 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-500 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Saldo Cuti Diterbitkan -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Saldo Diterbitkan</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ summary.total_balances_issued }}</p>
                            </div>
                            <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-500 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex space-x-4 mb-6 border-b border-gray-200 dark:border-gray-700">
                    <button @click="switchTab('balances')" :class="{'text-indigo-600 border-b-2 border-indigo-600': activeTab === 'balances', 'text-gray-500 hover:text-gray-700': activeTab !== 'balances'}" class="px-4 py-2 text-sm font-medium">
                        Saldo Cuti
                    </button>
                    <button @click="switchTab('history')" :class="{'text-indigo-600 border-b-2 border-indigo-600': activeTab === 'history', 'text-gray-500 hover:text-gray-700': activeTab !== 'history'}" class="px-4 py-2 text-sm font-medium">
                        Riwayat Pengajuan
                    </button>
                </div>

                <!-- Toolbar -->
                <div class="flex justify-between items-center mb-4">
                    <div class="flex gap-2 items-center">
                         <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <MagnifyingGlassIcon class="w-4 h-4 text-gray-500" />
                            </div>
                            <input 
                                v-model="search" 
                                type="text" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-64 pl-10 p-2.5" 
                                placeholder="Cari Karyawan..." 
                            />
                        </div>
                        <TextInput v-if="activeTab === 'balances'" v-model="year" type="number" class="w-24 text-sm" placeholder="Tahun" />
                    </div>
                    <PrimaryButton v-if="activeTab === 'balances'" @click="showGenerateModal = true">
                        Generate Saldo Massal
                    </PrimaryButton>
                </div>

                <!-- Tab: Balances -->
                <div v-if="activeTab === 'balances'">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="karyawan in balancesList.data" :key="karyawan.id" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col">
                            <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ karyawan.nama_lengkap }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ karyawan.departemen?.nama_departemen || 'Tanpa Departemen' }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ karyawan.saldo_cuti.length }} Tipe Cuti
                                    </span>
                                </div>
                            </div>
                            <div class="p-4 flex-1">
                                <div class="space-y-3">
                                    <div v-for="saldo in karyawan.saldo_cuti" :key="saldo.id" class="flex justify-between items-center group">
                                        <div>
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ saldo.jenis_cuti?.nama_cuti }}
                                                <span v-if="saldo.jenis_cuti?.is_unlimited" class="ml-1 text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded uppercase">Tidak Terbatas</span>
                                            </p>
                                            <p class="text-xs text-gray-500" v-if="!saldo.jenis_cuti?.is_unlimited">
                                                Awal: {{ saldo.saldo_awal }} &bull; Terpakai: {{ saldo.saldo_terpakai }}
                                            </p>
                                            <p class="text-xs text-gray-500" v-else>
                                                Total Terpakai: {{ saldo.saldo_terpakai }} Hari
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span v-if="!saldo.jenis_cuti?.is_unlimited" :class="[saldo.saldo_akhir <= 3 ? 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400' : 'text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-400']" class="flex items-center justify-center w-12 h-7 rounded md:rounded-md text-sm font-bold">
                                                {{ saldo.saldo_akhir }}
                                            </span>
                                            <span v-else class="text-gray-500 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 flex items-center justify-center w-12 h-7 rounded md:rounded-md text-lg font-bold">
                                                &infin;
                                            </span>
                                            
                                            <!-- Tombol edit selalu dirender agar layout gap tidak bergeser, tapi invisible jika unlimited -->
                                            <button 
                                                :disabled="saldo.jenis_cuti?.is_unlimited"
                                                @click="!saldo.jenis_cuti?.is_unlimited && openEditBalanceModal(saldo, karyawan)" 
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-opacity"
                                                :class="saldo.jenis_cuti?.is_unlimited ? 'invisible' : 'opacity-0 group-hover:opacity-100'"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="balancesList.data.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <p class="text-gray-500 dark:text-gray-400">Belum ada data saldo cuti untuk tahun ini.</p>
                    </div>

                    <Pagination :links="balancesList.links" class="mt-6" />
                </div>

                <!-- Tab: History (All Requests) -->
                <div v-if="activeTab === 'history'">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Jenis Cuti</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Durasi</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Approver</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="req in allRequests.data" :key="req.id">
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ req.approver?.name || '-' }}
                                    </td>
                                </tr>
                                <tr v-if="allRequests.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data pengajuan.</td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination :links="allRequests.links" class="mt-4 p-4" />
                    </div>
                </div>

            </div>
        </div>

        <!-- Generate Saldo Modal -->
        <Modal :show="showGenerateModal" @close="showGenerateModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Generate Saldo Cuti Massal</h2>
                
                <div v-if="jenisCutiList && jenisCutiList.length > 0">
                    <form @submit.prevent="submitGenerate">
                        <div class="mb-4">
                            <InputLabel value="Tahun Periode" />
                            <TextInput v-model="generateForm.tahun" type="number" class="mt-1 block w-full" required />
                        </div>
                        <div class="mb-6">
                            <InputLabel value="Jenis Cuti" />
                            <select v-model="generateForm.id_jenis_cuti" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                                <option v-for="jc in jenisCutiList" :key="jc.id" :value="jc.id">{{ jc.nama_cuti }} (Default: {{ jc.kuota_default }} Hari)</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-4">
                            <SecondaryButton @click="showGenerateModal = false">Batal</SecondaryButton>
                            <PrimaryButton :disabled="generateForm.processing">Generate</PrimaryButton>
                        </div>
                    </form>
                </div>
                <div v-else class="text-center py-4">
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Belum ada tipe cuti yang tersedia.</p>
                    <Link :href="route('admin.jenis-cuti.index')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <PlusIcon class="w-4 h-4 mr-2" /> Tambah Tipe Cuti
                    </Link>
                </div>
            </div>
        </Modal>

        <!-- Edit Balance Modal -->
        <Modal :show="showEditBalanceModal" @close="showEditBalanceModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Edit Saldo Cuti</h2>
                <div v-if="selectedKaryawan && selectedBalance" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ selectedKaryawan.nama_lengkap }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Tipe: <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ selectedBalance.jenis_cuti?.nama_cuti }}</span> &bull; Tahun: {{ selectedBalance.tahun_periode }}</p>
                </div>
                <form @submit.prevent="submitEditBalance">
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <InputLabel value="Saldo Awal" />
                            <TextInput v-model="editBalanceForm.saldo_awal" type="number" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Saldo Terpakai" />
                            <TextInput v-model="editBalanceForm.saldo_terpakai" type="number" class="mt-1 block w-full" required />
                        </div>
                    </div>
                    <div class="flex justify-end gap-4">
                        <SecondaryButton @click="showEditBalanceModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="editBalanceForm.processing">Simpan Perubahan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
