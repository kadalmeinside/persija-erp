<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { UserCircleIcon } from '@heroicons/vue/24/solid';

// 1. Mendefinisikan Props (Vendor ditambahkan)
const props = defineProps({
    masterAkun: Array,
    masterProgram: Array,
    masterPajak: Array,
    karyawan: Object,
    masterVendor: Array // <-- PROPS BARU DARI CONTROLLER
});

// Helper untuk format mata uang
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

// 2. Inisialisasi Form (Sesuai Best Practice)
const form = useForm({
    judul_pengajuan: '', // <-- BARU
    tgl_pengajuan: new Date().toISOString().split('T')[0],
    id_pengaju: props.karyawan.id,
    id_departemen: props.karyawan.id_departemen,
    tipe_pengajuan: 'Langsung',
    metode_pembayaran: 'Transfer', // <-- BARU
    id_vendor_penerima: null, // <-- BARU
    attachment: null, // <-- Pindah ke Header
    catatan_header: '',
    total_nominal_diajukan: 0,
    items: [
        // Attachment dihapus dari item
        { deskripsi_item: '', nominal_item: 0, id_program: null, id_akun: null, id_pajak: null }
    ]
});

// --- VALIDASI FRONTEND (Diperbarui) ---
const frontendErrors = ref({});

const validateFrontend = () => {
    frontendErrors.value = {}; // Reset error
    let hasError = false;

    // Validasi Wajib Header
    if (!form.judul_pengajuan) {
        frontendErrors.value['judul_pengajuan'] = 'Judul pengajuan wajib diisi.'; hasError = true;
    }
    if (!form.tgl_pengajuan) {
        frontendErrors.value['tgl_pengajuan'] = 'Tanggal wajib diisi.'; hasError = true;
    }

    if (form.tipe_pengajuan === 'Langsung') {
        // Cek Attachment di Header
        if (!form.attachment) {
            frontendErrors.value['attachment'] = 'Attachment faktur/bukti wajib diisi.';
            hasError = true;
        }
        // Cek Penerima (Vendor) jika metode Transfer
        if (form.metode_pembayaran === 'Transfer' && !form.id_vendor_penerima) {
            frontendErrors.value['id_vendor_penerima'] = 'Penerima (Vendor) wajib dipilih untuk transfer.'; 
            hasError = true;
        }

        // Cek Rincian Item
        form.items.forEach((item, index) => {
            if (!item.deskripsi_item) {
                frontendErrors.value[`items.${index}.deskripsi_item`] = 'Deskripsi wajib diisi.'; hasError = true;
            }
            if (!item.id_program) {
                frontendErrors.value[`items.${index}.id_program`] = 'Program wajib dipilih.'; hasError = true;
            }
            if (!item.id_akun) {
                frontendErrors.value[`items.${index}.id_akun`] = 'Akun wajib dipilih.'; hasError = true;
            }
            if (!item.nominal_item || parseFloat(item.nominal_item) <= 0) {
                frontendErrors.value[`items.${index}.nominal_item`] = 'Nominal harus lebih dari 0.'; hasError = true;
            }
        });
    } else { // Validasi untuk Uang Muka
        if (!form.total_nominal_diajukan || form.total_nominal_diajukan <= 0) {
            frontendErrors.value['total_nominal_diajukan'] = 'Jumlah uang muka harus lebih dari 0.';
            hasError = true;
        }
        // Untuk Uang Muka, Penerima otomatis adalah Karyawan, jadi tidak perlu validasi Vendor
    }
    return !hasError; // Kembalikan true jika valid
};
// --- AKHIR VALIDASI FRONTEND ---

// 3. Logika untuk menambah/menghapus baris item (Disederhanakan)
const addItem = () => {
    form.items.push({
        deskripsi_item: '',
        nominal_item: 0,
        id_program: null,
        id_akun: null,
        id_pajak: null
    });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

// 4. Logika Komputasi Total (Tidak Berubah)
const totalItems = computed(() => {
    return form.items.reduce((total, item) => {
        return total + (parseFloat(item.nominal_item) || 0);
    }, 0);
});

// 5. Watcher (Pengamat) (Tidak Berubah)
watch(() => form.tipe_pengajuan, (newType) => {
    frontendErrors.value = {}; 
    if (newType === 'UangMuka') {
        form.total_nominal_diajukan = 0;
        form.id_vendor_penerima = null; // Reset vendor jika ganti ke Uang Muka
    } else {
        if (form.items.length === 0) addItem(); 
        form.total_nominal_diajukan = totalItems.value;
    }
});

watch(totalItems, (newTotal) => {
    if (form.tipe_pengajuan === 'Langsung') {
        form.total_nominal_diajukan = newTotal;
    }
});

// 6. Fungsi Submit (Tidak Berubah)
const submit = () => {
    form.clearErrors();
    
    if (!validateFrontend()) {
        console.warn("Validasi frontend gagal.");
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return; 
    }

    if (form.tipe_pengajuan === 'Langsung') {
        form.total_nominal_diajukan = totalItems.value;
    }
    
    form.post(route('admin.pengajuan.store'), {
        onSuccess: () => {},
        onError: (errors) => {
            console.error("Error submitting form (Backend):", errors);
        },
    });
};
</script>

<template>
    <Head title="Buat Pengajuan Baru" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat Pengajuan Pembayaran Baru</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Kartu Info Pengaju -->
                <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <UserCircleIcon class="h-12 w-12 text-gray-300" />
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ props.karyawan.nama_lengkap }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ props.karyawan.jabatan }} - Divisi/Dept: 
                                <span class="font-semibold text-gray-700">{{ props.karyawan.departemen.nama_departemen }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <!-- Notifikasi Error/Sukses -->
                        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                            {{ $page.props.flash.error }}
                        </div>
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                            {{ $page.props.flash.success }}
                        </div>

                        <!-- Form Utama -->
                        <form @submit.prevent="submit" novalidate>
                            
                            <!-- Bagian Header Form (Best Practice) -->
                            <div class="border-b border-gray-200 pb-6 mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Utama</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Kolom Kiri -->
                                    <div class="space-y-6">
                                        <!-- Judul Pengajuan (BARU) -->
                                        <div>
                                            <InputLabel for="judul_pengajuan" value="Judul Pengajuan" class="font-bold" />
                                            <TextInput
                                                id="judul_pengajuan"
                                                type="text"
                                                class="mt-1 block w-full"
                                                v-model="form.judul_pengajuan"
                                                placeholder="Cth: Faktur Catering Rapat November"
                                            />
                                            <InputError class="mt-2" :message="frontendErrors['judul_pengajuan'] || form.errors.judul_pengajuan" />
                                        </div>

                                        <!-- Kategori Pengajuan -->
                                        <div>
                                            <InputLabel for="tipe_pengajuan" value="Kategori" class="font-bold" />
                                            <select
                                                id="tipe_pengajuan"
                                                v-model="form.tipe_pengajuan"
                                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            >
                                                <option value="Langsung">Payment Request (Langsung)</option>
                                                <option value="UangMuka">Cash Advance (Uang Muka)</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Metode Pembayaran (BARU) -->
                                        <div>
                                            <InputLabel for="metode_pembayaran" value="Metode Pembayaran" />
                                            <select
                                                id="metode_pembayaran"
                                                v-model="form.metode_pembayaran"
                                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            >
                                                <option value="Transfer">Transfer Bank</option>
                                                <option value="Cash">Cash / Kas Kecil</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Kolom Kanan -->
                                    <div class="space-y-6">
                                        <!-- Tanggal Pengajuan -->
                                        <div>
                                            <InputLabel for="tgl_pengajuan" value="Tanggal Pengajuan" />
                                            <TextInput
                                                id="tgl_pengajuan"
                                                type="date"
                                                class="mt-1 block w-full"
                                                v-model="form.tgl_pengajuan"
                                            />
                                            <InputError class="mt-2" :message="frontendErrors['tgl_pengajuan'] || form.errors.tgl_pengajuan" />
                                        </div>
                                        
                                        <!-- Input Attachment (PINDAH KE HEADER) -->
                                        <div v-if="form.tipe_pengajuan === 'Langsung'">
                                            <InputLabel for="attachment" value="Attachment Faktur/Bukti" class="font-bold" />
                                            <input 
                                                type="file" 
                                                id="attachment"
                                                @input="form.attachment = $event.target.files[0]"
                                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                            />
                                            <InputError class="mt-2" :message="frontendErrors['attachment'] || form.errors.attachment" />
                                        </div>

                                        <!-- Penerima (Vendor) (BARU) -->
                                        <div v-if="form.tipe_pengajuan === 'Langsung' && form.metode_pembayaran === 'Transfer'">
                                            <InputLabel for="id_vendor_penerima" value="Penerima (Vendor)" class="font-bold" />
                                            <select
                                                id="id_vendor_penerima"
                                                v-model="form.id_vendor_penerima"
                                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            >
                                                <option :value="null" disabled>Pilih Vendor Penerima</option>
                                                <option v-for="vendor in props.masterVendor" :key="vendor.id" :value="vendor.id">{{ vendor.nama_vendor }}</option>
                                            </select>
                                            <InputError class="mt-2" :message="frontendErrors['id_vendor_penerima'] || form.errors.id_vendor_penerima" />
                                        </div>

                                    </div>
                                </div>
                            </div>


                            <!-- Opsi 1: Rincian Item (Jika 'Payment Request') -->
                            <div v-if="form.tipe_pengajuan === 'Langsung'">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Rincian Alokasi Biaya</h3>
                                <div class="overflow-x-auto">
                                    <!-- TABEL LEBIH RAMPING (Attachment Dihapus) -->
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akun Biaya</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pajak</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                                                <th scope="col" class="relative px-6 py-3">
                                                    <span class="sr-only">Hapus</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(item, index) in form.items" :key="index">
                                                
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <TextInput type="text" v-model="item.deskripsi_item" class="min-w-[250px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Deskripsi item..." />
                                                    <InputError class="mt-1" :message="frontendErrors[`items.${index}.deskripsi_item`] || form.errors[`items.${index}.deskripsi_item`]" />
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <select v-model="item.id_program" class="min-w-[200px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                        <option :value="null" disabled>Pilih Program</option>
                                                        <option v-for="program in props.masterProgram" :key="program.id" :value="program.id">{{ program.nama_program }}</option>
                                                    </select>
                                                    <InputError class="mt-1" :message="frontendErrors[`items.${index}.id_program`] || form.errors[`items.${index}.id_program`]" />
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <select v-model="item.id_akun" class="min-w-[250px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                        <option :value="null" disabled>Pilih Akun Biaya</option>
                                                        <option v-for="akun in props.masterAkun" :key="akun.id" :value="akun.id">{{ akun.kode_akun }} - {{ akun.nama_akun }}</option>
                                                    </select>
                                                    <InputError class="mt-1" :message="frontendErrors[`items.${index}.id_akun`] || form.errors[`items.${index}.id_akun`]" />
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                     <select v-model="item.id_pajak" class="min-w-[150px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                        <option :value="null">Tanpa Pajak</option>
                                                        <option v-for="pajak in props.masterPajak" :key="pajak.id" :value="pajak.id">{{ pajak.kode_pajak }}</option>
                                                    </select>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <TextInput type="number" v-model.number="item.nominal_item" class="min-w-[150px] border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="0" />
                                                    <InputError class="mt-1" :message="frontendErrors[`items.${index}.nominal_item`] || form.errors[`items.${index}.nominal_item`]" />
                                                </td>
                                                
                                                <!-- Kolom Attachment DIHAPUS -->
                                                
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <DangerButton type="button" @click="removeItem(index)" :disabled="form.items.length === 1">
                                                        Hapus
                                                    </DangerButton>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <SecondaryButton type="button" @click="addItem" class="mt-4">
                                    + Tambah Baris
                                </SecondaryButton>
                            </div>

                            <!-- Opsi 2: Jumlah Global (Jika 'Cash Advance') -->
                            <div v-if="form.tipe_pengajuan === 'UangMuka'">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Detail Uang Muka</h3>
                                <div>
                                    <InputLabel for="total_nominal_diajukan" value="Jumlah Uang Muka" class="font-bold" />
                                    <TextInput
                                        id="total_nominal_diajukan"
                                        type="number"
                                        class="mt-1 block w-full md:w-1/3"
                                        v-model.number="form.total_nominal_diajukan"
                                        placeholder="0"
                                    /> 
                                    <InputError class="mt-2" :message="frontendErrors['total_nominal_diajukan'] || form.errors.total_nominal_diajukan" />
                                </div>
                            </div>
                            
                            <!-- Bagian Catatan & Total -->
                            <div class="border-t border-gray-200 mt-6 pt-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Catatan -->
                                    <div>
                                        <InputLabel for="catatan_header" value="Catatan / Keterangan" />
                                        <textarea
                                            id="catatan_header"
                                            v-model="form.catatan_header"
                                            rows="4"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            placeholder="Catatan tambahan untuk approver..."
                                        ></textarea>
                                        <InputError class="mt-2" :message="form.errors.catatan_header" />
                                    </div>
                                    <!-- Ringkasan Total -->
                                    <div class="flex flex-col justify-end items-end">
                                        <div class="text-gray-500 text-sm">Total Pengajuan</div>
                                        <div class="text-3xl font-bold text-gray-900">
                                            {{ formatCurrency(form.total_nominal_diajukan) }}
                                        </div>
                                        <InputError class="mt-2" :message="form.errors.total_nominal_diajukan" />
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Aksi -->
                            <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-200">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Kirim Pengajuan
                                </PrimaryButton>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>