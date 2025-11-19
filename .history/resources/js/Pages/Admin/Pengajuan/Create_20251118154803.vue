<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue'; // <-- Import Modal
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { UserCircleIcon, PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/solid';
import axios from 'axios'; // <-- Import Axios untuk AJAX

// 1. Mendefinisikan Props
const props = defineProps({
    masterAkun: Array,
    masterProgram: Array,
    masterPajak: Array,
    karyawan: Object,
    masterVendor: Array
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
    judul_pengajuan: '', 
    tgl_pengajuan: new Date().toISOString().split('T')[0],
    // Gunakan optional chaining untuk menghindari error 'undefined'
    id_pengaju: props.karyawan?.id,
    id_departemen: props.karyawan?.id_departemen,
    tipe_pengajuan: 'Langsung',
    metode_pembayaran: 'Transfer', 
    id_vendor_penerima: null, 
    attachment: null, 
    catatan_header: '',
    total_nominal_diajukan: 0,
    items: [] 
});

// --- STATE UNTUK MODAL ---
const showModal = ref(false);
const isEditingItem = ref(false);
const editingIndex = ref(null);

const modalForm = ref({
    deskripsi_item: '',
    id_program: null,
    id_akun: null,
    id_pajak: null,
    nominal_item: 0,
});

const saldoCache = ref({});
const sisaSaldoDB = ref(0);
const sisaSaldoSesi = ref(0);
const isLoadingSaldo = ref(false);
const modalErrors = ref({}); 
const frontendErrors = ref({}); // Error untuk header

// --- FUNGSI MODAL ---
const openModalUntukTambah = () => {
    isEditingItem.value = false;
    editingIndex.value = null;
    modalForm.value = { deskripsi_item: '', id_program: null, id_akun: null, id_pajak: null, nominal_item: 0 };
    sisaSaldoDB.value = 0;
    sisaSaldoSesi.value = 0;
    isLoadingSaldo.value = false;
    modalErrors.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

// --- FUNGSI REAL-TIME BUDGET CHECK ---
const onBudgetLineChange = async () => {
    const { id_akun, id_program } = modalForm.value;
    const { id_departemen, tgl_pengajuan } = form;

    sisaSaldoDB.value = 0;
    sisaSaldoSesi.value = 0;
    modalErrors.value.nominal_item = '';

    if (!id_akun || !id_program || !id_departemen || !tgl_pengajuan) return;

    isLoadingSaldo.value = true;
    const cacheKey = `dept_${id_departemen}_akun_${id_akun}_prog_${id_program}_thn_${tgl_pengajuan.split('-')[0]}`;

    try {
        let saldoDariDB = 0;
        if (saldoCache.value[cacheKey]) {
            saldoDariDB = saldoCache.value[cacheKey];
        } else {
            const response = await axios.get(route('admin.pengajuan.getBudgetBalance'), {
                params: { id_departemen, id_akun, id_program, tgl_pengajuan }
            });
            saldoDariDB = response.data.sisa_saldo_db;
            saldoCache.value[cacheKey] = saldoDariDB; 
        }

        sisaSaldoDB.value = saldoDariDB;
        const totalDiKeranjang = form.items.reduce((total, item, index) => {
            if (item.id_akun == id_akun && item.id_program == id_program && index !== editingIndex.value) {
                return total + (parseFloat(item.nominal_item) || 0);
            }
            return total;
        }, 0);
        sisaSaldoSesi.value = sisaSaldoDB.value - totalDiKeranjang;

    } catch (error) {
        console.error("Gagal mengambil sisa saldo:", error);
        // Jangan blokir user jika error koneksi, biarkan backend yang validasi akhir
        // modalErrors.value.nominal_item = 'Gagal koneksi ke server.';
    } finally {
        isLoadingSaldo.value = false;
    }
};

watch(() => modalForm.value.nominal_item, (newNominal) => {
    if (sisaSaldoSesi.value > 0 || (sisaSaldoSesi.value === 0 && !isLoadingSaldo.value)) {
        if (parseFloat(newNominal) > sisaSaldoSesi.value) {
            modalErrors.value.nominal_item = `Nominal melebihi sisa saldo (Rp ${sisaSaldoSesi.value.toLocaleString('id-ID')})`;
        } else {
            modalErrors.value.nominal_item = '';
        }
    }
});

const simpanItem = () => {
    modalErrors.value = {};
    if (!modalForm.value.deskripsi_item) modalErrors.value.deskripsi_item = 'Deskripsi wajib diisi.';
    if (!modalForm.value.id_program) modalErrors.value.id_program = 'Program wajib dipilih.';
    if (!modalForm.value.id_akun) modalErrors.value.id_akun = 'Akun wajib dipilih.';
    if (!modalForm.value.nominal_item || parseFloat(modalForm.value.nominal_item) <= 0) modalErrors.value.nominal_item = 'Nominal harus lebih dari 0.';
    // Biarkan user menyimpan meski melebihi saldo (soft check), nanti backend yang reject hard check
    
    if (Object.keys(modalErrors.value).length > 0) return;

    form.items.push(JSON.parse(JSON.stringify(modalForm.value)));
    closeModal();
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// --- FUNGSI HEADER & TOTAL ---
const totalItems = computed(() => {
    return form.items.reduce((total, item) => {
        return total + (parseFloat(item.nominal_item) || 0);
    }, 0);
});

watch(() => form.tipe_pengajuan, (newType) => {
    if (newType === 'UangMuka') {
        form.id_vendor_penerima = null;
        form.items = []; 
    }
    form.total_nominal_diajukan = (newType === 'UangMuka') ? 0 : totalItems.value;
});

watch(totalItems, (newTotal) => {
    if (form.tipe_pengajuan === 'Langsung') {
        form.total_nominal_diajukan = newTotal;
    }
});

// --- FUNGSI SUBMIT UTAMA ---
const validateFormUtama = () => {
    frontendErrors.value = {};
    let hasError = false;
    if (!form.judul_pengajuan) { frontendErrors.value['judul_pengajuan'] = 'Judul pengajuan wajib diisi.'; hasError = true; }
    if (!form.tgl_pengajuan) { frontendErrors.value['tgl_pengajuan'] = 'Tanggal wajib diisi.'; hasError = true; }

    if (form.tipe_pengajuan === 'Langsung') {
        if (!form.attachment) { frontendErrors.value['attachment'] = 'Attachment faktur/bukti wajib diisi.'; hasError = true; }
        
        // Validasi Vendor hanya jika Transfer
        if (form.metode_pembayaran === 'Transfer' && !form.id_vendor_penerima) { 
            frontendErrors.value['id_vendor_penerima'] = 'Penerima (Vendor) wajib dipilih.'; 
            hasError = true; 
        }
        
        if (form.items.length === 0) {
            frontendErrors.value['items'] = 'Minimal harus ada 1 item rincian.'; hasError = true;
        }
    } else {
        if (!form.total_nominal_diajukan || form.total_nominal_diajukan <= 0) { 
            frontendErrors.value['total_nominal_diajukan'] = 'Jumlah uang muka harus lebih dari 0.'; 
            hasError = true; 
        }
    }
    return !hasError;
};

const submit = () => {
    form.clearErrors();
    
    if (!validateFormUtama()) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return; 
    }

    form.post(route('admin.pengajuan.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Inertia akan otomatis redirect jika sukses
        },
        onError: (errors) => {
            console.error("Error Backend:", errors);
            // Error backend otomatis masuk ke form.errors
            // Kita bisa menampilkan notifikasi global jika perlu
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
                        <UserCircleIcon class="h-12 w-12 text-gray-300" />
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ props.karyawan?.nama_lengkap || 'Loading...' }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ props.karyawan?.jabatan }} - Divisi/Dept: 
                                <span class="font-semibold text-gray-700">{{ props.karyawan?.departemen?.nama_departemen }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <!-- Notifikasi Flash -->
                        <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                            {{ $page.props.flash.error }}
                        </div>
                        <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                            {{ $page.props.flash.success }}
                        </div>
                        
                        <!-- Tampilkan Error Validasi Global jika ada -->
                        <div v-if="Object.keys(form.errors).length > 0" class="mb-4 p-4 bg-red-50 text-red-700 rounded-md text-sm">
                            <p class="font-bold">Gagal menyimpan. Periksa input berikut:</p>
                            <ul class="list-disc list-inside">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submit" novalidate>
                            
                            <!-- Header Form -->
                            <div class="border-b border-gray-200 pb-6 mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Utama</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Kolom Kiri -->
                                    <div class="space-y-6">
                                        <div>
                                            <InputLabel for="judul_pengajuan" value="Judul Pengajuan" class="font-bold" />
                                            <TextInput
                                                id="judul_pengajuan"
                                                type="text"
                                                class="mt-1 block w-full"
                                                v-model="form.judul_pengajuan"
                                                placeholder="Cth: Faktur Catering Rapat"
                                            />
                                            <InputError class="mt-2" :message="frontendErrors['judul_pengajuan'] || form.errors.judul_pengajuan" />
                                        </div>

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

                            <!-- Tabel Rincian -->
                            <div v-if="form.tipe_pengajuan === 'Langsung'">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Rincian Alokasi Biaya (Keranjang)</h3>
                                    <SecondaryButton type="button" @click="openModalUntukTambah">
                                        <PlusIcon class="w-4 h-4 mr-2" />
                                        Tambah Item
                                    </SecondaryButton>
                                </div>
                                <InputError class="mt-2 mb-2" :message="frontendErrors['items'] || form.errors.items" />
                                
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akun Biaya</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nominal</th>
                                                <th class="relative px-6 py-3"><span class="sr-only">Hapus</span></th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-if="form.items.length === 0">
                                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                                    Keranjang rincian masih kosong.
                                                </td>
                                            </tr>
                                            <tr v-for="(item, index) in form.items" :key="index">
                                                <td class="px-6 py-4 text-sm text-gray-900">{{ item.deskripsi_item }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-500">{{ props.masterProgram.find(p => p.id === item.id_program)?.nama_program || 'N/A' }}</td>
                                                <td class="px-6 py-4 text-sm text-gray-500">{{ props.masterAkun.find(a => a.id === item.id_akun)?.nama_akun || 'N/A' }}</td>
                                                <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ formatCurrency(item.nominal_item) }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-900" title="Hapus item">
                                                        <TrashIcon class="w-5 h-5" />
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
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
                                    <div>
                                        <InputLabel for="catatan_header" value="Catatan / Keterangan" />
                                        <textarea
                                            id="catatan_header"
                                            v-model="form.catatan_header"
                                            rows="3"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        ></textarea>
                                    </div>
                                    <div class="flex flex-col justify-end items-end">
                                        <div class="text-gray-500 text-sm">Total Pengajuan</div>
                                        <div class="text-3xl font-bold text-gray-900">
                                            {{ formatCurrency(form.total_nominal_diajukan) }}
                                        </div>
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

        <!-- MODAL -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-medium text-gray-900">Tambah Item Rincian</h2>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <div class="mt-6 space-y-4">
                    <div>
                        <InputLabel value="Deskripsi Item" class="font-bold" />
                        <TextInput type="text" class="mt-1 block w-full" v-model="modalForm.deskripsi_item" />
                        <InputError class="mt-2" :message="modalErrors.deskripsi_item" />
                    </div>
                    <div>
                        <InputLabel value="Program" class="font-bold" />
                        <select v-model="modalForm.id_program" @change="onBudgetLineChange" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option :value="null" disabled>Pilih Program</option>
                            <option v-for="program in props.masterProgram" :key="program.id" :value="program.id">{{ program.nama_program }}</option>
                        </select>
                        <InputError class="mt-2" :message="modalErrors.id_program" />
                    </div>
                    <div>
                        <InputLabel value="Akun Biaya" class="font-bold" />
                        <select v-model="modalForm.id_akun" @change="onBudgetLineChange" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option :value="null" disabled>Pilih Akun Biaya</option>
                            <option v-for="akun in props.masterAkun" :key="akun.id" :value="akun.id">{{ akun.kode_akun }} - {{ akun.nama_akun }}</option>
                        </select>
                        <InputError class="mt-2" :message="modalErrors.id_akun" />
                    </div>
                    <div>
                        <InputLabel value="Nominal" class="font-bold" />
                        <TextInput type="number" class="mt-1 block w-full" v-model.number="modalForm.nominal_item" :disabled="isLoadingSaldo || (!modalForm.id_akun || !modalForm.id_program)" />
                        <div v-if="isLoadingSaldo" class="mt-1 text-sm text-gray-500">Memeriksa sisa saldo...</div>
                        <div v-else-if="sisaSaldoSesi > 0" class="mt-1 text-sm text-green-600">Sisa Saldo: {{ formatCurrency(sisaSaldoSesi) }}</div>
                        <InputError class="mt-2" :message="modalErrors.nominal_item" />
                    </div>
                    <div class="flex justify-end pt-4">
                        <PrimaryButton type="button" @click="simpanItem" :disabled="isLoadingSaldo">Tambahkan ke Keranjang</PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>