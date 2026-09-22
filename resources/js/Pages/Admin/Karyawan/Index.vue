<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EmployeeFormModal from '@/Components/EmployeeFormModal.vue';

import { MagnifyingGlassIcon, PlusIcon, PencilSquareIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/solid';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    karyawans: Object,
    departemens: Array,
    filters: Object
});

const search = ref(props.filters.search || '');
const filterDepartemen = ref(props.filters.departemen || '');
const filterStatusAktif = ref(props.filters.status_aktif || 'aktif');

watch([search, filterDepartemen, filterStatusAktif], ([newSearch, newDept, newStatus]) => {
    router.get(route('admin.karyawan.index'), { search: newSearch, departemen: newDept, status_aktif: newStatus }, { preserveState: true, replace: true });
});

// Modal State
const showModal = ref(false);
const selectedEmployee = ref(null);

const openCreateModal = () => {
    selectedEmployee.value = null;
    showModal.value = true;
};

const openEditModal = (item) => {
    selectedEmployee.value = item;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedEmployee.value = null;
};

const refreshData = () => {
    router.reload({ only: ['karyawans'] });
};

const formatDateShort = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const isContractEndingSoon = (dateString) => {
    if (!dateString) return false;
    const endDate = new Date(dateString);
    const today = new Date();
    const diffTime = endDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays >= 0 && diffDays <= 30;
};

const deleteItem = (item) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data karyawan akan dihapus!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.karyawan.destroy', item.id), {
                onSuccess: () => Swal.fire('Terhapus!', 'Data karyawan telah dihapus.', 'success')
            });
        }
    });
};
</script>

<template>
    <Head title="Manajemen Karyawan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Karyawan</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Status Tabs -->
                        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="status-tab" role="tablist">
                                <li class="mr-2" role="presentation">
                                    <button 
                                        @click="filterStatusAktif = 'aktif'" 
                                        :class="filterStatusAktif === 'aktif' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                                        class="inline-block p-4 border-b-2 rounded-t-lg" type="button">
                                        Karyawan Aktif
                                    </button>
                                </li>
                                <li class="mr-2" role="presentation">
                                    <button 
                                        @click="filterStatusAktif = 'non-aktif'" 
                                        :class="filterStatusAktif === 'non-aktif' ? 'border-red-500 text-red-600 dark:text-red-400' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                                        class="inline-block p-4 border-b-2 rounded-t-lg" type="button">
                                        Mantan Karyawan (Non-Aktif)
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <!-- Toolbar -->
                        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                            <div class="flex gap-2 w-full md:w-auto">
                                <div class="relative w-full md:w-64">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <MagnifyingGlassIcon class="w-5 h-5 text-gray-500" />
                                    </div>
                                    <input 
                                        v-model="search" 
                                        type="text" 
                                        class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5" 
                                        placeholder="Cari nama atau NIK..." 
                                    />
                                </div>
                                <select v-model="filterDepartemen" class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                                    <option value="">Semua Departemen</option>
                                    <option v-for="dept in departemens" :key="dept.id" :value="dept.id">{{ dept.nama_departemen }}</option>
                                </select>
                            </div>
                            <PrimaryButton @click="openCreateModal">
                                <PlusIcon class="w-5 h-5 mr-2" /> Tambah Karyawan
                            </PrimaryButton>
                        </div>

                        <!-- Table -->
                        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">NIK</th>
                                        <th scope="col" class="px-6 py-3">Nama Lengkap</th>
                                        <th scope="col" class="px-6 py-3">Departemen</th>
                                        <th scope="col" class="px-6 py-3">Jabatan</th>
                                        <th scope="col" class="px-6 py-3">Status & Kontrak</th>
                                        <th scope="col" class="px-6 py-3">User Akun</th>
                                        <th scope="col" class="px-6 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in karyawans.data" :key="item.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ item.nomor_induk_karyawan }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <img :src="item.foto_url" :alt="item.nama_lengkap" class="w-10 h-10 rounded-full object-cover shadow-sm border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800" />
                                                <div class="font-medium text-gray-900 dark:text-white">{{ item.nama_lengkap }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">{{ item.departemen?.nama_departemen || '-' }}</td>
                                        <td class="px-6 py-4">{{ item.jabatan }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col gap-1">
                                                <span class="font-medium" :class="item.status_karyawan === 'Tetap' ? 'text-indigo-600 dark:text-indigo-400' : 'text-blue-600 dark:text-blue-400'">
                                                    {{ item.status_karyawan }}
                                                </span>
                                                <span v-if="item.status_karyawan === 'Tetap'" class="text-xs text-gray-500 flex items-center gap-1" title="Tidak Terbatas">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M12 12c-2-2.67-4-4-6-4a4 4 0 1 0 0 8c2 0 4-1.33 6-4Zm0 0c2 2.67 4 4 6 4a4 4 0 1 0 0-8c-2 0-4 1.33-6 4Z"/></svg>
                                                </span>
                                                <span v-else class="text-xs flex items-center gap-1" :class="isContractEndingSoon(item.tanggal_berakhir_kontrak) ? 'text-red-600 font-bold' : 'text-gray-500'">
                                                    S/D: {{ formatDateShort(item.tanggal_berakhir_kontrak) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span v-if="item.user" class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Active</span>
                                            <span v-else class="bg-gray-100 text-gray-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">No User</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <Link :href="route('admin.karyawan.show', item.id)" class="text-green-600 hover:text-green-900">
                                                    <EyeIcon class="w-5 h-5" />
                                            </Link>
                                            <button @click="openEditModal(item)" class="text-indigo-600 hover:text-indigo-900">
                                                <PencilSquareIcon class="w-5 h-5" />
                                            </button>
                                            <button @click="deleteItem(item)" class="text-red-600 hover:text-red-900">
                                                <TrashIcon class="w-5 h-5" />
                                            </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="karyawans.data.length === 0">
                                        <td colspan="7" class="px-6 py-4 text-center">Tidak ada data karyawan ditemukan.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <Pagination :links="karyawans.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <EmployeeFormModal 
            :show="showModal" 
            :employee="selectedEmployee" 
            :departemens="departemens"
            @close="closeModal"
            @saved="refreshData"
        />
    </AuthenticatedLayout>
</template>
