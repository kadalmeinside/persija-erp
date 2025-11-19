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

// 1. Mendefinisikan Props yang diterima dari Controller
const props = defineProps({
    masterAkun: Array,
    masterProgram: Array,
    masterPajak: Array,
    karyawanLogin: Object
});

// Helper untuk format mata uang
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

// 2. Inisialisasi Form (useForm dari Inertia)
const form = useForm({
    tgl_pengajuan: new Date().toISOString().split('T')[0],
    id_pengaju: props.karyawanLogin.id,
    id_departemen: props.karyawanLogin.id_departemen,
    tipe_pengajuan: 'Langsung', // Default 'Langsung' atau 'UangMuka'
    catatan_header: '',
    total_nominal_diajukan: 0,
    items: [
        // Item default saat 'Langsung' dipilih
        { deskripsi_item: '', nominal_item: 0, id_program: null, id_akun: null, id_pajak: null }
    ]
});

// 3. Logika untuk menambah/menghapus baris item
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

// 4. Logika Komputasi Total
// Menghitung total HANYA dari rincian item
const totalItems = computed(() => {
    return form.items.reduce((total, item) => {
        return total + (parseFloat(item.nominal_item) || 0);
    }, 0);
});

// 5. Watcher (Pengamat)
// Mengawasi perubahan tipe_pengajuan untuk menyesuaikan data form
watch(() => form.tipe_pengajuan, (newType) => {
    if (newType === 'UangMuka') {
        // Jika Uang Muka, total di-reset dan diisi manual
        form.total_nominal_diajukan = 0;
    } else {
        // Jika Langsung, total diambil dari komputasi item
        form.total_nominal_diajukan = totalItems.value;
    }
});

// Mengawasi totalItems dan memperbarui form.total_nominal_diajukan HANYA jika 'Langsung'
watch(totalItems, (newTotal) => {
    if (form.tipe_pengajuan === 'Langsung') {
        form.total_nominal_diajukan = newTotal;
    }
});

// 6. Fungsi Submit
const submit = () => {
    // Memastikan total_nominal_diajukan sudah benar sebelum submit
    if (form.tipe_pengajuan === 'Langsung') {
        form.total_nominal_diajukan = totalItems.value;
    }
    
    form.post(route('admin.pengajuan.store'), {
        onSuccess: () => {
            // Mungkin reset form atau notifikasi
        },
        onError: (errors) => {
            console.error("Error submitting form:", errors);
        }
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <!-- Form Utama -->
                        <form @submit.prevent="submit">
                            
                            <!-- Bagian Header Form -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
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
                                    <InputError class="mt-2" :message="form.errors.tipe_pengajuan" />
                                </div>

                                <!-- Tanggal Pengajuan -->
                                <div>
                                    <InputLabel for="tgl_pengajuan" value="Tanggal Pengajuan" />
                                    <TextInput
                                        id="tgl_pengajuan"
                                        type="date"
                                        class="mt-1 block w-full"
                                        v-model="form.tgl_pengajuan"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.tgl_pengajuan" />
                                </div>

                                <!-- Departemen (Read Only) -->
                                <div>
                                    <InputLabel for="departemen" value="Departemen" />
                                    <TextInput
                                        id="departemen"
                                        type="text"
                                        class="mt-1 block w-full bg-gray-100"
                                        :value="props.karyawanLogin.nama_departemen"
                                        disabled
                                    />
                                </div>
                            </div>

                            <!-- Bagian Logika Kondisional -->
                            
                            <!-- Opsi 1: Rincian Item (Jika 'Payment Request') -->
                            <div v-if="form.tipe_pengajuan === 'Langsung'" class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Rincian Item Pembayaran</h3>
                                <div class="overflow-x-auto">
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
                                                    <TextInput type="text" v-model="item.deskripsi_item" class="w-full" placeholder="Deskripsi item..." required />
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <select v-model="item.id_program" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                                        <option :value="null" disabled>Pilih Program</option>
                                                        <option v-for="program in props.masterProgram" :key="program.id" :value="program.id">{{ program.nama_program }}</option>
                                                    </select>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <select v-model="item.id_akun" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                                        <option :value="null" disabled>Pilih Akun Biaya</option>
                                                        <option v-for="akun in props.masterAkun" :key="akun.id" :value="akun.id">{{ akun.kode_akun }} - {{ akun.nama_akun }}</option>
                                                    </select>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                     <select v-model="item.id_pajak" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                        <option :value="null">Tanpa Pajak</option>
                                                        <option v-for="pajak in props.masterPajak" :key="pajak.id" :value="pajak.id">{{ pajak.kode_pajak }}</option>
                                                    </select>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <TextInput type="number" v-model="item.nominal_item" class="w-full" placeholder="0" required />
                                                </td>
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
                            <div v-if="form.tipe_pengajuan === 'UangMuka'" class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Detail Uang Muka</h3>
                                <div>
                                    <InputLabel for="total_nominal_diajukan" value="Jumlah Uang Muka" class="font-bold" />
                                    <TextInput
                                        id="total_nominal_diajukan"
                                        type="number"
                                        class="mt-1 block w-full md:w-1/3"
                                        v-model.number="form.total_nominal_diajukan"
                                        placeholder="0"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.total_nominal_diajukan" />
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