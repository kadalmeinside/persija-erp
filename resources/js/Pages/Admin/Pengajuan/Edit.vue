<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';

// 1. Mendefinisikan Props
const props = defineProps({
    pengajuan: Object, 
    masterAkun: Array,
    masterProgram: Array,
    masterPajak: Array,
    karyawan: Object // PERBAIKAN: Menjadi 'karyawan'
});

// Helper untuk format mata uang
const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

// 2. Inisialisasi Form
const form = useForm({
    tgl_pengajuan: props.pengajuan.tgl_pengajuan.split('T')[0], 
    id_pengaju: props.karyawan.id, // PERBAIKAN: Menjadi 'id_pengaju'
    id_departemen: props.karyawan.id_departemen,
    tipe_pengajuan: props.pengajuan.tipe_pengajuan,
    catatan_header: props.pengajuan.catatan_header,
    total_nominal_diajukan: props.pengajuan.total_nominal_diajukan,
    
    items: props.pengajuan.detail.map(item => ({
        id: item.id, 
        deskripsi_item: item.deskripsi_item,
        nominal_item: parseFloat(item.nominal_item),
        id_program: item.id_program,
        id_akun: item.id_akun,
        id_pajak: item.id_pajak
    }))
});

// 3. Logika Tambah/Hapus Baris
const addItem = () => {
    form.items.push({
        id: null, 
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
const totalItems = computed(() => {
    return form.items.reduce((total, item) => {
        return total + (parseFloat(item.nominal_item) || 0);
    }, 0);
});

// 5. Watcher (Pengamat)
watch(() => form.tipe_pengajuan, (newType) => {
    if (newType === 'UangMuka') {
        form.total_nominal_diajukan = 0;
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

// 6. Fungsi Submit (Menggunakan PUT)
const submit = () => {
    if (form.tipe_pengajuan === 'Langsung') {
        form.total_nominal_diajukan = totalItems.value;
    }
    
    form.put(route('admin.pengajuan.update', props.pengajuan.id), {
        onSuccess: () => {
            // Ditangani Controller
        },
        onError: (errors) => {
            console.error("Error updating form:", errors);
        }
    });
};

</script>

<template>
    <Head :title="`Edit Pengajuan ${pengajuan.nomor_pengajuan}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Pengajuan: {{ pengajuan.nomor_pengajuan }}
                </h2>
                <Link :href="route('admin.pengajuan.show', pengajuan.id)" 
                      class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    <ArrowUturnLeftIcon class="w-4 h-4 mr-2" />
                    Batal
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <!-- Menampilkan Error Global (misal: Budget Gagal) -->
                        <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                            {{ $page.props.flash.error }}
                        </div>

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
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm bg-gray-100"
                                        disabled 
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
                                        :value="props.karyawan.departemen.nama_departemen"
                                        disabled
                                    />
                                </div>
                            </div>

                            <!-- Opsi 1: Rincian Item (Jika 'Payment Request') -->
                            <div v-if="form.tipe_pengajuan === 'Langsung'" class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Rincian Item Pembayaran</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akun Biaya</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pajak</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nominal</th>
                                                <th class="relative px-6 py-3">
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
                                    Simpan Perubahan
                                </PrimaryButton>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>