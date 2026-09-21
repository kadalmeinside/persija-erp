<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { MagnifyingGlassIcon, PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import CustomerModal from './CustomerModal.vue';

const props = defineProps({
    customers: Object,
    filters: Object
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('admin.customers.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const showModal = ref(false);
const selectedCustomer = ref(null);

const openCreateModal = () => {
    selectedCustomer.value = null;
    showModal.value = true;
};

const openEditModal = (customer) => {
    selectedCustomer.value = customer;
    showModal.value = true;
};

const deleteCustomer = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus pelanggan ini?')) {
        router.delete(route('admin.customers.destroy', id));
    }
};
</script>

<template>
    <Head title="Data Pelanggan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Data Pelanggan</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <div class="flex justify-between items-center mb-6">
                        <div class="relative w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <MagnifyingGlassIcon class="w-5 h-5 text-gray-400" />
                            </span>
                            <input v-model="search" type="text" placeholder="Cari Pelanggan..." class="pl-10 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        
                        <PrimaryButton @click="openCreateModal">
                            <PlusIcon class="w-4 h-4 mr-2" /> Tambah Pelanggan
                        </PrimaryButton>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kontak</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Alamat</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="customer in customers.data" :key="customer.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ customer.kode_pelanggan }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ customer.nama_pelanggan }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div>{{ customer.email }}</div>
                                        <div>{{ customer.telepon }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ customer.alamat }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openEditModal(customer)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            <PencilIcon class="w-5 h-5" />
                                        </button>
                                        <button @click="deleteCustomer(customer.id)" class="text-red-600 hover:text-red-900">
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="customers.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data pelanggan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <Pagination :links="customers.links" />
                    </div>

                </div>
            </div>
        </div>

        <!-- Customer Modal -->
        <CustomerModal 
            :show="showModal" 
            :customer="selectedCustomer"
            @close="showModal = false"
        />
    </AuthenticatedLayout>
</template>
