<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    filters: Object,
    reportData: [Array, Object]
});

const form = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    type: props.filters.type
});

const applyFilter = () => {
    router.get(route('admin.reports.index'), form.value, { preserveState: true, replace: true });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

const tabs = [
    { id: 'neraca_saldo', name: 'Neraca Saldo' },
    { id: 'laba_rugi', name: 'Laba Rugi' },
    { id: 'neraca', name: 'Neraca' }
];
</script>

<template>
    <Head title="Laporan Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Laporan Keuangan</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                
                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex flex-wrap gap-4 items-end">
                        <div>
                            <InputLabel value="Jenis Laporan" />
                            <select v-model="form.type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option v-for="tab in tabs" :key="tab.id" :value="tab.id">{{ tab.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Dari Tanggal" />
                            <TextInput type="date" v-model="form.start_date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel value="Sampai Tanggal" />
                            <TextInput type="date" v-model="form.end_date" class="mt-1 block w-full" />
                        </div>
                        <PrimaryButton @click="applyFilter">Tampilkan</PrimaryButton>
                    </div>
                </div>

                <!-- Report Content -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <!-- Neraca Saldo -->
                    <div v-if="form.type === 'neraca_saldo'">
                        <h3 class="text-lg font-bold text-center mb-4 text-gray-900 dark:text-white">NERACA SALDO</h3>
                        <p class="text-center text-sm text-gray-500 mb-6">Periode: {{ form.start_date }} s/d {{ form.end_date }}</p>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kode</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nama Akun</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Saldo Awal</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Debit</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kredit</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Saldo Akhir</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="item in reportData" :key="item.kode_akun">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ item.kode_akun }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ item.nama_akun }}</td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-500">{{ formatCurrency(item.saldo_awal) }}</td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-500">{{ formatCurrency(item.debit) }}</td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-500">{{ formatCurrency(item.kredit) }}</td>
                                        <td class="px-4 py-2 text-sm text-right font-bold text-gray-900 dark:text-white">{{ formatCurrency(item.saldo_akhir) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Laba Rugi -->
                    <div v-if="form.type === 'laba_rugi'">
                        <h3 class="text-lg font-bold text-center mb-4 text-gray-900 dark:text-white">LAPORAN LABA RUGI</h3>
                        <p class="text-center text-sm text-gray-500 mb-6">Periode: {{ form.start_date }} s/d {{ form.end_date }}</p>

                        <div class="space-y-6">
                            <div>
                                <h4 class="font-bold text-gray-700 dark:text-gray-300 border-b pb-2 mb-2">PENDAPATAN</h4>
                                <table class="w-full">
                                    <tr v-for="item in reportData.pendapatan" :key="item.kode_akun">
                                        <td class="py-1 text-gray-600 dark:text-gray-400">{{ item.kode_akun }} - {{ item.nama_akun }}</td>
                                        <td class="py-1 text-right text-gray-900 dark:text-white">{{ formatCurrency(item.balance * -1) }}</td>
                                    </tr>
                                    <tr class="font-bold border-t mt-2">
                                        <td class="py-2 text-gray-900 dark:text-white">TOTAL PENDAPATAN</td>
                                        <td class="py-2 text-right text-gray-900 dark:text-white">{{ formatCurrency(reportData.total_pendapatan) }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div>
                                <h4 class="font-bold text-gray-700 dark:text-gray-300 border-b pb-2 mb-2">BEBAN</h4>
                                <table class="w-full">
                                    <tr v-for="item in reportData.beban" :key="item.kode_akun">
                                        <td class="py-1 text-gray-600 dark:text-gray-400">{{ item.kode_akun }} - {{ item.nama_akun }}</td>
                                        <td class="py-1 text-right text-gray-900 dark:text-white">{{ formatCurrency(item.balance) }}</td>
                                    </tr>
                                    <tr class="font-bold border-t mt-2">
                                        <td class="py-2 text-gray-900 dark:text-white">TOTAL BEBAN</td>
                                        <td class="py-2 text-right text-gray-900 dark:text-white">{{ formatCurrency(reportData.total_beban) }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                <div class="flex justify-between font-bold text-lg">
                                    <span class="text-gray-900 dark:text-white">LABA BERSIH</span>
                                    <span :class="reportData.laba_bersih >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ formatCurrency(reportData.laba_bersih) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Neraca -->
                    <div v-if="form.type === 'neraca'">
                        <h3 class="text-lg font-bold text-center mb-4 text-gray-900 dark:text-white">NERACA (BALANCE SHEET)</h3>
                        <p class="text-center text-sm text-gray-500 mb-6">Per Tanggal: {{ form.end_date }}</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- ASET -->
                            <div>
                                <h4 class="font-bold text-gray-700 dark:text-gray-300 border-b pb-2 mb-2">ASET</h4>
                                <table class="w-full">
                                    <tr v-for="item in reportData.aset" :key="item.kode_akun">
                                        <td class="py-1 text-sm text-gray-600 dark:text-gray-400">{{ item.kode_akun }} - {{ item.nama_akun }}</td>
                                        <td class="py-1 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(item.balance) }}</td>
                                    </tr>
                                    <tr class="font-bold border-t mt-2">
                                        <td class="py-2 text-gray-900 dark:text-white">TOTAL ASET</td>
                                        <td class="py-2 text-right text-gray-900 dark:text-white">{{ formatCurrency(reportData.total_aset) }}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- KEWAJIBAN & EKUITAS -->
                            <div>
                                <div class="mb-6">
                                    <h4 class="font-bold text-gray-700 dark:text-gray-300 border-b pb-2 mb-2">KEWAJIBAN</h4>
                                    <table class="w-full">
                                        <tr v-for="item in reportData.kewajiban" :key="item.kode_akun">
                                            <td class="py-1 text-sm text-gray-600 dark:text-gray-400">{{ item.kode_akun }} - {{ item.nama_akun }}</td>
                                            <td class="py-1 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(item.balance * -1) }}</td>
                                        </tr>
                                        <tr class="font-bold border-t mt-2">
                                            <td class="py-2 text-gray-900 dark:text-white">TOTAL KEWAJIBAN</td>
                                            <td class="py-2 text-right text-gray-900 dark:text-white">{{ formatCurrency(reportData.total_kewajiban) }}</td>
                                        </tr>
                                    </table>
                                </div>

                                <div>
                                    <h4 class="font-bold text-gray-700 dark:text-gray-300 border-b pb-2 mb-2">EKUITAS</h4>
                                    <table class="w-full">
                                        <tr v-for="item in reportData.ekuitas" :key="item.kode_akun">
                                            <td class="py-1 text-sm text-gray-600 dark:text-gray-400">{{ item.kode_akun }} - {{ item.nama_akun }}</td>
                                            <td class="py-1 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(item.balance * -1) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="py-1 text-sm text-gray-600 dark:text-gray-400">Laba Tahun Berjalan</td>
                                            <td class="py-1 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(reportData.laba_tahun_berjalan) }}</td>
                                        </tr>
                                        <tr class="font-bold border-t mt-2">
                                            <td class="py-2 text-gray-900 dark:text-white">TOTAL EKUITAS</td>
                                            <td class="py-2 text-right text-gray-900 dark:text-white">{{ formatCurrency(reportData.total_ekuitas) }}</td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg mt-4">
                                    <div class="flex justify-between font-bold">
                                        <span class="text-gray-900 dark:text-white">TOTAL KEWAJIBAN & EKUITAS</span>
                                        <span class="text-gray-900 dark:text-white">
                                            {{ formatCurrency(reportData.total_kewajiban + reportData.total_ekuitas) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
