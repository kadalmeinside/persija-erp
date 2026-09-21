<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { CreditCardIcon, BuildingStorefrontIcon, UserGroupIcon, PlusIcon, XMarkIcon, BanknotesIcon } from '@heroicons/vue/24/solid';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    form: Object,
    masterVendor: Array,
    masterKaryawan: Array,
    karyawan: Object, // Current User's Employee Data
    frontendErrors: Object
});

const emit = defineEmits(['open-vendor-modal', 'open-employee-bank-modal']);

// --- LOCAL COMPUTED ---
const selectedVendor = computed(() => {
    return props.masterVendor.find(v => v.id == props.form.id_vendor_penerima);
});

const selectedEmployee = computed(() => {
    return (props.masterKaryawan || []).find(k => k.id == props.form.id_karyawan_penerima);
});

const selectedEmployeeBank = computed(() => {
    return selectedEmployee.value ? selectedEmployee.value.primary_bank : null;
});

// Search Vendor State
const showSelectVendorModal = ref(false); // Just local UI state for dropdown/modal trigger
// Note: The actual Modal for Creating Vendor is in Parent, we just emit 'open-vendor-modal'

</script>

<template>
    <div v-if="form.metode_pembayaran === 'Transfer' || form.metode_pembayaran === 'Cash'" class="border-b border-gray-200 pb-6 mb-6 bg-gray-50 p-4 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
            <CreditCardIcon v-if="form.metode_pembayaran === 'Transfer'" class="w-5 h-5 mr-2 text-indigo-500"/>
            <BanknotesIcon v-else class="w-5 h-5 mr-2 text-green-500"/>
            {{ form.metode_pembayaran === 'Transfer' ? 'Informasi Tujuan Transfer' : 'Informasi Penerima Tunai' }}
        </h3>

        <!-- PILIHAN SUB-TIPE UNTUK LANGSUNG -->
        <div v-if="form.tipe_pengajuan === 'Langsung'" class="mb-4">
            <InputLabel value="Jenis Penerima" class="mb-2" />
            <div class="flex space-x-4">
                <label class="flex items-center cursor-pointer bg-white px-4 py-2 rounded border" :class="form.sub_tipe_penerima === 'Vendor' ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-300'">
                    <input type="radio" v-model="form.sub_tipe_penerima" value="Vendor" class="text-indigo-600 focus:ring-indigo-500 mr-2">
                    <BuildingStorefrontIcon class="w-4 h-4 mr-2 text-gray-500"/> Vendor / Supplier
                </label>
                <label class="flex items-center cursor-pointer bg-white px-4 py-2 rounded border" :class="form.sub_tipe_penerima === 'Karyawan' ? 'border-indigo-500 ring-1 ring-indigo-500' : 'border-gray-300'">
                    <input type="radio" v-model="form.sub_tipe_penerima" value="Karyawan" class="text-indigo-600 focus:ring-indigo-500 mr-2">
                    <UserGroupIcon class="w-4 h-4 mr-2 text-gray-500"/> Reimburse Karyawan
                </label>
            </div>
        </div>

        <!-- KHUSUS TAX PAYMENT: KAS NEGARA -->
        <div v-if="form.tipe_pengajuan === 'TaxPayment'" class="bg-white p-4 rounded border border-gray-200 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <BuildingStorefrontIcon class="w-8 h-8 text-indigo-600" />
                <div>
                    <h4 class="font-bold text-gray-900">Kas Negara (Penerimaan Negara)</h4>
                    <p class="text-sm text-gray-500">Pembayaran Pajak via Bank Persepsi / MPN G3</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <InputLabel value="Bank / Channel Pembayaran" />
                    <TextInput v-model="form.bank_tujuan" placeholder="Contoh: Bank Mandiri / BNI" class="w-full mt-1" />
                </div>
                <div>
                    <InputLabel value="Kode Billing / No. Rekening" />
                    <TextInput v-model="form.no_rek_tujuan" placeholder="Masukkan Kode Billing" class="w-full mt-1 font-mono" />
                </div>
                <div class="md:col-span-2">
                    <InputLabel value="Atas Nama (Opsional)" />
                    <TextInput v-model="form.atas_nama_tujuan" placeholder="Kas Negara" class="w-full mt-1" />
                </div>
            </div>
        </div>

        <!-- LOGIKA TAMPILAN DINAMIS -->
        <!-- 1. TAMPILKAN PILIHAN VENDOR -->
        <div v-if="form.tipe_pengajuan === 'Langsung' && form.sub_tipe_penerima === 'Vendor'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <InputLabel value="Pilih Vendor" />
                <div class="flex gap-2 mt-1 relative">
                    <select v-model="form.id_vendor_penerima" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                         <option :value="null">-- Pilih Vendor --</option>
                         <option v-for="vendor in masterVendor" :key="vendor.id" :value="vendor.id">
                             {{ vendor.nama_vendor }}
                         </option>
                    </select>
                    <SecondaryButton type="button" @click="$emit('open-vendor-modal')" title="Tambah Vendor Baru"><PlusIcon class="w-5 h-5" /></SecondaryButton>
                </div>
                <InputError class="mt-1" :message="frontendErrors['id_vendor_penerima'] || form.errors.id_vendor_penerima" />
            </div>
            
            <div v-if="selectedVendor" class="bg-white p-3 rounded border border-gray-200 shadow-sm">
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Rekening Tujuan</div>
                <div v-if="selectedVendor.primary_bank">
                    <div class="font-bold text-indigo-700 text-lg">{{ selectedVendor.primary_bank.nama_bank }}</div>
                    <div class="font-mono text-gray-800">{{ selectedVendor.primary_bank.nomor_rekening }}</div>
                    <div class="text-sm text-gray-600">a.n {{ selectedVendor.primary_bank.atas_nama_rekening }}</div>
                </div>
                <div v-else class="text-red-500 italic text-sm flex items-center">
                    <XMarkIcon class="w-4 h-4 mr-1"/> Vendor ini belum memiliki data bank.
                </div>
            </div>
        </div>

        <!-- 2. TAMPILKAN PILIHAN KARYAWAN (Reimburse, Uang Muka, atau PettyCash) -->
        <div v-if="form.tipe_pengajuan === 'PettyCash' || form.tipe_pengajuan === 'UangMuka' || (form.tipe_pengajuan === 'Langsung' && form.sub_tipe_penerima === 'Karyawan')" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <InputLabel :value="form.tipe_pengajuan === 'PettyCash' ? 'Karyawan yang Menerima Uang Tunai' : 'Karyawan Penerima'" />
                <select v-model="form.id_karyawan_penerima" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option v-for="k in masterKaryawan" :key="k.id" :value="k.id">
                        {{ k.nama_lengkap }} {{ karyawan && k.id === karyawan.id ? '(Saya)' : '' }}
                    </option>
                </select>
                <InputError class="mt-1" :message="frontendErrors['id_karyawan_penerima'] || form.errors.id_karyawan_penerima" />
            </div>

            <!-- Detail Bank Karyawan Hanya Ditampilkan Jika Metode Transfer -->
            <div v-if="form.id_karyawan_penerima && form.metode_pembayaran === 'Transfer'" class="bg-white p-3 rounded border border-gray-200 shadow-sm relative">
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Rekening Tujuan</div>
                <div v-if="selectedEmployeeBank">
                    <div class="font-bold text-indigo-700 text-lg">{{ selectedEmployeeBank.nama_bank }}</div>
                    <div class="font-mono text-gray-800">{{ selectedEmployeeBank.nomor_rekening }}</div>
                    <div class="text-sm text-gray-600">a.n {{ selectedEmployeeBank.atas_nama_rekening }}</div>
                </div>
                <div v-else class="text-red-500 italic text-sm mb-2">
                    Belum ada data rekening untuk karyawan ini.
                </div>
                
                <button type="button" @click="$emit('open-employee-bank-modal')" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 underline mt-2 flex items-center">
                    <PlusIcon class="w-3 h-3 mr-1"/>
                    {{ selectedEmployeeBank ? 'Ubah / Tambah Rekening Lain' : 'Tambah Rekening Baru' }}
                </button>
            </div>
            
            <!-- Info box jika metode pembayaran Cash -->
            <div v-else-if="form.id_karyawan_penerima && form.metode_pembayaran === 'Cash'" class="bg-amber-50 p-3 rounded border border-amber-200 text-sm text-amber-800">
                <p><strong>Informasi:</strong> Dana ini akan diberikan/telah diberikan secara tunai dari kas kecil perusahaan kepada <strong>{{ selectedEmployee ? selectedEmployee.nama_lengkap : 'Karyawan' }}</strong>.</p>
            </div>
        </div>
    </div>
</template>
