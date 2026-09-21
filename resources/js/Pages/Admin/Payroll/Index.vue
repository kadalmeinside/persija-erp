<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { MagnifyingGlassIcon, PlusIcon, EyeIcon, TrashIcon } from '@heroicons/vue/24/solid';
import debounce from 'lodash/debounce';

const props = defineProps({
    payrolls: Object,
    filters: Object
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('admin.payrolls.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const confirmDeletion = (e) => {
    if (!confirm('Apakah Anda yakin ingin menghapus draft payroll ini? Tindakan ini tidak dapat dibatalkan.')) {
        e.preventDefault();
        e.stopPropagation(); // Stop Inertia link from firing
    } else {
        // Allow default behavior (Inertia delete request)
    }
};
</script>

<template>
    <Head title="Payroll" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Payroll & Gaji</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
                        <div class="relative w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <MagnifyingGlassIcon class="w-5 h-5 text-gray-400" />
                            </span>
                            <input v-model="search" type="text" placeholder="Cari Periode (YYYY-MM)..." class="pl-10 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        
                        <Link :href="route('admin.payrolls.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <PlusIcon class="w-4 h-4 mr-2" /> Buat Payroll Baru
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Periode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total Gaji Bersih</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="payroll in payrolls.data" :key="payroll.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ payroll.bulan_periode }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ formatDate(payroll.tgl_payroll) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(payroll.total_gaji_bersih) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        <span :class="{
                                            'bg-green-100 text-green-800': payroll.status === 'Paid',
                                            'bg-yellow-100 text-yellow-800': payroll.status === 'Draft',
                                        }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ payroll.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link :href="route('admin.payrolls.show', payroll.id)" class="text-indigo-600 hover:text-indigo-900" title="Lihat Detail">
                                                <EyeIcon class="w-5 h-5" />
                                            </Link>
                                            <Link v-if="payroll.status === 'Draft'" :href="route('admin.payrolls.destroy', payroll.id)" method="delete" as="button" class="text-red-600 hover:text-red-900" title="Hapus Draft" preserve-scroll @click="confirmDeletion">
                                                <TrashIcon class="w-5 h-5" />
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="payrolls.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data payroll.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <Pagination :links="payrolls.links" />
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
