<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    asset: Object,
    currentBookValue: Number
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head :title="`Aset ${asset.kode_aset}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Detail Aset</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6">
                    <Link :href="route('admin.assets.index')" class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        <ArrowLeftIcon class="w-4 h-4 mr-2" /> Kembali
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Asset Info -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex justify-between mb-6">
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ asset.nama_aset }}</h1>
                                    <p class="text-sm text-gray-500">{{ asset.kode_aset }} | {{ asset.kategori }}</p>
                                </div>
                                <div class="text-right">
                                    <span :class="{
                                        'bg-green-100 text-green-800': asset.status === 'Active',
                                        'bg-gray-100 text-gray-800': asset.status === 'Fully Depreciated',
                                        'bg-red-100 text-red-800': asset.status === 'Disposed',
                                    }" class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full">
                                        {{ asset.status }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Harga Perolehan</h4>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(asset.harga_perolehan) }}</p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Nilai Buku Saat Ini</h4>
                                    <p class="text-lg font-bold text-indigo-600">{{ formatCurrency(currentBookValue) }}</p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Tanggal Perolehan</h4>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ formatDate(asset.tgl_perolehan) }}</p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Umur Manfaat</h4>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ asset.umur_manfaat_bulan }} Bulan</p>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2">Konfigurasi GL</h3>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li><span class="font-medium">Akun Aset:</span> {{ asset.akun_aset?.kode_akun }} - {{ asset.akun_aset?.nama_akun }}</li>
                                    <li><span class="font-medium">Akun Akumulasi:</span> {{ asset.akun_akumulasi?.kode_akun }} - {{ asset.akun_akumulasi?.nama_akun }}</li>
                                    <li><span class="font-medium">Akun Beban:</span> {{ asset.akun_beban?.kode_akun }} - {{ asset.akun_beban?.nama_akun }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Depreciation History -->
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">Riwayat Penyusutan</h3>
                            
                            <div v-if="asset.penyusutan.length > 0" class="space-y-4 max-h-96 overflow-y-auto">
                                <div v-for="dep in asset.penyusutan" :key="dep.id" class="border-l-4 border-indigo-500 pl-4 py-2">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ formatCurrency(dep.nilai_penyusutan) }}</p>
                                    <p class="text-xs text-gray-500">{{ formatDate(dep.tgl_penyusutan) }}</p>
                                    <p class="text-xs text-gray-500">Nilai Buku: {{ formatCurrency(dep.nilai_buku_akhir) }}</p>
                                </div>
                            </div>
                            <p v-else class="text-sm text-gray-500 italic">Belum ada data penyusutan.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
