<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import { Head } from '@inertiajs/vue3';

import PinModal from '@/Components/PinModal.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    karyawan: {
        type: Object,
        default: null
    }
});

const primaryBank = computed(() => {
    if (!props.karyawan || !props.karyawan.rekening_bank || props.karyawan.rekening_bank.length === 0) return null;
    return props.karyawan.rekening_bank.find(b => b.is_primary) || props.karyawan.rekening_bank[0];
});

const showPinModal = ref(false);
const pinMode = ref('create'); // create or change
const hasPin = ref(false);

const checkPinStatus = async () => {
    try {
        const res = await axios.get(route('admin.pin.status'));
        hasPin.value = res.data.has_pin;
        pinMode.value = hasPin.value ? 'change' : 'create';
    } catch (e) {
        console.error(e);
    }
};

const openPinModal = () => {
    showPinModal.value = true;
};

const onPinSuccess = () => {
    checkPinStatus();
};

onMounted(() => {
    checkPinStatus();
});
</script>

<template>
    <Head title="Profil Akun" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="flex items-center gap-2 text-2xl font-bold leading-tight text-slate-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                Profil Saya
            </h2>
        </template>

        <div class="min-h-screen bg-slate-50 py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <div v-if="karyawan" class="overflow-hidden rounded-3xl bg-white border border-slate-200 shadow-sm relative z-0 flex flex-col">
                    <!-- Bagian Atas: Identitas Karyawan -->
                    <div class="flex flex-col md:flex-row items-center gap-8 p-8 md:p-10 z-10 w-full">
                        <img :src="karyawan.foto_url" alt="Profile" class="w-32 h-32 rounded-full object-cover shadow-md border-4 border-slate-50 ring-1 ring-slate-200" />
                        <div class="text-center md:text-left flex-1">
                            <h3 class="text-3xl font-extrabold text-slate-800 tracking-tight">{{ karyawan.nama_lengkap }}</h3>
                            <p class="text-slate-500 font-medium mt-1 text-lg">{{ karyawan.nomor_induk_karyawan }} <span class="mx-2 text-slate-300">|</span> {{ karyawan.jabatan }}</p>
                            <div class="mt-4 flex flex-wrap items-center justify-center md:justify-start gap-3">
                                <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-sm font-semibold text-red-700 ring-1 ring-inset ring-red-600/20">
                                    {{ karyawan.departemen?.nama_departemen || 'Tanpa Departemen' }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600">
                                    Bergabung: {{ karyawan.tgl_bergabung }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bagian Bawah: Informasi Saldo Cuti (Grid Style) -->
                    <div v-if="karyawan.saldo_cuti && karyawan.saldo_cuti.length" class="bg-slate-50 border-t border-slate-100 p-6 md:px-10 z-10">
                        <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-4">Informasi Saldo Cuti</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <div v-for="cuti in karyawan.saldo_cuti" :key="cuti.id" class="bg-white border border-slate-200 rounded-2xl p-4 text-center shadow-sm hover:shadow-md transition-shadow">
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-wider truncate" :title="cuti.jenis_cuti?.nama_cuti">{{ cuti.jenis_cuti?.nama_cuti }}</p>
                                <p class="text-2xl font-black text-slate-800 mt-2">
                                    {{ cuti.jenis_cuti?.is_unlimited ? '∞' : cuti.saldo_akhir }}
                                    <span v-if="!cuti.jenis_cuti?.is_unlimited" class="text-sm font-semibold text-slate-400">hr</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hero Banner Fallback (Jika hanya User biasa/Admin non-karyawan) -->
                <div v-else class="flex items-center justify-between overflow-hidden rounded-3xl bg-gradient-to-r from-red-600 to-rose-500 p-8 shadow-lg md:p-10 relative z-0">
                    <div class="relative z-10">
                        <h3 class="text-3xl font-extrabold tracking-tight text-white">Pengaturan Akun & Keamanan</h3>
                        <p class="mt-2 max-w-2xl text-red-100 font-medium">Kelola informasi data diri, perbarui kata sandi secara berkala, dan lindungi persetujuan transaksi menggunakan PIN Keamanan.</p>
                    </div>
                    <div class="hidden opacity-20 md:block mix-blend-screen overflow-visible">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-32 w-32 translate-x-4 scale-150 transform">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 11.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <div class="grid relative grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Kiri: Info Profil & Sandi -->
                    <div class="space-y-6 md:col-span-2">
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md sm:p-8">
                            <section>
                                <header>
                                    <h2 class="text-lg font-bold text-slate-900">Informasi Pribadi & Akun</h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Informasi identitas dan demografi Anda. Data ini dikelola oleh tim HR dan tidak dapat diubah secara mandiri.
                                    </p>
                                </header>

                                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Data Akun -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide">Nama Akun (Sistem)</label>
                                        <p class="mt-1 text-slate-800 font-semibold">{{ $page.props.auth.user.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide">Email (Sistem)</label>
                                        <p class="mt-1 text-slate-800 font-semibold">{{ $page.props.auth.user.email }}</p>
                                    </div>
                                    
                                    <template v-if="karyawan">
                                        <div class="col-span-1 md:col-span-2 border-t border-slate-100 my-2 pt-6">
                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Detail Demografi & HR</h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide">Jenis Kelamin</label>
                                                    <p class="mt-1 text-slate-800 font-semibold">{{ karyawan.jenis_kelamin === 'L' ? 'Laki-Laki' : (karyawan.jenis_kelamin === 'P' ? 'Perempuan' : '-') }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide">Tempat, Tgl Lahir</label>
                                                    <p class="mt-1 text-slate-800 font-semibold">{{ karyawan.tempat_lahir || '-' }}, {{ karyawan.tgl_lahir || '-' }}</p>
                                                </div>
                                                <div class="col-span-1 md:col-span-2">
                                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide">Alamat Domisili</label>
                                                    <p class="mt-1 text-slate-800 font-semibold">{{ karyawan.alamat || '-' }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide">Jabatan</label>
                                                    <p class="mt-1 text-slate-800 font-semibold">{{ karyawan.jabatan || '-' }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide">Departemen</label>
                                                    <p class="mt-1 text-slate-800 font-semibold">{{ karyawan.departemen?.nama_departemen || 'Tanpa Departemen' }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide">Status Karyawan</label>
                                                    <p class="mt-1 text-slate-800 font-semibold">{{ karyawan.status_karyawan || '-' }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide">Status Pajak (PTKP)</label>
                                                    <p class="mt-1 text-slate-800 font-semibold">{{ karyawan.status_ptkp || '-' }}</p>
                                                </div>
                                                
                                                <div class="col-span-1 md:col-span-2 mt-4 p-5 bg-slate-50 rounded-2xl border border-slate-100">
                                                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wide mb-3">Rekening Penerimaan Utama</label>
                                                    <div v-if="primaryBank" class="flex items-center gap-4">
                                                        <div class="h-12 w-12 bg-white rounded-xl border border-slate-200 flex items-center justify-center shadow-sm text-slate-400">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <p class="text-base font-bold text-slate-800">{{ primaryBank.nama_bank }}</p>
                                                            <p class="text-sm text-slate-500 font-medium mt-0.5">{{ primaryBank.nomor_rekening }} <span class="mx-1 text-slate-300">•</span> {{ primaryBank.nama_pemilik }}</p>
                                                        </div>
                                                    </div>
                                                    <p v-else class="text-sm text-slate-500 font-medium italic">Belum ada data rekening terdaftar. Hubungi HR untuk mendaftarkan rekening Anda.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </section>
                        </div>
                    </div>

                    <!-- Kanan: Keamanan (Sandi & PIN) -->
                    <div class="space-y-6">
                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md sm:p-8">
                            <UpdatePasswordForm />
                        </div>

                        <div class="rounded-3xl bg-gradient-to-br from-slate-800 to-slate-950 p-6 shadow-xl ring-1 ring-slate-800 sm:p-8 text-white relative overflow-hidden z-0">
                            <!-- Aksen visual dark -->
                            <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-slate-700 blur-[40px] opacity-50"></div>
                            <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-rose-500 blur-[50px] opacity-20"></div>
                            
                            <section class="relative z-10 w-full">
                                <header>
                                    <h2 class="text-xl font-bold flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-rose-400">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                        PIN Keamanan
                                    </h2>
                                    <p class="mt-3 text-sm leading-relaxed text-slate-300">
                                        Lapis keamanan ekstra untuk otorisasi Anda. Wajib diaktifkan untuk meyetujui (Approve) transaksi krusial dalam modul ERP dan Pencairan Dana.
                                    </p>
                                </header>

                                <div class="mt-8">
                                    <button 
                                        @click="openPinModal"
                                        class="w-full rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 py-3.5 px-4 text-sm font-semibold tracking-wide text-white transition-all hover:scale-[1.02] shadow-inner focus:ring-2 focus:ring-white focus:outline-none flex justify-center items-center gap-2"
                                    >
                                        <svg v-if="!hasPin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>

                                        {{ hasPin ? 'Ubah Kode PIN Anda' : 'Buat PIN Sekarang' }}
                                    </button>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <PinModal 
        :show="showPinModal" 
        :mode="pinMode" 
        :title="hasPin ? 'Ubah PIN Keamanan' : 'Buat PIN Baru'"
        @close="showPinModal = false"
        @success="onPinSuccess"
    />
</template>
