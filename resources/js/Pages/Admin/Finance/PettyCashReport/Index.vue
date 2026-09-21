<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import {
    BanknotesIcon,
    FunnelIcon,
    PrinterIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    ArrowTrendingDownIcon,
    DocumentTextIcon,
    BuildingOfficeIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    filters: Object,
    daftarKasKecil: Array,
    kasKecilTerpilih: Object,
    saldoAwal: Number,
    saldoAkhir: Number,
    totalPengeluaran: Number,
    transaksi: Array,
    perDepartemen: Array,
    perAkun: Array,
});

// --- Filter State ---
const filterStartDate  = ref(props.filters.start_date);
const filterEndDate    = ref(props.filters.end_date);
const filterKasKecil   = ref(props.filters.id_kas_kecil);
const activeTab        = ref('buku'); // 'buku' | 'departemen' | 'akun'
const expandedRows     = ref(new Set());

const applyFilter = () => {
    router.get(route('admin.petty-cash-report.index'), {
        start_date:   filterStartDate.value,
        end_date:     filterEndDate.value,
        id_kas_kecil: filterKasKecil.value,
    }, { preserveScroll: true });
};

const toggleRow = (id) => {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
};

const formatCurrency = (v) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v ?? 0);

const formatDate = (d) => {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const printReport = () => window.print();

// Hitung persentase untuk bar chart
const maxDept = computed(() => Math.max(...(props.perDepartemen || []).map(d => d.total), 1));
const maxAkun = computed(() => Math.max(...(props.perAkun || []).map(a => a.total), 1));
</script>

<template>
    <Head title="Laporan Petty Cash" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <BanknotesIcon class="w-6 h-6 text-amber-500" />
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Laporan Petty Cash (Kas Kecil)
                    </h2>
                </div>
                <button
                    @click="printReport"
                    class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm print:hidden"
                >
                    <PrinterIcon class="w-4 h-4" />
                    Cetak
                </button>
            </div>
        </template>

        <div class="pb-12 pt-4 print:p-0">
            <div class="max-w-7xl mx-auto space-y-5 print:max-w-full">

                <!-- FILTER PANEL -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5 print:hidden">
                    <div class="flex items-center gap-2 mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <FunnelIcon class="w-4 h-4 text-amber-500" />
                        Filter Laporan
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Kas Kecil</label>
                            <select v-model="filterKasKecil" class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:border-amber-500 focus:ring-amber-500">
                                <option v-for="k in daftarKasKecil" :key="k.id" :value="k.id">{{ k.nama_bank }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Dari Tanggal</label>
                            <input type="date" v-model="filterStartDate" class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:border-amber-500 focus:ring-amber-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Sampai Tanggal</label>
                            <input type="date" v-model="filterEndDate" class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm focus:border-amber-500 focus:ring-amber-500" />
                        </div>
                        <div class="flex items-end">
                            <button @click="applyFilter" class="w-full px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm rounded-md shadow-sm transition">
                                Tampilkan
                            </button>
                        </div>
                    </div>
                </div>

                <!-- JUDUL CETAK -->
                <div class="hidden print:block text-center pb-4 border-b-2 border-gray-800 mb-4">
                    <h1 class="text-xl font-bold">LAPORAN PETTY CASH (KAS KECIL)</h1>
                    <p class="text-sm mt-1">{{ kasKecilTerpilih?.nama_bank }}</p>
                    <p class="text-sm">Periode: {{ formatDate(filters.start_date) }} s.d. {{ formatDate(filters.end_date) }}</p>
                </div>

                <!-- SUMMARY CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Saldo Awal Periode</div>
                        <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(saldoAwal) }}</div>
                        <div class="text-xs text-gray-400 mt-1">Per {{ formatDate(filters.start_date) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border-l-4 border-red-500">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Pengeluaran</div>
                        <div class="text-2xl font-bold text-red-600">{{ formatCurrency(totalPengeluaran) }}</div>
                        <div class="text-xs text-gray-400 mt-1">{{ transaksi?.length ?? 0 }} transaksi dalam periode</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 border-l-4 border-green-500">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Saldo Akhir Periode</div>
                        <div class="text-2xl font-bold" :class="saldoAkhir < 0 ? 'text-red-600' : 'text-green-600'">
                            {{ formatCurrency(saldoAkhir) }}
                        </div>
                        <div class="text-xs mt-1" :class="saldoAkhir < 0 ? 'text-red-400' : 'text-gray-400'">
                            {{ saldoAkhir < 0 ? '⚠ Saldo negatif – perlu pengisian ulang' : 'Per ' + formatDate(filters.end_date) }}
                        </div>
                    </div>
                </div>

                <!-- ALERT SALDO RENDAH -->
                <div v-if="saldoAkhir >= 0 && saldoAkhir < (saldoAwal * 0.2) && saldoAwal > 0"
                     class="bg-amber-50 border border-amber-300 rounded-lg px-5 py-3 flex items-start gap-3 print:hidden">
                    <ArrowTrendingDownIcon class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Saldo Kas Kecil Hampir Habis</p>
                        <p class="text-xs text-amber-700">Saldo tersisa kurang dari 20% dari saldo awal. Pertimbangkan untuk melakukan pengisian ulang (replenishment).</p>
                    </div>
                </div>

                <!-- TAB NAVIGATION -->
                <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg w-fit print:hidden">
                    <button v-for="tab in [
                        { key: 'buku', label: 'Buku Kas Kecil', icon: DocumentTextIcon },
                        { key: 'departemen', label: 'Per Departemen', icon: BuildingOfficeIcon },
                        { key: 'akun', label: 'Per Akun GL', icon: ChartBarIcon },
                    ]" :key="tab.key"
                        @click="activeTab = tab.key"
                        class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition"
                        :class="activeTab === tab.key
                            ? 'bg-white dark:bg-gray-800 text-amber-600 shadow-sm'
                            : 'text-gray-500 hover:text-gray-700'"
                    >
                        <component :is="tab.icon" class="w-4 h-4" />
                        {{ tab.label }}
                    </button>
                </div>

                <!-- TAB: BUKU KAS KECIL -->
                <div v-if="activeTab === 'buku' || true" :class="activeTab === 'buku' ? '' : 'hidden print:block'">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between print:border-b-2 print:border-gray-800">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <DocumentTextIcon class="w-5 h-5 text-amber-500" />
                                    Buku Harian Kas Kecil
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">{{ kasKecilTerpilih?.nama_bank }} — {{ formatDate(filters.start_date) }} s.d. {{ formatDate(filters.end_date) }}</p>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-if="!transaksi || transaksi.length === 0" class="py-16 text-center">
                            <BanknotesIcon class="w-12 h-12 text-gray-200 mx-auto mb-3" />
                            <p class="text-gray-500 font-medium">Tidak ada transaksi dalam periode ini</p>
                            <p class="text-xs text-gray-400 mt-1">Ubah filter periode atau pilih Kas Kecil yang berbeda</p>
                        </div>

                        <!-- Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-8"></th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor / Keterangan</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pengaju</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Penerima Tunai</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Departemen</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Pengeluaran</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                                    <!-- Baris Saldo Awal -->
                                    <tr class="bg-blue-50 dark:bg-blue-900/20">
                                        <td colspan="6" class="px-4 py-3 text-xs font-semibold text-blue-700 dark:text-blue-300">Saldo Awal Periode</td>
                                        <td></td>
                                        <td class="px-4 py-3 text-right text-sm font-bold text-blue-700 dark:text-blue-300">{{ formatCurrency(saldoAwal) }}</td>
                                    </tr>

                                    <!-- Baris Transaksi -->
                                    <template v-for="t in transaksi" :key="t.id">
                                        <tr class="hover:bg-amber-50/40 dark:hover:bg-gray-700/30 cursor-pointer" @click="toggleRow(t.id)">
                                            <td class="px-4 py-3 text-gray-400">
                                                <component :is="expandedRows.has(t.id) ? ChevronDownIcon : ChevronRightIcon" class="w-4 h-4" />
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ formatDate(t.tgl_pengajuan) }}</td>
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ t.nomor_pengajuan }}</div>
                                                <div class="text-xs text-gray-500 truncate max-w-xs">{{ t.judul_pengajuan }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ t.nama_pengaju }}</td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ t.nama_penerima ?? '-' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                    {{ t.nama_departemen ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-medium text-red-600">{{ formatCurrency(t.total_nominal) }}</td>
                                            <td class="px-4 py-3 text-right font-semibold" :class="t.saldo_running < 0 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100'">
                                                {{ formatCurrency(t.saldo_running) }}
                                            </td>
                                        </tr>

                                        <!-- Detail Baris -->
                                        <tr v-if="expandedRows.has(t.id)" class="bg-amber-50/60 dark:bg-amber-900/10 print:hidden">
                                            <td></td>
                                            <td colspan="7" class="px-4 py-3">
                                                <div class="text-xs text-gray-600 font-semibold mb-2">Rincian Item:</div>
                                                <table class="w-full text-xs">
                                                    <thead>
                                                        <tr class="text-gray-500">
                                                            <th class="text-left pb-1 font-medium">Deskripsi</th>
                                                            <th class="text-left pb-1 font-medium">Akun GL</th>
                                                            <th class="text-left pb-1 font-medium">Program Kerja</th>
                                                            <th class="text-right pb-1 font-medium">Nominal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(d, i) in t.detail" :key="i">
                                                            <td class="py-0.5 text-gray-700">{{ d.deskripsi }}</td>
                                                            <td class="py-0.5">
                                                                <span class="font-mono text-indigo-600">{{ d.kode_akun }}</span>
                                                                <span class="text-gray-500 ml-1">{{ d.nama_akun }}</span>
                                                            </td>
                                                            <td class="py-0.5 text-gray-500">{{ d.nama_program ?? '-' }}</td>
                                                            <td class="py-0.5 text-right text-red-600 font-medium">{{ formatCurrency(d.nominal) }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Baris Saldo Akhir -->
                                    <tr class="bg-green-50 dark:bg-green-900/20 font-semibold">
                                        <td colspan="6" class="px-4 py-3 text-xs font-semibold text-green-700 dark:text-green-300">Saldo Akhir Periode</td>
                                        <td class="px-4 py-3 text-right text-red-600 font-bold">{{ formatCurrency(totalPengeluaran) }}</td>
                                        <td class="px-4 py-3 text-right font-bold" :class="saldoAkhir < 0 ? 'text-red-600' : 'text-green-700 dark:text-green-300'">{{ formatCurrency(saldoAkhir) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB: PER DEPARTEMEN -->
                <div v-if="activeTab === 'departemen'" class="print:block">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <BuildingOfficeIcon class="w-5 h-5 text-amber-500" />
                                Pengeluaran Per Departemen
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div v-if="!perDepartemen || perDepartemen.length === 0" class="text-center py-8 text-gray-400">
                                Tidak ada data
                            </div>
                            <div v-for="dept in perDepartemen" :key="dept.nama_departemen" class="flex items-center gap-4">
                                <div class="w-36 text-sm font-medium text-gray-700 dark:text-gray-300 truncate flex-shrink-0">{{ dept.nama_departemen }}</div>
                                <div class="flex-1">
                                    <div class="h-5 bg-amber-100 dark:bg-amber-900/30 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-500 rounded-full transition-all duration-500"
                                             :style="{ width: ((dept.total / maxDept) * 100) + '%' }"></div>
                                    </div>
                                </div>
                                <div class="w-32 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(dept.total) }}</div>
                                <div class="w-20 text-right text-xs text-gray-400">{{ dept.jumlah_transaksi }} txn</div>
                            </div>
                            <!-- Total -->
                            <div class="border-t pt-4 flex justify-between items-center">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">Total</span>
                                <span class="font-bold text-lg text-red-600">{{ formatCurrency(totalPengeluaran) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: PER AKUN GL -->
                <div v-if="activeTab === 'akun'" class="print:block">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <ChartBarIcon class="w-5 h-5 text-amber-500" />
                                Pengeluaran Per Akun GL (COA)
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div v-if="!perAkun || perAkun.length === 0" class="text-center py-8 text-gray-400">
                                Tidak ada data
                            </div>
                            <div v-for="akun in perAkun" :key="akun.kode_akun" class="flex items-center gap-4">
                                <div class="w-44 flex-shrink-0">
                                    <div class="text-xs font-mono text-indigo-600 dark:text-indigo-400">{{ akun.kode_akun }}</div>
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ akun.nama_akun }}</div>
                                </div>
                                <div class="flex-1">
                                    <div class="h-5 bg-indigo-100 dark:bg-indigo-900/30 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full transition-all duration-500"
                                             :style="{ width: ((akun.total / maxAkun) * 100) + '%' }"></div>
                                    </div>
                                </div>
                                <div class="w-32 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(akun.total) }}</div>
                                <div class="w-16 text-right text-xs text-gray-400">
                                    {{ totalPengeluaran > 0 ? ((akun.total / totalPengeluaran) * 100).toFixed(1) + '%' : '-' }}
                                </div>
                            </div>
                            <!-- Total -->
                            <div class="border-t pt-4 flex justify-between items-center">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">Total</span>
                                <span class="font-bold text-lg text-red-600">{{ formatCurrency(totalPengeluaran) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    .print\:hidden { display: none !important; }
    .print\:block { display: block !important; }
    body { font-size: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 4px 8px; }
    thead { background: #f3f4f6 !important; }
}
</style>
