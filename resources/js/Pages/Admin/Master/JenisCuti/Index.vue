<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    jenisCuti: Array
});

const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
    id: null,
    nama_cuti: '',
    kuota_default: '',
    bisa_mundur: false,
    khusus_perempuan: false,
    is_unlimited: false,
    wajib_lampiran: false
});

watch(() => form.is_unlimited, (val) => {
    if (val) {
        form.kuota_default = 0;
    }
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    form.id = item.id;
    form.nama_cuti = item.nama_cuti;
    form.kuota_default = item.kuota_default;
    form.bisa_mundur = !!item.bisa_mundur;
    form.khusus_perempuan = !!item.khusus_perempuan;
    form.is_unlimited = !!item.is_unlimited;
    form.wajib_lampiran = !!item.wajib_lampiran;
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.jenis-cuti.update', form.id), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('admin.jenis-cuti.store'), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    }
};

const confirmDelete = (id) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.delete(route('admin.jenis-cuti.destroy', id));
        }
    });
};
</script>

<template>
    <Head title="Master Jenis Cuti" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Master Jenis Cuti</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Daftar Jenis Cuti</h3>
                        <PrimaryButton @click="openCreateModal">
                            <PlusIcon class="w-5 h-5 mr-2" /> Tambah Jenis Cuti
                        </PrimaryButton>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Cuti</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kuota Default (Hari)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aturan</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="item in jenisCuti" :key="item.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ item.nama_cuti }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <span v-if="item.is_unlimited" class="text-green-600 font-bold">Unlimited</span>
                                        <span v-else>{{ item.kuota_default }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 space-x-1">
                                        <span v-if="item.bisa_mundur" class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Backdate</span>
                                        <span v-if="item.wajib_lampiran" class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs">Wajib Lampiran</span>
                                        <span v-if="item.khusus_perempuan" class="px-2 py-1 bg-pink-100 text-pink-800 rounded-full text-xs">Perempuan</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button @click="openEditModal(item)" class="text-indigo-600 hover:text-indigo-900">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                        <button @click="confirmDelete(item.id)" class="text-red-600 hover:text-red-900">
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="jenisCuti.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data jenis cuti.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    {{ isEditing ? 'Edit' : 'Tambah' }} Jenis Cuti
                </h2>
                
                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <InputLabel for="nama_cuti" value="Nama Cuti" />
                        <TextInput id="nama_cuti" v-model="form.nama_cuti" type="text" class="mt-1 block w-full" placeholder="Contoh: Cuti Tahunan" required />
                        <InputError :message="form.errors.nama_cuti" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <InputLabel for="kuota_default" value="Kuota Default (Hari)" />
                        <TextInput id="kuota_default" v-model="form.kuota_default" type="number" class="mt-1 block w-full" placeholder="Contoh: 12" :disabled="form.is_unlimited" :required="!form.is_unlimited" />
                        <InputError :message="form.errors.kuota_default" class="mt-2" />
                    </div>

                    <div class="mb-6 grid grid-cols-2 gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.bisa_mundur" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Bisa Mundur (Backdate)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.is_unlimited" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Tanpa Batas Kuota (Unlimited)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.wajib_lampiran" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Wajib Lampiran (Surat Dokter, dll)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.khusus_perempuan" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Khusus Karyawan Perempuan</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-4">
                        <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
