<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    accounts: Array
});

const form = useForm({
    nama_aset: '',
    kategori: 'Elektronik',
    tgl_perolehan: new Date().toISOString().substr(0, 10),
    harga_perolehan: 0,
    nilai_sisa: 0,
    umur_manfaat_bulan: 12,
    metode_penyusutan: 'Straight Line',
    id_akun_aset: '',
    id_akun_akumulasi_penyusutan: '',
    id_akun_beban_penyusutan: '',
    deskripsi: ''
});

const submit = () => {
    form.post(route('admin.assets.store'));
};
</script>

<template>
    <Head title="Registrasi Aset Baru" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Registrasi Aset Baru</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Informasi Dasar -->
                            <div class="col-span-2">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-2">Informasi Aset</h3>
                            </div>

                            <div>
                                <InputLabel value="Nama Aset" />
                                <TextInput type="text" v-model="form.nama_aset" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel value="Kategori" />
                                <select v-model="form.kategori" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="Elektronik">Elektronik</option>
                                    <option value="Furniture">Furniture</option>
                                    <option value="Kendaraan">Kendaraan</option>
                                    <option value="Bangunan">Bangunan</option>
                                    <option value="Mesin">Mesin</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Tanggal Perolehan" />
                                <TextInput type="date" v-model="form.tgl_perolehan" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel value="Deskripsi" />
                                <TextInput type="text" v-model="form.deskripsi" class="mt-1 block w-full" />
                            </div>

                            <!-- Nilai & Penyusutan -->
                            <div class="col-span-2 mt-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-2">Nilai & Penyusutan</h3>
                            </div>

                            <div>
                                <InputLabel value="Harga Perolehan (Cost)" />
                                <TextInput type="number" v-model="form.harga_perolehan" class="mt-1 block w-full" min="0" required />
                            </div>
                            <div>
                                <InputLabel value="Nilai Sisa (Salvage Value)" />
                                <TextInput type="number" v-model="form.nilai_sisa" class="mt-1 block w-full" min="0" required />
                            </div>
                            <div>
                                <InputLabel value="Umur Manfaat (Bulan)" />
                                <TextInput type="number" v-model="form.umur_manfaat_bulan" class="mt-1 block w-full" min="1" required />
                                <p class="text-xs text-gray-500 mt-1">Contoh: 5 Tahun = 60 Bulan</p>
                            </div>
                            <div>
                                <InputLabel value="Metode Penyusutan" />
                                <TextInput type="text" v-model="form.metode_penyusutan" class="mt-1 block w-full bg-gray-100" readonly />
                            </div>

                            <!-- Mapping Akun GL -->
                            <div class="col-span-2 mt-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-2">Mapping Akun GL</h3>
                            </div>

                            <div>
                                <InputLabel value="Akun Aset Tetap" />
                                <select v-model="form.id_akun_aset" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled>Pilih Akun</option>
                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.kode_akun }} - {{ acc.nama_akun }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Akun Akumulasi Penyusutan" />
                                <select v-model="form.id_akun_akumulasi_penyusutan" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled>Pilih Akun</option>
                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.kode_akun }} - {{ acc.nama_akun }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Akun Beban Penyusutan" />
                                <select v-model="form.id_akun_beban_penyusutan" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled>Pilih Akun</option>
                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.kode_akun }} - {{ acc.nama_akun }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <Link :href="route('admin.assets.index')" class="text-gray-600 hover:text-gray-900">Batal</Link>
                            <PrimaryButton :disabled="form.processing">
                                Simpan Aset
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
