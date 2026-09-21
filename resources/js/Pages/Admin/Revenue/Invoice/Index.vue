<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import { MagnifyingGlassIcon, PlusIcon, EyeIcon, PencilSquareIcon, TrashIcon, FunnelIcon, XCircleIcon, PrinterIcon } from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    invoices: Object,
    filters: Object,
    summary: Object
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

watch([search, status, startDate, endDate], debounce(() => {
    router.get(route('admin.invoices.index'), { 
        search: search.value, 
        status: status.value,
        start_date: startDate.value,
        end_date: endDate.value
    }, { preserveState: true, replace: true });
}, 500));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const deleteForm = useForm({});

const confirmDelete = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus invoice ini? Tindakan ini akan membatalkan jurnal dan anggaran terkait.')) {
        deleteForm.delete(route('admin.invoices.destroy', id));
    }
};

const confirmCancel = (id) => {
    if (confirm('Apakah Anda yakin ingin membatalkan (VOID) invoice ini? Status akan menjadi Cancelled dan jurnal akan dibalik.')) {
        router.put(route('admin.invoices.cancel', id));
    }
};
</script>

<template>
    <Head title="Daftar Invoice" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Daftar Invoice</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                        <p class="text-sm font-medium text-gray-500">Total Pendapatan (Filtered)</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(summary.total_revenue) }}</p>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-red-500">
                        <p class="text-sm font-medium text-gray-500">Belum Dibayar (Unpaid)</p>
                        <p class="text-2xl font-bold text-red-600 mt-1">{{ formatCurrency(summary.total_unpaid) }}</p>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-orange-500">
                        <p class="text-sm font-medium text-gray-500">Jatuh Tempo (Overdue)</p>
                        <p class="text-2xl font-bold text-orange-600 mt-1">{{ formatCurrency(summary.total_overdue) }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <!-- Filters & Actions -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div class="flex flex-wrap gap-4 w-full md:w-auto">
                            <div class="relative w-full md:w-64">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                    <MagnifyingGlassIcon class="w-5 h-5 text-gray-400" />
                                </span>
                                <input v-model="search" type="text" placeholder="Cari Invoice / Pelanggan..." class="pl-10 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            
                            <select v-model="status" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Semua Status</option>
                                <option value="Draft">Draft (Menunggu Persetujuan)</option>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Partial">Partial</option>
                                <option value="Paid">Paid</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>

                            <div class="flex items-center gap-2">
                                <input v-model="startDate" type="date" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <span class="text-gray-500">-</span>
                                <input v-model="endDate" type="date" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>
                        </div>
                        
                        <Link :href="route('admin.invoices.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <PlusIcon class="w-4 h-4 mr-2" />
                            Buat Invoice
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">No. Invoice</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pelanggan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total Tagihan</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sisa Tagihan</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="invoice in invoices.data" :key="invoice.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ invoice.nomor_invoice }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ invoice.pelanggan?.nama_pelanggan }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        <div>{{ formatDate(invoice.tgl_invoice) }}</div>
                                        <div v-if="invoice.status === 'Unpaid' && new Date(invoice.tgl_jatuh_tempo) < new Date()" class="text-xs text-red-500 font-bold">
                                            Overdue: {{ formatDate(invoice.tgl_jatuh_tempo) }}
                                        </div>
                                        <div v-else class="text-xs text-gray-400">
                                            Jatuh Tempo: {{ formatDate(invoice.tgl_jatuh_tempo) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="{
                                            'bg-blue-100 text-blue-800': invoice.status === 'Draft',
                                            'bg-red-100 text-red-800': invoice.status === 'Unpaid',
                                            'bg-yellow-100 text-yellow-800': invoice.status === 'Partial',
                                            'bg-green-100 text-green-800': invoice.status === 'Paid',
                                            'bg-gray-100 text-gray-800': invoice.status === 'Cancelled',
                                        }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ invoice.status === 'Draft' ? 'Draft (Menunggu Persetujuan)' : invoice.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(invoice.total_tagihan) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white">{{ formatCurrency(invoice.sisa_tagihan) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end gap-2">
                                        <Link :href="route('admin.invoices.show', invoice.id)" class="text-indigo-600 hover:text-indigo-900" title="Lihat Detail">
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <a :href="route('admin.invoices.print', invoice.id)" target="_blank" class="text-gray-600 hover:text-gray-900" title="Print PDF">
                                            <PrinterIcon class="w-5 h-5" />
                                        </a>
                                        
                                        <!-- Draft: bisa edit & hapus (belum ada GL) -->
                                        <template v-if="invoice.status === 'Draft'">
                                            <Link :href="route('admin.invoices.edit', invoice.id)" class="text-yellow-600 hover:text-yellow-900" title="Edit Invoice Draft">
                                                <PencilSquareIcon class="w-5 h-5" />
                                            </Link>
                                            <button @click="confirmDelete(invoice.id)" class="text-red-600 hover:text-red-900" title="Hapus Invoice Draft">
                                                <TrashIcon class="w-5 h-5" />
                                            </button>
                                        </template>
                                        <!-- Unpaid: bisa void & hapus, tidak bisa edit -->
                                        <template v-else-if="invoice.status === 'Unpaid'">
                                            <button @click="confirmCancel(invoice.id)" class="text-gray-600 hover:text-gray-900" title="Batalkan Invoice (Void)">
                                                <XCircleIcon class="w-5 h-5" />
                                            </button>
                                            <button @click="confirmDelete(invoice.id)" class="text-red-600 hover:text-red-900" title="Hapus Invoice">
                                                <TrashIcon class="w-5 h-5" />
                                            </button>
                                        </template>
                                    </td>
                                </tr>
                                <tr v-if="invoices.data.length === 0">
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data invoice.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <Pagination :links="invoices.links" />
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
