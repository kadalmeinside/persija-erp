<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    journal: Object
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head :title="`Jurnal ${journal.nomor_jurnal}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Detail Jurnal</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6">
                    <Link :href="route('admin.journals.index')" class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        <ArrowLeftIcon class="w-4 h-4 mr-2" /> Kembali ke Daftar Jurnal
                    </Link>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ journal.nomor_jurnal }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal: {{ formatDate(journal.tgl_jurnal) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Dibuat Oleh: {{ journal.pembuat?.name || '-' }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                {{ journal.status }}
                            </span>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tipe: {{ journal.tipe_transaksi }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Modul: {{ journal.sumber_modul }}</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300">Keterangan:</h4>
                        <p class="text-gray-900 dark:text-white">{{ journal.deskripsi_jurnal }}</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kode Akun</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Akun</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Keterangan Baris</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Debit</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kredit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="detail in journal.detail" :key="detail.id">
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ detail.akun?.kode_akun }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ detail.akun?.nama_akun }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ detail.keterangan_baris || '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">{{ parseFloat(detail.debit) > 0 ? formatCurrency(detail.debit) : '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">{{ parseFloat(detail.kredit) > 0 ? formatCurrency(detail.kredit) : '-' }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700 font-bold">
                                <tr>
                                    <td colspan="3" class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">Total</td>
                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">
                                        {{ formatCurrency(journal.detail.reduce((sum, item) => sum + parseFloat(item.debit), 0)) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">
                                        {{ formatCurrency(journal.detail.reduce((sum, item) => sum + parseFloat(item.kredit), 0)) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
