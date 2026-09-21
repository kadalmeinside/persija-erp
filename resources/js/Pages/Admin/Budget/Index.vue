<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    PlusIcon, MagnifyingGlassIcon, PencilSquareIcon, TrashIcon, 
    ChevronDownIcon, ChevronRightIcon, 
    BanknotesIcon, CreditCardIcon, WalletIcon, ChartBarIcon, ArrowUpTrayIcon, ArrowPathIcon
} from '@heroicons/vue/24/outline';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import { Line, Doughnut } from 'vue-chartjs';
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    PointElement, 
    CategoryScale, 
    LinearScale, 
    ArcElement 
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    budgets: Array, // Controller returns get() (Array)
    periodes: Array,
    departemens: Array,
    filters: Object,
    activePeriodId: Number,
    chartData: Object, // { combined: {} }
    expenseSummary: Object, // { total: 0, used: 0, remaining: 0 }
    revenueSummary: Object // { target: 0, realized: 0, achievement: 0 }
});

const search = ref(props.filters.search || '');
const filterPeriode = ref(props.filters.id_periode || props.activePeriodId);
const filterDepartemen = ref(props.filters.id_departemen || '');
const activeTab = ref('expense'); // 'expense' or 'revenue'

// Chart Options
const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                usePointStyle: true,
                padding: 20
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null) {
                        label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                    }
                    return label;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                display: true,
                drawBorder: false,
                color: '#f3f4f6'
            },
            ticks: {
                callback: function(value) {
                    return new Intl.NumberFormat('id-ID', { notation: "compact", compactDisplay: "short" }).format(value);
                }
            }
        },
        x: {
            grid: {
                display: false
            }
        }
    }
};

const doughnutOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' },
        title: { 
            display: true, 
            text: props.chartData.allocation_title || 'Alokasi Anggaran' 
        }
    }
}));

// Get Active Period Info
const activePeriod = computed(() => {
    return props.periodes.find(p => p.id == filterPeriode.value);
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

// Debounce search & filters
watch([search, filterPeriode, filterDepartemen], debounce(([newSearch, newPeriode, newDept]) => {
    router.get(route('admin.budget.index'), { 
        search: newSearch, 
        id_periode: newPeriode,
        id_departemen: newDept
    }, { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true 
    });
}, 300));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const getPercentage = (used, total) => {
    const t = parseFloat(total) || 0;
    const u = parseFloat(used) || 0;
    if (t === 0) return 0;
    return Math.min(Math.round((u / t) * 100), 100);
};

const getProgressColor = (percent) => {
    if (percent >= 90) return 'bg-red-600';
    if (percent >= 75) return 'bg-yellow-500';
    return 'bg-green-500';
};

const deleteBudget = (id) => {
    Swal.fire({
        title: 'Hapus Anggaran?',
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.budget.destroy', id), {
                onSuccess: () => Swal.fire('Terhapus!', 'Anggaran berhasil dihapus.', 'success'),
                onError: () => Swal.fire('Gagal!', 'Anggaran tidak bisa dihapus karena sudah ada realisasi.', 'error')
            });
        }
    });
};

const fileInput = ref(null);
const isImporting = ref(false);

const triggerImport = () => {
    fileInput.value.click();
};

const handleImport = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    isImporting.value = true;
    console.log("Memulai proses import Excel...", file.name);
    
    router.post(route('admin.budget.import'), formData, {
        forceFormData: true,
        onSuccess: (page) => {
            console.log("✅ IMPORT BERHASIL!", page);
            Swal.fire('Berhasil!', 'Data anggaran berhasil di-import.', 'success');
            fileInput.value.value = '';
        },
        onError: (errors) => {
            console.error("❌ IMPORT GAGAL!", errors);
            Swal.fire('Gagal!', errors.error || 'Terjadi kesalahan saat import. Cek console log.', 'error');
            fileInput.value.value = '';
        },
        onFinish: () => {
            isImporting.value = false;
        }
    });
};

const resetPeriod = () => {
    if (!activePeriod.value) return;

    Swal.fire({
        title: `Reset Anggaran ${activePeriod.value.nama_periode}?`,
        text: "Ketik RESET untuk mengkonfirmasi. Semua data limit anggaran (yang belum terpakai) di periode ini akan terhapus!",
        input: 'text',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Reset!',
        cancelButtonText: 'Batal',
        preConfirm: (inputValue) => {
            if (inputValue !== 'RESET') {
                Swal.showValidationMessage('Anda harus mengetik RESET');
                return false;
            }
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.budget.reset-period'), {
                id_periode: activePeriod.value.id
            }, {
                onSuccess: () => Swal.fire('Tereset!', 'Anggaran periode berhasil dibersihkan.', 'success'),
                onError: (errors) => Swal.fire('Gagal!', errors.error || 'Terjadi kesalahan.', 'error')
            });
        }
    });
};

// Grouping Logic: Dept -> Program -> Accounts
const groupedBudgets = computed(() => {
    const groups = {
        expense: {},
        revenue: {}
    };
    
    // 1. Initialize all departments for both tabs
    if (props.departemens) {
        props.departemens.forEach(dept => {
            // Expense Group Init
            groups.expense[dept.id] = {
                id: dept.id,
                name: dept.nama_departemen,
                programs: {},
                total: 0,
                used: 0,
                hasBudget: false
            };
            // Revenue Group Init
            groups.revenue[dept.id] = {
                id: dept.id,
                name: dept.nama_departemen,
                programs: {},
                target: 0,
                realized: 0,
                hasBudget: false
            };
        });
    }
    
    // Handle if budgets is empty or undefined
    if (!props.budgets || !Array.isArray(props.budgets)) return groups;

    // 2. Populate with Budget Data
    props.budgets.forEach(item => {
        // Access Dept via Pos -> Program -> Dept (Centralized Budgeting)
        const dept = item.pos_anggaran.program_kerja.departemen;
        const deptId = dept ? dept.id : 'unknown';
        
        // If dept not in groups (e.g. deleted dept?), skip or add
        if (!groups.expense[deptId]) return; 

        // Access Program via Pos Anggaran
        const progId = item.pos_anggaran.program_kerja.id;
        const isRevenue = item.pos_anggaran.akun_gl.tipe_akun === 'Pendapatan';
        const targetGroup = isRevenue ? groups.revenue : groups.expense;

        targetGroup[deptId].hasBudget = true;

        if (!targetGroup[deptId].programs[progId]) {
            const isDelegated = item.pos_anggaran.program_kerja.id_departemen != deptId;
            const deptName = item.pos_anggaran.program_kerja.departemen ? item.pos_anggaran.program_kerja.departemen.nama_departemen : 'Unknown Dept';
            const programName = isDelegated 
                ? `[${deptName}] ${item.pos_anggaran.program_kerja.nama_program}`
                : item.pos_anggaran.program_kerja.nama_program;

            targetGroup[deptId].programs[progId] = {
                id: progId,
                name: programName,
                accounts: [],
                total: 0, // For expense: Limit, For revenue: Target
                used: 0   // For expense: Used, For revenue: Realized
            };
        }

        targetGroup[deptId].programs[progId].accounts.push(item);
        
        // Accumulate Totals
        const itemUsed = parseFloat(item.anggaran_terikat_ytd) + parseFloat(item.anggaran_realisasi_ytd);
        const itemTotal = parseFloat(item.anggaran_total_tahun);
        
        targetGroup[deptId].programs[progId].total += itemTotal;
        targetGroup[deptId].programs[progId].used += itemUsed;
        
        if (isRevenue) {
            targetGroup[deptId].target += itemTotal;
            targetGroup[deptId].realized += itemUsed;
        } else {
            targetGroup[deptId].total += itemTotal;
            targetGroup[deptId].used += itemUsed;
        }
    });

    return groups;
});

const currentGroups = computed(() => {
    return activeTab.value === 'expense' ? groupedBudgets.value.expense : groupedBudgets.value.revenue;
});

const expandedDepts = ref({});
const toggleDept = (id) => {
    expandedDepts.value[id] = !expandedDepts.value[id];
};

// Auto expand if filtering by department
watch(filterDepartemen, (newVal) => {
    if (newVal) {
        expandedDepts.value[newVal] = true;
    }
}, { immediate: true });

</script>

<template>
    <Head title="Manajemen Anggaran" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen Anggaran</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <!-- Header Content (Periode Info & Summary) -->
                        <div v-if="activePeriod" class="mb-6 bg-indigo-50 border border-indigo-100 rounded-lg p-4 flex flex-col lg:flex-row justify-between items-center gap-4">
                             <!-- Left: Periode Info -->
                             <div class="flex items-center gap-4 w-full lg:w-auto">
                                <div class="p-3 bg-indigo-100 rounded-full text-indigo-600">
                                    <BanknotesIcon class="w-8 h-8" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-indigo-900">{{ activePeriod.nama_periode }}</h3>
                                    <p class="text-sm text-indigo-700 font-medium">{{ formatDate(activePeriod.tanggal_mulai) }} - {{ formatDate(activePeriod.tanggal_selesai) }}</p>
                                    <span v-if="activePeriod.is_active" class="mt-1 inline-block px-2 py-0.5 text-[10px] font-bold bg-green-100 text-green-700 rounded-full border border-green-200">PERIODE AKTIF</span>
                                </div>
                             </div>

                             <!-- Right: Summary Stats -->
                             <div class="flex flex-col gap-4 w-full lg:w-auto">
                                <!-- Expense Summary (Orange Accent) -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-orange-400 min-w-[150px]">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Total Anggaran (Biaya)</p>
                                        <p class="text-lg font-bold text-gray-800">{{ formatCurrency(expenseSummary.total) }}</p>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-orange-400 min-w-[150px]">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <CreditCardIcon class="w-3 h-3" /> Terpakai
                                        </p>
                                        <p class="text-lg font-bold text-orange-600">{{ formatCurrency(expenseSummary.used) }}</p>
                                        <p class="text-[10px] text-gray-400 mt-1">
                                            {{ getPercentage(expenseSummary.used, expenseSummary.total) }}% dari total
                                        </p>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-orange-400 min-w-[150px]">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <WalletIcon class="w-3 h-3" /> Sisa
                                        </p>
                                        <p class="text-lg font-bold text-gray-800">{{ formatCurrency(expenseSummary.remaining) }}</p>
                                    </div>
                                </div>

                                <!-- Revenue Summary (Green Accent) -->
                                <div v-if="revenueSummary.target > 0" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-green-500 min-w-[150px]">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">Target Pendapatan</p>
                                        <p class="text-lg font-bold text-gray-800">{{ formatCurrency(revenueSummary.target) }}</p>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-green-500 min-w-[150px]">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <BanknotesIcon class="w-3 h-3" /> Realisasi
                                        </p>
                                        <p class="text-lg font-bold text-green-600">{{ formatCurrency(revenueSummary.realized) }}</p>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-green-500 min-w-[150px]">
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <ChartBarIcon class="w-3 h-3" /> Pencapaian
                                        </p>
                                        <p class="text-lg font-bold" :class="revenueSummary.achievement >= 100 ? 'text-green-600' : 'text-yellow-600'">
                                            {{ Math.round(revenueSummary.achievement) }}%
                                        </p>
                                    </div>
                                </div>
                             </div>
                        </div>

                        <!-- Charts -->
                        <div v-if="chartData && chartData.combined" class="mb-8 grid grid-cols-1 lg:grid-cols-1 gap-6">
                            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold text-gray-800">
                                        Overview Keuangan (Pendapatan vs Biaya)
                                    </h3>
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                        Klik legend untuk filter
                                    </span>
                                </div>
                                <div class="h-80">
                                    <Line :data="chartData.combined" :options="lineOptions" />
                                </div>
                            </div>
                        </div>

                        <!-- Toolbar -->
                        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
                            <div class="flex flex-col sm:flex-row flex-wrap items-center gap-3 w-full lg:w-auto">
                                <!-- Search -->
                                <div class="relative w-full sm:w-64">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400" />
                                    </div>
                                    <input 
                                        v-model="search" 
                                        type="text" 
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-9 p-2.5 shadow-sm transition-colors" 
                                        placeholder="Cari Akun..." 
                                    />
                                </div>
                                <!-- Filter Periode -->
                                <select v-model="filterPeriode" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:w-auto p-2.5 pr-10 shadow-sm cursor-pointer transition-colors">
                                    <option v-for="p in periodes" :key="p.id" :value="p.id">
                                        {{ p.nama_periode }} {{ p.is_active ? '(Aktif)' : '' }}
                                    </option>
                                </select>
                                <!-- Filter Departemen -->
                                <select v-model="filterDepartemen" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:w-auto p-2.5 pr-10 shadow-sm cursor-pointer transition-colors">
                                    <option value="">Semua Departemen</option>
                                    <option v-for="d in departemens" :key="d.id" :value="d.id">{{ d.nama_departemen }}</option>
                                </select>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-start lg:justify-end">
                                <template v-if="$page.props.auth.user.roles && $page.props.auth.user.roles.includes('Super Admin')">
                                    <button 
                                        @click="triggerImport"
                                        :disabled="isImporting"
                                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25">
                                        <ArrowUpTrayIcon class="w-4 h-4 mr-2" />
                                        {{ isImporting ? 'Mengimpor...' : 'Import Excel' }}
                                    </button>
                                    <input type="file" ref="fileInput" class="hidden" accept=".xlsx,.xls,.csv" @change="handleImport">
                                    
                                    <button 
                                        v-if="activePeriod && expenseSummary.total > 0"
                                        @click="resetPeriod"
                                        class="inline-flex items-center rounded-lg border border-transparent bg-red-600 px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700">
                                        <ArrowPathIcon class="w-4 h-4 mr-2" />
                                        Reset Anggaran
                                    </button>
                                </template>

                                <Link 
                                    :href="route('admin.budget.create')" 
                                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <PlusIcon class="w-4 h-4 mr-2" />
                                    Alokasi Manual
                                </Link>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div class="border-b border-gray-200 mb-6">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button 
                                    @click="activeTab = 'expense'"
                                    :class="[
                                        activeTab === 'expense'
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                                    ]"
                                >
                                    <CreditCardIcon class="w-5 h-5" />
                                    Anggaran Biaya (Expense)
                                </button>
                                <button 
                                    @click="activeTab = 'revenue'"
                                    :class="[
                                        activeTab === 'revenue'
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                                    ]"
                                >
                                    <BanknotesIcon class="w-5 h-5" />
                                    Target Pendapatan (Revenue)
                                </button>
                            </nav>
                        </div>

                        <!-- Hierarchical List -->
                        <div class="border rounded-lg overflow-hidden">
                            <div v-if="Object.keys(currentGroups).length === 0" class="p-8 text-center text-gray-500">
                                Belum ada data anggaran untuk filter ini.
                            </div>

                            <div v-for="dept in currentGroups" :key="dept.id" class="border-b last:border-b-0">
                                <!-- Department Header -->
                                <div 
                                    @click="toggleDept(dept.id)"
                                    class="bg-gray-100 px-4 py-3 flex justify-between items-center cursor-pointer hover:bg-gray-200 transition"
                                >
                                    <div class="flex items-center font-bold text-gray-800">
                                        <component :is="expandedDepts[dept.id] ? ChevronDownIcon : ChevronRightIcon" class="w-5 h-5 mr-2 text-gray-500" />
                                        {{ dept.name }}
                                    </div>
                                    
                                    <!-- Expense Summary -->
                                    <div v-if="activeTab === 'expense'" class="text-xs sm:text-sm text-gray-700 flex gap-4 items-center">
                                        <div class="flex items-center gap-1" title="Total Anggaran">
                                            <BanknotesIcon class="w-4 h-4 text-gray-500" />
                                            <span>{{ formatCurrency(dept.total) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1" title="Terpakai">
                                            <CreditCardIcon class="w-4 h-4 text-gray-500" />
                                            <span>{{ formatCurrency(dept.used) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1" title="Sisa Anggaran">
                                            <WalletIcon class="w-4 h-4 text-gray-500" />
                                            <span class="font-semibold text-green-600">{{ formatCurrency(dept.total - dept.used) }}</span>
                                        </div>
                                    </div>

                                    <!-- Revenue Summary -->
                                    <div v-if="activeTab === 'revenue'" class="text-xs sm:text-sm text-gray-700 flex gap-4 items-center">
                                        <div class="flex items-center gap-1" title="Total Target">
                                            <BanknotesIcon class="w-4 h-4 text-indigo-500" />
                                            <span>{{ formatCurrency(dept.target) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1" title="Realisasi">
                                            <CreditCardIcon class="w-4 h-4 text-indigo-500" />
                                            <span>{{ formatCurrency(dept.realized) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1" title="Pencapaian">
                                            <ChartBarIcon class="w-4 h-4 text-indigo-500" />
                                            <span class="font-semibold" :class="dept.realized >= dept.target ? 'text-green-600' : 'text-yellow-600'">
                                                {{ getPercentage(dept.realized, dept.target) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Programs (Collapsible) -->
                                <div v-if="expandedDepts[dept.id]" class="bg-white">
                                    <div v-for="prog in dept.programs" :key="prog.id" class="border-t border-gray-100">
                                        <!-- Program Header -->
                                        <div class="px-4 py-2 bg-gray-50 flex justify-between items-center pl-10">
                                            <div class="font-semibold text-gray-700 text-sm">
                                                {{ prog.name }}
                                            </div>
                                            <!-- Program Stats (Dynamic based on Tab) -->
                                            <div class="text-xs text-gray-600 flex gap-3 items-center">
                                                <div class="flex items-center gap-1">
                                                    <BanknotesIcon class="w-3 h-3 text-gray-400" />
                                                    <span>{{ formatCurrency(prog.total) }}</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <CreditCardIcon class="w-3 h-3 text-gray-400" />
                                                    <span>{{ formatCurrency(prog.used) }}</span>
                                                </div>
                                                <div v-if="activeTab === 'expense'" class="flex items-center gap-1">
                                                    <WalletIcon class="w-3 h-3 text-gray-400" />
                                                    <span class="text-green-600">{{ formatCurrency(prog.total - prog.used) }}</span>
                                                </div>
                                                <div v-if="activeTab === 'revenue'" class="flex items-center gap-1">
                                                    <ChartBarIcon class="w-3 h-3 text-gray-400" />
                                                    <span :class="prog.used >= prog.total ? 'text-green-600' : 'text-yellow-600'">
                                                        {{ getPercentage(prog.used, prog.total) }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Accounts Table -->
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-4 py-2 pl-16 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akun</th>
                                                    <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ activeTab === 'expense' ? 'Pagu' : 'Target' }}</th>
                                                    <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ activeTab === 'expense' ? 'Terpakai' : 'Realisasi' }}</th>
                                                    <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ activeTab === 'expense' ? 'Sisa' : 'Capaian' }}</th>
                                                    <th scope="col" class="px-4 py-2 w-1/6"></th>
                                                    <th scope="col" class="px-4 py-2 w-20"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <tr v-for="item in prog.accounts.filter(a => (activeTab === 'expense' && a.pos_anggaran.akun_gl.tipe_akun !== 'Pendapatan') || (activeTab === 'revenue' && a.pos_anggaran.akun_gl.tipe_akun === 'Pendapatan'))" :key="item.id" class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 pl-16 whitespace-nowrap text-sm text-gray-900 w-1/3">
                                                        <div class="font-mono text-xs text-gray-500">{{ item.pos_anggaran.akun_gl.kode_akun }}</div>
                                                        <div>{{ item.pos_anggaran.akun_gl.nama_akun }}</div>
                                                    </td>
                                                    <td class="px-4 py-2 text-right text-sm font-medium text-gray-900 w-1/6">
                                                        {{ formatCurrency(item.anggaran_total_tahun) }}
                                                    </td>
                                                    <td class="px-4 py-2 text-right text-sm text-gray-600 w-1/6">
                                                        {{ formatCurrency(parseFloat(item.anggaran_terikat_ytd) + parseFloat(item.anggaran_realisasi_ytd)) }}
                                                    </td>
                                                    <!-- Dynamic Column: Sisa (Expense) or % (Revenue) -->
                                                    <td class="px-4 py-2 text-right text-sm font-bold w-1/6" :class="activeTab === 'expense' ? 'text-green-600' : ((parseFloat(item.anggaran_terikat_ytd) + parseFloat(item.anggaran_realisasi_ytd)) >= item.anggaran_total_tahun ? 'text-green-600' : 'text-yellow-600')">
                                                        <span v-if="activeTab === 'expense'">
                                                            {{ formatCurrency(item.anggaran_total_tahun - (parseFloat(item.anggaran_terikat_ytd) + parseFloat(item.anggaran_realisasi_ytd))) }}
                                                        </span>
                                                        <span v-else>
                                                            {{ getPercentage(parseFloat(item.anggaran_terikat_ytd) + parseFloat(item.anggaran_realisasi_ytd), item.anggaran_total_tahun) }}%
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2 align-middle w-1/6">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                                                <div 
                                                                    class="h-1.5 rounded-full" 
                                                                    :class="activeTab === 'expense' ? getProgressColor(getPercentage(parseFloat(item.anggaran_terikat_ytd) + parseFloat(item.anggaran_realisasi_ytd), item.anggaran_total_tahun)) : 'bg-indigo-600'"
                                                                    :style="{ width: Math.min(getPercentage(parseFloat(item.anggaran_terikat_ytd) + parseFloat(item.anggaran_realisasi_ytd), item.anggaran_total_tahun), 100) + '%' }"
                                                                ></div>
                                                            </div>
                                                            <span class="text-[10px] font-medium text-gray-500 w-8 text-right">
                                                                {{ getPercentage(parseFloat(item.anggaran_terikat_ytd) + parseFloat(item.anggaran_realisasi_ytd), item.anggaran_total_tahun) }}%
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-2 text-right text-sm font-medium w-20">
                                                        <div class="flex justify-end space-x-2">
                                                            <Link :href="route('admin.budget.edit', item.id)" class="text-blue-600 hover:text-blue-900">
                                                                <PencilSquareIcon class="w-4 h-4" />
                                                            </Link>
                                                            <button @click="deleteBudget(item.id)" class="text-red-600 hover:text-red-900">
                                                                <TrashIcon class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
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
