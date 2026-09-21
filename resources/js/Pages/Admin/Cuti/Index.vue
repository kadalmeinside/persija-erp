<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onUnmounted } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    myRequests: Object,
    balances: Array,
    jenisCutiList: Array,
    isEmployee: Boolean,
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Create Request Logic
const showCreateModal = ref(false);
const closeModal = () => {
    if (createForm.processing) return;
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};
const createForm = useForm({
    id_jenis_cuti: '',
    tgl_mulai: '',
    tgl_selesai: '',
    alasan: '',
    lampiran: null
});

const availableJenisCuti = computed(() => {
    const list = [];
    if (props.balances) {
        props.balances.forEach(b => {
            if (!list.find(item => item.id === b.id_jenis_cuti)) {
                list.push(b.jenis_cuti);
            }
        });
    }
    if (props.jenisCutiList) {
        props.jenisCutiList.forEach(jc => {
            if (jc.is_unlimited && !list.find(item => item.id === jc.id)) {
                list.push(jc);
            }
        });
    }
    return list;
});

const isLampiranRequired = computed(() => {
    if (!createForm.id_jenis_cuti) return false;
    const jc = props.jenisCutiList?.find(j => j.id === createForm.id_jenis_cuti);
    return jc ? !!jc.wajib_lampiran : false;
});

const submitCreate = () => {
    createForm.clearErrors();
    let hasError = false;

    // Client-side Validation
    if (!createForm.id_jenis_cuti) {
        createForm.setError('id_jenis_cuti', 'Jenis cuti wajib dipilih.');
        hasError = true;
    }
    if (!createForm.tgl_mulai) {
        createForm.setError('tgl_mulai', 'Tanggal mulai wajib diisi.');
        hasError = true;
    }
    if (!createForm.tgl_selesai) {
        createForm.setError('tgl_selesai', 'Tanggal selesai wajib diisi.');
        hasError = true;
    }
    if (!createForm.alasan) {
        createForm.setError('alasan', 'Alasan wajib diisi.');
        hasError = true;
    }
    if (isLampiranRequired.value && !createForm.lampiran) {
        createForm.setError('lampiran', 'Lampiran (Surat Dokter) wajib diunggah untuk jenis cuti ini.');
        hasError = true;
    }

    if (createForm.tgl_mulai && createForm.tgl_selesai) {
        if (createForm.tgl_mulai > createForm.tgl_selesai) {
            createForm.setError('tgl_mulai', 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
            hasError = true;
        } else {
            // Check overlap
            const selectedStart = new Date(createForm.tgl_mulai).setHours(0,0,0,0);
            const selectedEnd = new Date(createForm.tgl_selesai).setHours(0,0,0,0);
            
            const hasOverlap = props.myRequests?.some(req => {
                if (['Pending', 'Pending Approval', 'Approved'].includes(req.status)) {
                    const reqStart = new Date(req.tgl_mulai).setHours(0,0,0,0);
                    const reqEnd = new Date(req.tgl_selesai).setHours(0,0,0,0);
                    return selectedStart <= reqEnd && selectedEnd >= reqStart;
                }
                return false;
            });

            if (hasOverlap) {
                createForm.setError('tgl_mulai', 'Tanggal sudah diajukan (beririsan dengan pengajuan lain yang Pending/Disetujui).');
                createForm.setError('tgl_selesai', 'Silakan pilih tanggal lain.');
                hasError = true;
            }
        }
    }

    if (hasError) return;

    createForm.post(route('admin.cuti.store'), {
        onSuccess: () => {
            closeModal();
        }
    });
};

const handleBeforeUnload = (e) => {
    if (createForm.processing) {
        e.preventDefault();
        e.returnValue = '';
    }
};

watch(() => createForm.processing, (isProcessing) => {
    if (isProcessing) {
        window.addEventListener('beforeunload', handleBeforeUnload);
    } else {
        window.removeEventListener('beforeunload', handleBeforeUnload);
    }
});

// Prevent Inertia navigation as well
router.on('before', (event) => {
    if (createForm.processing) {
        event.preventDefault();
        alert('Proses pengajuan sedang berlangsung. Harap tunggu hingga selesai.');
    }
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>

<template>
    <Head title="Cuti Saya" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Cuti Saya
            </h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                
                <!-- Balance Cards -->
                <div v-if="balances && balances.length > 0" class="flex md:grid md:grid-cols-3 overflow-x-auto md:overflow-visible gap-4 pb-4 md:pb-0 mb-6 snap-x snap-mandatory px-4 md:px-0 -mx-4 md:mx-0 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    <div v-for="(balance, index) in balances" :key="balance.id" 
                        class="flex-shrink-0 w-48 md:w-auto snap-center bg-gradient-to-br from-white rounded-xl shadow-sm p-3 md:p-4 relative overflow-hidden group hover:shadow-md transition-all border"
                        :class="[
                            index % 2 === 0 ? 'to-indigo-50 border-indigo-100' : 'to-emerald-50 border-emerald-100'
                        ]"
                    >
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 opacity-50 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-500"
                             :class="[
                                index % 2 === 0 ? 'bg-indigo-100' : 'bg-emerald-100'
                             ]"
                        ></div>
                        
                        <div class="relative z-10">
                            <p class="text-[10px] md:text-xs font-bold uppercase tracking-wider mb-1"
                               :class="[
                                  index % 2 === 0 ? 'text-indigo-600' : 'text-emerald-600'
                               ]"
                            >
                                {{ balance.jenis_cuti.nama_cuti }}
                            </p>
                            <div class="flex items-baseline gap-1 md:gap-2">
                                <h4 v-if="balance.jenis_cuti.is_unlimited" class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100 leading-none">
                                    &infin;
                                </h4>
                                <h4 v-else class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100 leading-none">
                                    {{ balance.saldo_akhir }}
                                </h4>
                                <span v-if="!balance.jenis_cuti.is_unlimited" class="text-[10px] md:text-sm font-medium text-gray-500">Hari</span>
                            </div>
                            <div class="mt-1.5 text-[9px] md:text-xs text-gray-400">
                                <span v-if="balance.jenis_cuti.is_unlimited">Tanpa Batas &bull; Pakai: {{ balance.saldo_terpakai }}</span>
                                <span v-else>Pakai: {{ balance.saldo_terpakai }} / Total: {{ balance.saldo_awal }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else-if="isEmployee" class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Saldo cuti belum digenerate untuk tahun ini. Hubungi HR.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mb-4">
                    <button v-if="isEmployee" @click="showCreateModal = true" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <PlusIcon class="w-4 h-4 mr-2" /> Ajukan Cuti
                    </button>
                </div>

                <!-- Mobile Card View (Visible on Small Screens) -->
                <div class="block md:hidden space-y-4">
                    <div v-if="!myRequests.data || myRequests.data.length === 0" class="bg-white dark:bg-gray-800 p-8 rounded-xl shadow-sm text-center text-gray-500 text-sm border border-gray-100 dark:border-gray-700">
                        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Belum ada riwayat pengajuan cuti.
                    </div>
                    
                    <template v-else>
                        <div v-for="req in myRequests.data" :key="req.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden relative">
                        <div class="absolute top-0 left-0 w-1 h-full" :class="{
                            'bg-yellow-400': req.status === 'Pending' || req.status === 'Pending Approval',
                            'bg-green-500': req.status === 'Approved',
                            'bg-red-500': req.status === 'Rejected',
                        }"></div>
                        <div class="p-4 pl-5">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-base">{{ req.jenis_cuti.nama_cuti }}</h3>
                                    <p class="text-xs text-gray-500 mt-0.5 flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ formatDate(req.tgl_mulai) }} - {{ formatDate(req.tgl_selesai) }}
                                    </p>
                                </div>
                                <span :class="{
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': req.status === 'Pending' || req.status === 'Pending Approval',
                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': req.status === 'Approved',
                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': req.status === 'Rejected',
                                }" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md">
                                    {{ req.status }}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Durasi</p>
                                    <p class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ req.jumlah_hari }} Hari</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Menunggu</p>
                                    <div class="font-medium text-sm text-gray-900 dark:text-gray-100">
                                        <template v-if="req.status === 'Pending Approval' && req.approval_process">
                                            <template v-for="step in req.approval_process" :key="step.id">
                                                <span v-if="step.status === 'Pending'" class="text-yellow-600 block truncate">{{ step.target_karyawan?.nama_lengkap }}</span>
                                            </template>
                                        </template>
                                        <span v-else class="truncate">{{ req.approver?.name || '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </template>
                </div>

                <!-- Desktop Table View (Hidden on Small Screens) -->
                <div class="hidden md:block bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Jenis Cuti</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Durasi</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Approver</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="req in myRequests.data" :key="req.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ req.jenis_cuti.nama_cuti }}</td>
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
                                    <!-- Show current approver from process if pending -->
                                    <div v-if="req.status === 'Pending Approval' && req.approval_process">
                                        <div v-for="step in req.approval_process" :key="step.id">
                                                <span v-if="step.status === 'Pending'" class="text-yellow-600">{{ step.target_karyawan?.nama_lengkap }}</span>
                                        </div>
                                    </div>
                                    <span v-else>{{ req.approver?.name || '-' }}</span>
                                </td>
                            </tr>
                            <tr v-if="!myRequests.data || myRequests.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada riwayat pengajuan cuti.</td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination v-if="myRequests.data && myRequests.data.length > 0" :links="myRequests.links" class="p-6 border-t border-gray-100 dark:border-gray-700" />
                </div>

            </div>
        </div>

        <!-- Create Cuti Modal -->
        <Modal :show="showCreateModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Ajukan Cuti Baru</h2>
                
                <form @submit.prevent="submitCreate" novalidate>
                    <fieldset :disabled="createForm.processing">
                    <div class="mb-4">
                        <InputLabel value="Jenis Cuti" />
                        <select v-model="createForm.id_jenis_cuti" @change="createForm.clearErrors('id_jenis_cuti')" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" :class="{'border-red-500': createForm.errors.id_jenis_cuti}">
                            <option value="" disabled>Pilih Jenis Cuti</option>
                            <option v-for="jc in availableJenisCuti" :key="jc.id" :value="jc.id">{{ jc.nama_cuti }}</option>
                        </select>
                        <InputError :message="createForm.errors.id_jenis_cuti" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Lampiran (Surat Dokter/Bukti)" :class="{ 'font-bold': createForm.id_jenis_cuti && jenisCutiMap[createForm.id_jenis_cuti]?.wajib_lampiran }" />
                        <input type="file" @change="e => { createForm.lampiran = e.target.files[0]; createForm.clearErrors('lampiran'); clearClientError('lampiran'); }" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 dark:border-gray-600 rounded-md p-1 bg-white dark:bg-gray-700 cursor-pointer focus:outline-none" />
                        <InputError class="mt-2" :message="clientErrors.lampiran || createForm.errors.lampiran" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel value="Tanggal Mulai" />
                            <TextInput v-model="createForm.tgl_mulai" @input="createForm.clearErrors('tgl_mulai')" type="date" class="mt-1 block w-full" :class="{'border-red-500': createForm.errors.tgl_mulai}" />
                            <InputError :message="createForm.errors.tgl_mulai" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel value="Tanggal Selesai" />
                            <TextInput v-model="createForm.tgl_selesai" @input="createForm.clearErrors('tgl_selesai')" type="date" class="mt-1 block w-full" :class="{'border-red-500': createForm.errors.tgl_selesai}" />
                            <InputError :message="createForm.errors.tgl_selesai" class="mt-2" />
                        </div>
                    </div>

                    <div class="mb-6">
                        <InputLabel value="Alasan" />
                        <textarea v-model="createForm.alasan" @input="createForm.clearErrors('alasan')" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" :class="{'border-red-500': createForm.errors.alasan}" rows="3"></textarea>
                        <InputError :message="createForm.errors.alasan" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-4 mt-6">
                        <SecondaryButton @click="closeModal" :disabled="createForm.processing">Batal</SecondaryButton>
                        <PrimaryButton :disabled="createForm.processing" class="relative">
                            <span :class="{'opacity-0': createForm.processing}">Kirim Pengajuan</span>
                            <span v-if="createForm.processing" class="absolute inset-0 flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="ml-2">Memproses...</span>
                            </span>
                        </PrimaryButton>
                    </div>
                    </fieldset>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
