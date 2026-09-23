<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PlusIcon, PencilSquareIcon, TrashIcon, MapPinIcon } from '@heroicons/vue/24/solid';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    lokasiKantors: Array
});

// Modal State
const showModal = ref(false);
const isEditing = ref(false);
const modalTitle = ref('');

const form = useForm({
    id: null,
    nama_kantor: '',
    latitude: '',
    longitude: '',
    radius_meter: 50,
    is_active: true
});

const openCreateModal = () => {
    isEditing.value = false;
    modalTitle.value = 'Tambah Lokasi Kantor';
    form.reset();
    form.clearErrors();
    form.radius_meter = 50;
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    modalTitle.value = 'Edit Lokasi Kantor';
    form.id = item.id;
    form.nama_kantor = item.nama_kantor;
    form.latitude = item.latitude;
    form.longitude = item.longitude;
    form.radius_meter = item.radius_meter;
    form.is_active = !!item.is_active;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.lokasi-kantor.update', form.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                Swal.fire('Berhasil', 'Data cabang diperbarui.', 'success');
            }
        });
    } else {
        form.post(route('admin.lokasi-kantor.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                Swal.fire('Berhasil', 'Cabang baru ditambahkan.', 'success');
            }
        });
    }
};

const deleteItem = (item) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Cabang " + item.nama_kantor + " akan dihapus!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.lokasi-kantor.destroy', item.id), {
                onSuccess: () => Swal.fire('Terhapus!', 'Cabang telah dihapus.', 'success')
            });
        }
    });
};

const openMap = (lat, lng) => {
    window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
};
</script>

<template>
    <Head title="Master Lokasi Kantor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Master Lokasi Kantor (Cabang)</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <MapPinIcon class="h-5 w-5 text-blue-500" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-bold text-blue-800 dark:text-blue-300">Informasi Penting</h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-400 space-y-1">
                                <p>Sistem ini sudah mendukung <strong>Multi-Cabang Geofencing</strong>. Karyawan dapat melakukan Absen Masuk/Keluar dari cabang mana saja, selama posisi mereka berada dalam radius yang Anda tentukan di tabel ini.</p>
                                <p>Jika Anda ingin mengunci seorang karyawan agar hanya bisa absen di 1 cabang tertentu, ubah pengaturan <strong>"Penempatan Kantor"</strong> di menu Manajemen Karyawan.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Toolbar -->
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold">Daftar Cabang</h3>
                            <PrimaryButton @click="openCreateModal">
                                <PlusIcon class="w-5 h-5 mr-2" /> Tambah Cabang
                            </PrimaryButton>
                        </div>

                        <!-- Table -->
                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg border border-gray-100 dark:border-gray-700">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-200 dark:border-gray-600">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Nama Cabang</th>
                                        <th scope="col" class="px-6 py-3">Koordinat GPS</th>
                                        <th scope="col" class="px-6 py-3">Radius Toleransi</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                        <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in lokasiKantors" :key="item.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                            {{ item.nama_kantor }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span>{{ item.latitude }}, {{ item.longitude }}</span>
                                                <button @click="openMap(item.latitude, item.longitude)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-1 rounded">
                                                    <MapPinIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-indigo-600">
                                            {{ item.radius_meter }} Meter
                                        </td>
                                        <td class="px-6 py-4">
                                            <span v-if="item.is_active" class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Aktif</span>
                                            <span v-else class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">Non-Aktif</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="openEditModal(item)" class="text-indigo-600 hover:text-indigo-900 p-2 bg-indigo-50 rounded shadow-sm hover:bg-indigo-100 transition-colors">
                                                    <PencilSquareIcon class="w-4 h-4" />
                                                </button>
                                                <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900 p-2 bg-red-50 rounded shadow-sm hover:bg-red-100 transition-colors">
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="lokasiKantors.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data cabang. Silakan tambahkan.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <Modal :show="showModal" @close="closeModal" maxWidth="xl">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-2">
                    {{ modalTitle }}
                </h2>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <InputLabel for="nama_kantor" value="Nama Cabang / Kantor" />
                        <TextInput
                            id="nama_kantor"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.nama_kantor"
                            required
                            placeholder="Misal: Kantor Pusat, Cabang Sudirman"
                        />
                        <InputError class="mt-2" :message="form.errors.nama_kantor" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="latitude" value="Latitude" />
                            <TextInput
                                id="latitude"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.latitude"
                                required
                                placeholder="-6.2088"
                            />
                            <InputError class="mt-2" :message="form.errors.latitude" />
                        </div>
                        <div>
                            <InputLabel for="longitude" value="Longitude" />
                            <TextInput
                                id="longitude"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.longitude"
                                required
                                placeholder="106.8456"
                            />
                            <InputError class="mt-2" :message="form.errors.longitude" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="radius_meter" value="Radius Toleransi (Meter)" />
                        <TextInput
                            id="radius_meter"
                            type="number"
                            class="mt-1 block w-full bg-gray-50 font-bold text-indigo-700"
                            v-model="form.radius_meter"
                            required
                            min="1"
                        />
                        <p class="text-xs text-gray-500 mt-1">Jarak toleransi absensi dari titik koordinat GPS. Disarankan: 50-100 meter.</p>
                        <InputError class="mt-2" :message="form.errors.radius_meter" />
                    </div>

                    <div class="mt-4">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Cabang Aktif</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-6">Jika tidak aktif, karyawan tidak bisa absensi di cabang ini.</p>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Simpan Data
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
