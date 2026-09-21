<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { CheckCircleIcon, LockClosedIcon, LockOpenIcon, ArrowsRightLeftIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    months: Array,
    selectedYear: Number,
    canReopen: Boolean
});

const yearSelector = ref(props.selectedYear);

const changeYear = () => {
    router.get(route('admin.period-closings.index'), { tahun: yearSelector.value }, { preserveState: true });
};

// Modals
const showCloseModal = ref(false);
const showReopenModal = ref(false);
const selectedPeriod = ref(null);

const form = useForm({
    bulan: null,
    tahun: null,
    catatan: ''
});

const openCloseModal = (monthData) => {
    selectedPeriod.value = monthData;
    form.bulan = monthData.bulan;
    form.tahun = monthData.tahun;
    form.catatan = '';
    form.clearErrors();
    showCloseModal.value = true;
};

const openReopenModal = (monthData) => {
    selectedPeriod.value = monthData;
    form.bulan = monthData.bulan;
    form.tahun = monthData.tahun;
    form.catatan = ''; // Reason is mandatory here
    form.clearErrors();
    showReopenModal.value = true;
};

const submitClose = () => {
    form.post(route('admin.period-closings.close'), {
        onSuccess: () => {
            showCloseModal.value = false;
        }
    });
};

const submitReopen = () => {
    form.post(route('admin.period-closings.reopen'), {
        onSuccess: () => {
            showReopenModal.value = false;
        }
    });
};

const getStatusBadge = (status) => {
    return status === 'Closed' 
        ? 'bg-red-100 text-red-800 border-red-200' 
        : 'bg-green-100 text-green-800 border-green-200';
};
</script>

<template>
    <Head title="Periode Tutup Buku" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen Tutup Buku (Period Closing)</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Toolbar -->
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6 flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <InputLabel value="Pilih Tahun Akuntansi" />
                        <select v-model="yearSelector" @change="changeYear" class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option v-for="y in [selectedYear - 2, selectedYear - 1, selectedYear, selectedYear + 1]" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <div class="text-sm text-gray-500">
                        Pastikan seluruh transaksi bulan terkait telah terselesaikan (Settled/Posted) sebelum melakukan Tutup Buku!
                    </div>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="month in months" :key="month.bulan" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                        <div class="p-5 flex-1 relative">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-bold text-gray-900">{{ month.nama_bulan }} {{ month.tahun }}</h3>
                                <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold border flex items-center', getStatusBadge(month.status)]">
                                    <LockClosedIcon v-if="month.status === 'Closed'" class="w-3 h-3 mr-1" />
                                    <LockOpenIcon v-else class="w-3 h-3 mr-1" />
                                    {{ month.status === 'Closed' ? 'Terkunci' : 'Terbuka' }}
                                </span>
                            </div>

                            <div v-if="month.status === 'Closed'" class="space-y-2 mt-4 text-sm bg-gray-50 p-3 rounded border border-gray-100">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Oleh:</span>
                                    <span class="font-medium text-gray-800">{{ month.closed_by_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Waktu:</span>
                                    <span class="font-medium text-gray-800">{{ month.closed_at }}</span>
                                </div>
                            </div>
                            
                            <div v-else-if="month.reopened_at" class="space-y-2 mt-4 text-sm bg-amber-50 p-3 rounded border border-amber-100">
                                <p class="text-amber-800 font-semibold mb-1 text-xs">Riwayat Terakhir Buka:</p>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Oleh:</span>
                                    <span class="font-medium text-gray-800">{{ month.reopened_by_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Waktu:</span>
                                    <span class="font-medium text-gray-800">{{ month.reopened_at }}</span>
                                </div>
                            </div>

                        </div>
                        
                        <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-end">
                            <PrimaryButton v-if="month.status === 'Open'" @click="openCloseModal(month)" class="w-full justify-center bg-indigo-600 hover:bg-indigo-700">
                                <LockClosedIcon class="w-4 h-4 mr-2" /> Tutup Buku
                            </PrimaryButton>
                            
                            <!-- Buka Kembali hanya muncul jika status Closed dan user punya akses -->
                            <button v-if="month.status === 'Closed' && canReopen" @click="openReopenModal(month)" class="w-full text-center px-4 py-2 bg-amber-100 text-amber-800 hover:bg-amber-200 border border-amber-200 rounded-md font-semibold text-xs transition ease-in-out duration-150 flex items-center justify-center">
                                <LockOpenIcon class="w-4 h-4 mr-2 text-amber-600" /> Buka Kembali
                            </button>
                            
                            <div v-if="month.status === 'Closed' && !canReopen" class="text-xs text-red-500 text-center w-full py-1">
                                Hubungi Manager untuk Re-Open
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Tutup Modals -->
        <Modal :show="showCloseModal" @close="showCloseModal = false">
            <div class="p-6">
                <div class="flex items-center text-indigo-600 mb-4">
                    <LockClosedIcon class="w-8 h-8 mr-2" />
                    <h2 class="text-lg font-bold text-gray-900">Konfirmasi Tutup Buku</h2>
                </div>
                
                <p class="text-sm text-gray-600 mb-4">
                    Anda yakin akan menutup periode akuntansi untuk <strong>{{ selectedPeriod?.nama_bulan }} {{ selectedPeriod?.tahun }}</strong>?
                </p>
                
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Setelah ditutup, sistem akan <strong>menolak</strong> pembuatan atau persetujuan Jurnal / Invoice / Pengajuan baru yang tertanggal pada periode ini.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel for="catatan_close" value="Catatan (Opsional)" />
                    <TextInput id="catatan_close" v-model="form.catatan" type="text" class="mt-1 block w-full" placeholder="Misal: Sudah diverifikasi dan balance" />
                    <InputError class="mt-2" :message="form.errors.catatan" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showCloseModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submitClose" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Ya, Tutup Periode
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Re-Open Modals -->
        <Modal :show="showReopenModal" @close="showReopenModal = false">
            <div class="p-6">
                <div class="flex items-center text-amber-600 mb-4">
                    <LockOpenIcon class="w-8 h-8 mr-2" />
                    <h2 class="text-lg font-bold text-gray-900">Buka Kembali Periode Tertutup</h2>
                </div>
                
                <p class="text-sm text-gray-600 mb-4">
                    Anda akan membuka kembali (Re-Open) periode <strong>{{ selectedPeriod?.nama_bulan }} {{ selectedPeriod?.tahun }}</strong>. Tindakan ini sangat sensitif secara audit.
                </p>
                
                <div class="mt-4">
                    <InputLabel for="catatan_reopen" value="Alasan Re-Open (Wajib)" />
                    <TextInput id="catatan_reopen" v-model="form.catatan" type="text" class="mt-1 block w-full" placeholder="Misal: Terdapat koreksi audit Jurnal PPN..." autocomplete="off" />
                    <InputError class="mt-2" :message="form.errors.catatan" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showReopenModal = false">Batal</SecondaryButton>
                    <button @click="submitReopen" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                        Konfirmasi Re-Open
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
