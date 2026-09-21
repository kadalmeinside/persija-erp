<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    UserCircleIcon, BriefcaseIcon, MapPinIcon, CalendarIcon, 
    BanknotesIcon, PencilSquareIcon, ArrowLeftIcon, ChevronRightIcon,
    IdentificationIcon, CheckBadgeIcon, AtSymbolIcon, BuildingOfficeIcon
} from '@heroicons/vue/24/outline';
import EmployeeFormModal from '@/Components/EmployeeFormModal.vue';

const props = defineProps({
    karyawan: Object,
    leaveBalances: Array,
    departemens: Array // Needed for Edit Form
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};

// Tabs
const activeTab = ref('profile'); // profile, employment, salary, leave

// Edit Modal
const showEditModal = ref(false);
const openEditModal = () => {
    showEditModal.value = true;
};
const closeEditModal = () => {
    showEditModal.value = false;
};
const refreshData = () => {
    router.reload();
};
</script>

<template>
    <Head :title="karyawan.nama_lengkap" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-3">
                <div class="p-1 bg-white dark:bg-gray-800 rounded-full shadow-sm">
                    <img :src="karyawan.foto_url" :alt="karyawan.nama_lengkap" class="w-8 h-8 rounded-full object-cover" />
                </div>
                <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight">Detail Karyawan</h2>
            </div>
        </template>

        <div class="pb-12 pt-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Breadcrumb & Back Button -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4 bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/50">
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-2">
                            <li class="inline-flex items-center">
                                <Link :href="route('dashboard')" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">
                                    Dashboard
                                </Link>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <ChevronRightIcon class="w-4 h-4 text-gray-400" />
                                    <Link :href="route('admin.karyawan.index')" class="ml-1 text-sm font-medium text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors md:ml-2">
                                        Karyawan
                                    </Link>
                                </div>
                            </li>
                            <li aria-current="page">
                                <div class="flex items-center">
                                    <ChevronRightIcon class="w-4 h-4 text-gray-400" />
                                    <span class="ml-1 text-sm font-semibold text-gray-900 md:ml-2 dark:text-white">{{ karyawan.nama_lengkap }}</span>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <Link :href="route('admin.karyawan.index')" class="inline-flex items-center px-4 py-2 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm transition-all shadow-sm">
                        <ArrowLeftIcon class="w-4 h-4 mr-2" /> Kembali
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Profile Card (Left Sidebar) -->
                    <div class="lg:col-span-4">
                        <div class="bg-white dark:bg-gray-800 shadow-xl shadow-indigo-100/20 dark:shadow-none rounded-3xl overflow-hidden sticky top-8 border border-gray-100 dark:border-gray-700/50 group">
                            <!-- Header Gradient -->
                            <div class="h-36 bg-gradient-to-br from-indigo-500 via-purple-500 to-indigo-700 relative overflow-hidden">
                                <!-- Abstract decorative shapes -->
                                <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white/10 blur-2xl"></div>
                                <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 rounded-full bg-black/10 blur-xl"></div>
                            </div>
                            
                            <!-- Profile Image & Info -->
                            <div class="px-8 pb-8 text-center relative">
                                <div class="-mt-20 mb-5 flex justify-center">
                                    <div class="p-2 bg-white dark:bg-gray-800 rounded-full shadow-lg relative group-hover:-translate-y-2 transition-transform duration-500">
                                        <div class="w-32 h-32 rounded-full bg-gray-100 dark:bg-gray-700 border-4 border-indigo-50 dark:border-gray-700 flex items-center justify-center overflow-hidden">
                                            <img :src="karyawan.foto_url" :alt="karyawan.nama_lengkap" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="absolute bottom-2 right-2 w-6 h-6 bg-green-500 border-4 border-white dark:border-gray-800 rounded-full" title="Active"></div>
                                    </div>
                                </div>
                                
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ karyawan.nama_lengkap }}</h3>
                                <p class="text-indigo-600 dark:text-indigo-400 font-medium mt-1 bg-indigo-50 dark:bg-indigo-900/30 inline-block px-3 py-1 rounded-full text-sm">{{ karyawan.jabatan }}</p>
                                
                                <div class="mt-6 space-y-4 text-left border-t border-gray-100 dark:border-gray-700/50 pt-6">
                                    <div class="flex items-start text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 transition-colors">
                                        <IdentificationIcon class="w-5 h-5 text-gray-400 mr-3 shrink-0" />
                                        <div>
                                            <p class="text-xs text-gray-400 uppercase tracking-wider">NIK</p>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ karyawan.nomor_induk_karyawan }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 transition-colors">
                                        <BuildingOfficeIcon class="w-5 h-5 text-gray-400 mr-3 shrink-0" />
                                        <div>
                                            <p class="text-xs text-gray-400 uppercase tracking-wider">Departemen</p>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ karyawan.departemen?.nama_departemen }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start text-sm text-gray-600 dark:text-gray-300 hover:text-indigo-600 transition-colors">
                                        <MapPinIcon class="w-5 h-5 text-gray-400 mr-3 shrink-0" />
                                        <div>
                                            <p class="text-xs text-gray-400 uppercase tracking-wider">Lokasi</p>
                                            <p class="font-medium text-gray-900 dark:text-white line-clamp-2">{{ karyawan.alamat || '-' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <button @click="openEditModal" class="w-full inline-flex justify-center items-center px-4 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 rounded-xl font-bold text-sm text-white shadow-lg shadow-indigo-200 dark:shadow-none hover:-translate-y-0.5 transition-all duration-200">
                                        <PencilSquareIcon class="w-5 h-5 mr-2" /> Edit Profil Karyawan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content (Tabs) -->
                    <div class="lg:col-span-8">
                        <div class="bg-white dark:bg-gray-800 shadow-xl shadow-gray-100/50 dark:shadow-none rounded-3xl border border-gray-100 dark:border-gray-700/50 min-h-[600px] flex flex-col">
                            
                            <!-- Tab Headers -->
                            <div class="p-4 border-b border-gray-100 dark:border-gray-700/50">
                                <nav class="flex space-x-2 bg-gray-50 dark:bg-gray-900/50 p-1.5 rounded-2xl" aria-label="Tabs">
                                    <button 
                                        v-for="tab in ['profile', 'employment', 'salary', 'leave']" 
                                        :key="tab"
                                        @click="activeTab = tab"
                                        :class="[
                                            activeTab === tab 
                                                ? 'bg-white dark:bg-gray-800 text-indigo-700 dark:text-indigo-400 shadow-sm' 
                                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-200/50 dark:hover:bg-gray-700/50',
                                            'w-1/4 py-2.5 px-4 text-center rounded-xl font-semibold text-sm capitalize transition-all duration-300 relative'
                                        ]"
                                    >
                                        {{ tab === 'profile' ? 'Biodata' : tab === 'employment' ? 'Karir' : tab === 'salary' ? 'Finansial' : 'Cuti' }}
                                    </button>
                                </nav>
                            </div>

                            <!-- Tab Contents -->
                            <div class="p-8 flex-1">
                                <!-- Profile Tab -->
                                <Transition name="fade" mode="out-in">
                                    <div v-if="activeTab === 'profile'" class="space-y-6">
                                        <div class="flex items-center mb-6">
                                            <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center mr-4 p-0.5 shadow-sm border border-indigo-50 dark:border-gray-700 overflow-hidden">
                                                <img :src="karyawan.foto_url" class="w-full h-full rounded-full object-cover" />
                                            </div>
                                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Informasi Personal</h4>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="bg-gray-50 dark:bg-gray-900/30 p-5 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-indigo-100 transition-colors">
                                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Nama Lengkap</label>
                                                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ karyawan.nama_lengkap }}</p>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-900/30 p-5 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-indigo-100 transition-colors">
                                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Jenis Kelamin</label>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-lg font-medium text-gray-900 dark:text-white">
                                                        {{ karyawan.jenis_kelamin === 'L' ? 'Laki-laki' : (karyawan.jenis_kelamin === 'P' ? 'Perempuan' : '-') }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-900/30 p-5 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-indigo-100 transition-colors sm:col-span-2">
                                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Tempat, Tanggal Lahir</label>
                                                <p class="text-lg font-medium text-gray-900 dark:text-white">
                                                    {{ karyawan.tempat_lahir || '-' }}, {{ formatDate(karyawan.tgl_lahir) }}
                                                </p>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-900/30 p-5 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-indigo-100 transition-colors sm:col-span-2">
                                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Alamat Domisili</label>
                                                <p class="text-base text-gray-900 dark:text-white leading-relaxed">{{ karyawan.alamat || 'Belum ada data alamat' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Employment Tab -->
                                    <div v-else-if="activeTab === 'employment'" class="space-y-6">
                                        <div class="flex items-center mb-6">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mr-4">
                                                <BriefcaseIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Detail Kepegawaian</h4>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="bg-gray-50 dark:bg-gray-900/30 p-5 rounded-2xl border border-gray-100 dark:border-gray-800">
                                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Status Karyawan</label>
                                                <span class="px-4 py-1.5 inline-flex text-sm font-bold rounded-full bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 ring-1 ring-green-600/20 shadow-sm">
                                                    {{ karyawan.status_karyawan }}
                                                </span>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-900/30 p-5 rounded-2xl border border-gray-100 dark:border-gray-800">
                                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Tanggal Bergabung</label>
                                                <p class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                                    <CalendarIcon class="w-5 h-5 text-gray-400 mr-2" />
                                                    {{ formatDate(karyawan.tgl_bergabung) }}
                                                </p>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-900/30 p-5 rounded-2xl border border-gray-100 dark:border-gray-800">
                                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Departemen</label>
                                                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ karyawan.departemen?.nama_departemen }}</p>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-900/30 p-5 rounded-2xl border border-gray-100 dark:border-gray-800">
                                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Jabatan</label>
                                                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ karyawan.jabatan }}</p>
                                            </div>
                                            
                                            <div class="sm:col-span-2 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/50 dark:to-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between mt-2">
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1">Akun Sistem (Email)</label>
                                                    <div class="flex items-center mt-1">
                                                        <AtSymbolIcon class="w-5 h-5 text-indigo-500 mr-2" />
                                                        <p class="text-lg font-medium text-gray-900 dark:text-white">{{ karyawan.user?.email || 'Belum ditautkan' }}</p>
                                                    </div>
                                                </div>
                                                <div v-if="karyawan.user" class="hidden sm:block">
                                                    <CheckBadgeIcon class="w-10 h-10 text-green-500 opacity-20" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Salary Tab -->
                                    <div v-else-if="activeTab === 'salary'" class="space-y-6">
                                        <div class="flex justify-between items-center mb-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mr-4">
                                                    <BanknotesIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                                                </div>
                                                <h4 class="text-xl font-bold text-gray-900 dark:text-white">Finansial & Kompensasi</h4>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gradient-to-r from-amber-50 to-amber-100/50 dark:from-amber-900/20 dark:to-amber-900/10 border-l-4 border-amber-400 p-4 rounded-r-xl rounded-l-sm flex items-start shadow-sm">
                                            <BanknotesIcon class="h-5 w-5 text-amber-500 mt-0.5 shrink-0" aria-hidden="true" />
                                            <p class="text-sm text-amber-800 dark:text-amber-200 ml-3 leading-relaxed">
                                                <strong class="font-semibold">Informasi Rahasia!</strong> Data pada tab ini bersifat konfidensial. Pastikan privasi layar Anda terjaga saat melihat informasi gaji.
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
                                            <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-emerald-100 dark:border-emerald-900/50 shadow-lg shadow-emerald-100/20 dark:shadow-none flex flex-col justify-center">
                                                <label class="block text-xs font-bold text-emerald-600 uppercase tracking-widest mb-2">Gaji Pokok</label>
                                                <p class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ formatCurrency(karyawan.gaji_pokok || 0) }}</p>
                                            </div>
                                            <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col justify-center">
                                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Status PTKP</label>
                                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ karyawan.status_ptkp || '-' }}</p>
                                                <p class="text-xs text-gray-400 mt-1">Penghasilan Tidak Kena Pajak</p>
                                            </div>
                                            
                                            <!-- Bank Card Style -->
                                            <div class="sm:col-span-2 relative overflow-hidden rounded-3xl mt-4 border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none">
                                                <div class="absolute inset-0 bg-gradient-to-br from-slate-800 to-gray-900 z-0"></div>
                                                <!-- Decorative circles -->
                                                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/5 rounded-full blur-xl z-0"></div>
                                                <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-white/5 rounded-full blur-xl z-0"></div>
                                                
                                                <div class="relative z-10 p-8 text-white">
                                                    <div class="flex justify-between items-start mb-8">
                                                        <div>
                                                            <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold mb-1">Rekening Pencairan</p>
                                                            <div class="flex items-center">
                                                                <div class="w-8 h-5 bg-yellow-400/80 rounded mr-3 opacity-80"></div>
                                                                <h5 class="text-xl font-bold tracking-wider">{{ karyawan.rekening_bank && karyawan.rekening_bank.length > 0 ? karyawan.rekening_bank[0].nama_bank : 'TIDAK ADA DATA' }}</h5>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div v-if="karyawan.rekening_bank && karyawan.rekening_bank.length > 0">
                                                        <p class="font-mono text-3xl tracking-widest mb-6 opacity-90">{{ karyawan.rekening_bank[0].nomor_rekening.replace(/(.{4})/g, '$1 ').trim() }}</p>
                                                        <div class="flex justify-between items-end">
                                                            <div>
                                                                <p class="text-[10px] text-gray-400 uppercase tracking-widest mb-1">Card Holder</p>
                                                                <p class="font-bold tracking-wider uppercase text-lg">{{ karyawan.rekening_bank[0].atas_nama_rekening }}</p>
                                                            </div>
                                                            <div class="w-12 h-8 flex relative">
                                                                <div class="w-8 h-8 rounded-full bg-red-500/80 absolute right-4 mix-blend-screen"></div>
                                                                <div class="w-8 h-8 rounded-full bg-yellow-500/80 absolute right-0 mix-blend-screen"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-else class="py-4 text-center">
                                                        <p class="text-gray-400 italic">Silakan tambahkan data rekening melalui Edit Profil</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Leave Tab -->
                                    <div v-else-if="activeTab === 'leave'" class="space-y-6">
                                        <div class="flex justify-between items-center mb-6">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center mr-4">
                                                    <CalendarIcon class="w-6 h-6 text-rose-600 dark:text-rose-400" />
                                                </div>
                                                <h4 class="text-xl font-bold text-gray-900 dark:text-white">Saldo Cuti</h4>
                                            </div>
                                            <span class="text-xs font-bold text-indigo-700 bg-indigo-100 dark:bg-indigo-900/60 dark:text-indigo-300 px-3 py-1.5 rounded-full ring-1 ring-indigo-500/20 shadow-sm uppercase tracking-wider">Periode {{ new Date().getFullYear() }}</span>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                                            <div v-for="balance in leaveBalances" :key="balance.id" class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/80 p-6 rounded-3xl shadow-sm hover:shadow-xl hover:shadow-indigo-100/40 dark:hover:shadow-none hover:-translate-y-1 transition-all duration-300 group">
                                                <div class="flex justify-between items-start mb-4">
                                                    <h5 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider line-clamp-1 pr-2">{{ balance.jenis_cuti.nama_cuti }}</h5>
                                                    <div class="w-8 h-8 rounded-full bg-gray-50 dark:bg-gray-700 flex items-center justify-center shrink-0 group-hover:bg-indigo-50 dark:group-hover:bg-indigo-900/50 transition-colors">
                                                        <CalendarIcon class="w-4 h-4 text-gray-400 group-hover:text-indigo-500" />
                                                    </div>
                                                </div>
                                                
                                                <!-- Normal Case (Limited) -->
                                                <template v-if="!balance.jenis_cuti.is_unlimited">
                                                    <div class="flex items-baseline mb-6">
                                                        <span class="text-5xl font-black text-indigo-600 dark:text-indigo-400 tracking-tighter">{{ balance.saldo_akhir }}</span>
                                                        <span class="ml-2 text-sm font-medium text-gray-500">hari tersisa</span>
                                                    </div>
                                                    <div class="w-full bg-gray-100 rounded-full h-3 mb-3 dark:bg-gray-700 overflow-hidden relative">
                                                        <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-3 rounded-full relative overflow-hidden transition-all duration-1000" :style="{ width: Math.min((balance.saldo_terpakai / balance.saldo_awal) * 100, 100) + '%' }">
                                                            <!-- Shine effect -->
                                                            <div class="absolute inset-0 bg-white/20 w-1/2 -skew-x-12 -translate-x-full group-hover:animate-[shimmer_1.5s_infinite]"></div>
                                                        </div>
                                                    </div>
                                                    <div class="flex justify-between items-center text-xs font-medium">
                                                        <span class="text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded-md">Terpakai: {{ balance.saldo_terpakai }}</span>
                                                        <span class="text-gray-500 dark:text-gray-400">Total: {{ balance.saldo_awal }}</span>
                                                    </div>
                                                </template>
                                                
                                                <!-- Unlimited Case -->
                                                <template v-else>
                                                    <div class="flex items-baseline mb-6">
                                                        <span class="text-5xl font-black text-rose-500 dark:text-rose-400 tracking-tighter">{{ balance.saldo_terpakai }}</span>
                                                        <span class="ml-2 text-sm font-medium text-gray-500">hari terpakai</span>
                                                    </div>
                                                    <div class="w-full bg-rose-50 rounded-full h-3 mb-3 dark:bg-gray-700 overflow-hidden">
                                                        <div class="bg-gradient-to-r from-rose-400 to-rose-500 h-3 rounded-full" style="width: 100%"></div>
                                                    </div>
                                                    <div class="flex justify-between items-center text-xs font-medium">
                                                        <span class="text-rose-600 bg-rose-50 dark:bg-rose-900/30 dark:text-rose-300 px-2 py-1 rounded-md shadow-sm">Batas: Tidak Terbatas</span>
                                                    </div>
                                                </template>
                                            </div>
                                            
                                            <div v-if="leaveBalances.length === 0" class="col-span-full flex flex-col items-center justify-center py-16 bg-gray-50 dark:bg-gray-800/50 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-3xl text-gray-500">
                                                <div class="w-16 h-16 bg-white dark:bg-gray-800 rounded-full shadow-sm flex items-center justify-center mb-4">
                                                    <CalendarIcon class="w-8 h-8 text-gray-400" />
                                                </div>
                                                <p class="font-medium text-lg">Belum Ada Saldo Cuti</p>
                                                <p class="text-sm text-gray-400 mt-1">Karyawan ini belum memiliki kuota cuti untuk tahun ini.</p>
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <EmployeeFormModal 
            :show="showEditModal" 
            :employee="karyawan" 
            :departemens="departemens"
            @close="closeEditModal"
            @saved="refreshData"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
/* View Transitions & Animations */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@keyframes shimmer {
  100% {
    transform: translateX(200%);
  }
}
</style>
