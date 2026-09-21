<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    ArrowRightIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import { ArrowsRightLeftIcon } from '@heroicons/vue/24/solid';
import debounce from 'lodash/debounce';

const props = defineProps({
    transfers: Object,
    filters: Object,
    summary: Object,
});

const search    = ref(props.filters.search || '');
const status    = ref(props.filters.status || '');
const startDate = ref(props.filters.start_date || '');
const endDate   = ref(props.filters.end_date || '');

watch([search, status, startDate, endDate], debounce(() => {
    router.get(route('admin.internal-transfers.index'), {
        search: search.value,
        status: status.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, { preserveState: true, replace: true });
}, 400));

const formatCurrency = (v) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(v || 0);

const formatDate = (d) =>
    d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';

const statusConfig = {
    Draft:      { label: 'Draft',      class: 'bg-blue-100 text-blue-800',   icon: ClockIcon },
    Approved:   { label: 'Disetujui',  class: 'bg-green-100 text-green-800', icon: CheckCircleIcon },
    Cancelled:  { label: 'Dibatalkan', class: 'bg-gray-100 text-gray-600',   icon: XCircleIcon },
};
</script>

<template>
    <Head title="Internal Transfer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <ArrowsRightLeftIcon class="w-6 h-6 text-indigo-500" />
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Internal Transfer</h2>
            </div>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <ClockIcon class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Menunggu Persetujuan</p>
                            <p class="text-2xl font-bold text-blue-700">{{ summary.draft }}</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <CheckCircleIcon class="w-5 h-5 text-green-600" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Disetujui</p>
                            <p class="text-2xl font-bold text-green-700">{{ summary.approved }}</p>
                        </div>
                    </div>
                </div>

                <!-- Main Table Card -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6">

                    <!-- Filter & Aksi Bar -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div class="flex flex-wrap gap-3 w-full md:w-auto">
                            <!-- Search -->
                            <div class="relative w-full md:w-64">
                                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Cari nomor / keterangan..."
                                    class="pl-9 w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <!-- Status Filter -->
                            <select v-model="status" class="border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Semua Status</option>
                                <option value="Draft">Draft (Menunggu)</option>
                                <option value="Approved">Disetujui</option>
                                <option value="Cancelled">Dibatalkan</option>
                            </select>

                            <!-- Date Range -->
                            <div class="flex items-center gap-2">
                                <input v-model="startDate" type="date" class="border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm" />
                                <span class="text-gray-400">—</span>
                                <input v-model="endDate" type="date" class="border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm" />
                            </div>
                        </div>

                        <Link
                            :href="route('admin.internal-transfers.create')"
                            class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700 transition"
                        >
                            <PlusIcon class="w-4 h-4" /> Buat Transfer
                        </Link>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Transfer</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dari</th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ke</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nominal</th>
                                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                <tr v-for="t in transfers.data" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <td class="px-5 py-3 text-sm font-mono font-medium text-gray-900 dark:text-white">
                                        {{ t.nomor_transfer }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatDate(t.tgl_transfer) }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-800 dark:text-gray-200">
                                        <p class="font-medium">{{ t.from_kas_bank?.nama_bank }}</p>
                                        <p class="text-xs text-gray-400">{{ t.from_kas_bank?.nomor_rekening }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <ArrowRightIcon class="w-4 h-4 text-gray-400 mx-auto" />
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-800 dark:text-gray-200">
                                        <p class="font-medium">{{ t.to_kas_bank?.nama_bank }}</p>
                                        <p class="text-xs text-gray-400">{{ t.to_kas_bank?.nomor_rekening }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(t.nominal) }}
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span
                                            :class="[statusConfig[t.status]?.class || 'bg-gray-100 text-gray-600', 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold']"
                                        >
                                            <component :is="statusConfig[t.status]?.icon" class="w-3 h-3" />
                                            {{ statusConfig[t.status]?.label || t.status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <Link
                                            :href="route('admin.internal-transfers.show', t.id)"
                                            class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                        >
                                            <EyeIcon class="w-4 h-4" /> Detail
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="transfers.data.length === 0">
                                    <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-400">
                                        Belum ada data Internal Transfer.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <Pagination :links="transfers.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
