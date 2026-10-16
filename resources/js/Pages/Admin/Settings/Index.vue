<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    settings: Object,
    users: Array,
    currentUserId: [Number, String],
    pageTitle: String,
    can: Object,
});

const page = usePage();

const presetTemplates = {
    approval: {
        title: 'Approval Dibutuhkan: Pengajuan Dana Baru',
        message: 'Pengajuan nomor PJ-2026-004 membutuhkan persetujuan Anda segera.',
        link: '/approvals',
    },
    cuti: {
        title: 'Pengajuan Cuti Disetujui',
        message: 'Pengajuan cuti tahunan Anda telah disetujui oleh HRD dan Direktur.',
        link: '/cuti-filter',
    },
    attendance: {
        title: 'Pengingat Absensi Kerja',
        message: 'Waktu presensi masuk kerja hampir berakhir. Silakan lakukan check-in sekarang.',
        link: '/absensi-history',
    },
    task: {
        title: 'Tugas Baru Ditugaskan',
        message: 'Anda mendapatkan tugas baru: "Laporan Evaluasi Operasional Mingguan".',
        link: '/tasks',
    },
    ess: {
        title: 'Slip Gaji Diterbitkan',
        message: 'Slip gaji periode September 2026 telah diterbitkan. Silakan periksa rincian pada menu ESS.',
        link: '/ess?tab=payslip',
    },
    announcement: {
        title: 'Pengumuman Manajemen Perusahaan',
        message: 'Jadwal penyesuaian jam kerja dan briefing bulanan Persija Jakarta.',
        link: '/dashboard',
    },
    custom: {
        title: '',
        message: '',
        link: '/dashboard',
    },
};

const notifForm = useForm({
    target_user_id: props.currentUserId || (props.users && props.users[0]?.id) || '',
    type: 'approval',
    title: presetTemplates.approval.title,
    message: presetTemplates.approval.message,
    link: presetTemplates.approval.link,
});

function onNotifTypeChange() {
    const preset = presetTemplates[notifForm.type];
    if (preset) {
        notifForm.title = preset.title;
        notifForm.message = preset.message;
        notifForm.link = preset.link;
    }
}

function sendNotification() {
    notifForm.post(route('admin.settings.test-notification'), {
        preserveScroll: true,
    });
}

const form = useForm({
    app_name: props.settings.app_name || '',
    app_logo: null,
    app_favicon: null,
    company_name: props.settings.company_name || '',
    company_address: props.settings.company_address || '',
    company_logo: null,
    reimburse_tolerance_limit: props.settings.reimburse_tolerance_limit || 1000000,
});

const logoPreview = ref(props.settings.app_logo ? `/storage/${props.settings.app_logo}` : null);
const faviconPreview = ref(props.settings.app_favicon ? `/storage/${props.settings.app_favicon}` : null);
const companyLogoPreview = ref(props.settings.company_logo ? `/storage/${props.settings.company_logo}` : null);

function onLogoChange(event) {
    const file = event.target.files[0];
    if (!file) return;

    form.app_logo = file;
    logoPreview.value = URL.createObjectURL(file);
}

function onFaviconChange(event) {
    const file = event.target.files[0];
    if (!file) return;

    form.app_favicon = file;
    faviconPreview.value = URL.createObjectURL(file);
}

function onCompanyLogoChange(event) {
    const file = event.target.files[0];
    if (!file) return;

    form.company_logo = file;
    companyLogoPreview.value = URL.createObjectURL(file);
}

function submit() {
    form.post(route('admin.settings.update'), {
        forceFormData: true,
        onSuccess: () => {
            document.getElementById('app_logo_input').value = '';
            if (document.getElementById('app_favicon_input')) document.getElementById('app_favicon_input').value = '';
            document.getElementById('company_logo_input').value = '';
            form.app_logo = null;
            form.app_favicon = null;
            form.company_logo = null;
        }
    });
}
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="py-12 pt-4">
            <div class="max-w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- App Settings -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pengaturan Aplikasi</h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <InputLabel for="app_name" value="Nama Aplikasi" />
                                    <TextInput
                                        id="app_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.app_name"
                                    />
                                    <InputError class="mt-2" :message="form.errors.app_name" />
                                </div>

                                <div>
                                    <InputLabel for="app_logo" value="Logo Aplikasi" />
                                    <div class="mt-2 flex items-center gap-x-3">
                                        <img v-if="logoPreview" :src="logoPreview" alt="Logo Preview" class="h-16 w-16 object-contain rounded-md bg-gray-100 dark:bg-gray-700">
                                        <div v-else class="h-16 w-16 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-md text-gray-400">
                                            No Logo
                                        </div>
                                        <input id="app_logo_input" type="file" @input="onLogoChange" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"/>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.app_logo" />
                                </div>

                                <div>
                                    <InputLabel for="app_favicon" value="Favicon Aplikasi (PNG/ICO)" />
                                    <div class="mt-2 flex items-center gap-x-3">
                                        <img v-if="faviconPreview" :src="faviconPreview" alt="Favicon Preview" class="h-10 w-10 object-contain rounded bg-gray-100 dark:bg-gray-700">
                                        <div v-else class="h-10 w-10 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded text-gray-400 text-xs">
                                            N/A
                                        </div>
                                        <input id="app_favicon_input" type="file" @input="onFaviconChange" accept=".png,.ico" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"/>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.app_favicon" />
                                </div>
                            </div>
                        </div>

                        <!-- Finance Settings -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pengaturan Finansial & Persetujuan</h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <InputLabel for="reimburse_tolerance_limit" value="Batas Toleransi Kurang Bayar (Rp) - Laporan Tanpa Re-Approval" />
                                    <TextInput
                                        id="reimburse_tolerance_limit"
                                        type="number"
                                        class="mt-1 block w-full"
                                        v-model="form.reimburse_tolerance_limit"
                                    />
                                    <p class="mt-1 text-sm text-gray-500">Jika nombok laporan > dari nilai ini, maka wajib Re-Approval ke atasan.</p>
                                    <InputError class="mt-2" :message="form.errors.reimburse_tolerance_limit" />
                                </div>
                            </div>
                        </div>

                        <!-- Company Settings -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Identitas Perusahaan (Untuk Cetak)</h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <InputLabel for="company_name" value="Nama Perusahaan" />
                                    <TextInput
                                        id="company_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.company_name"
                                    />
                                    <InputError class="mt-2" :message="form.errors.company_name" />
                                </div>

                                <div>
                                    <InputLabel for="company_address" value="Alamat Perusahaan" />
                                    <textarea
                                        id="company_address"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows="3"
                                        v-model="form.company_address"
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.company_address" />
                                </div>

                                <div>
                                    <InputLabel for="company_logo" value="Logo Perusahaan" />
                                    <div class="mt-2 flex items-center gap-x-3">
                                        <img v-if="companyLogoPreview" :src="companyLogoPreview" alt="Company Logo Preview" class="h-16 w-16 object-contain rounded-md bg-gray-100 dark:bg-gray-700">
                                        <div v-else class="h-16 w-16 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-md text-gray-400">
                                            No Logo
                                        </div>
                                        <input id="company_logo_input" type="file" @input="onCompanyLogoChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"/>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.company_logo" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Simpan Pengaturan</PrimaryButton>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Tersimpan.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- Push Notification Simulation Card -->
                <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-red-100 dark:border-red-900/30">
                    <div class="p-6">
                        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center space-x-3">
                                <div class="p-2.5 bg-red-100 dark:bg-red-950/50 rounded-xl text-red-600 dark:text-red-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Uji Coba Push Notifikasi ke Mobile</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Pilih jenis notifikasi dan kirim langsung ke aplikasi mobile pengguna untuk menguji notifikasi real-time & deep link.</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                Realtime Sync Active
                            </span>
                        </div>

                        <form @submit.prevent="sendNotification" class="mt-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Target User -->
                                <div>
                                    <InputLabel for="target_user_id" value="Karyawan / Pengguna Target" />
                                    <select
                                        id="target_user_id"
                                        v-model="notifForm.target_user_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    >
                                        <option v-for="u in users" :key="u.id" :value="u.id">
                                            {{ u.name }} ({{ u.role }} - {{ u.email }}) {{ u.has_device_token ? '📱 HP: ' + (u.device_name || 'Tersambung') : '' }}
                                        </option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Pilih akun yang sedang login di aplikasi mobile Anda.</p>
                                    <InputError class="mt-2" :message="notifForm.errors.target_user_id" />
                                </div>

                                <!-- Notification Type -->
                                <div>
                                    <InputLabel for="notif_type" value="Macam / Tipe Notifikasi" />
                                    <select
                                        id="notif_type"
                                        v-model="notifForm.type"
                                        @change="onNotifTypeChange"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    >
                                        <option value="approval">📋 Approval Pengajuan Dana (/approvals)</option>
                                        <option value="cuti">🏖️ Pengajuan Cuti Disetujui (/cuti-filter)</option>
                                        <option value="attendance">⏰ Pengingat Absensi Masuk/Pulang (/absensi-history)</option>
                                        <option value="task">📌 Tugas Baru Ditugaskan (/tasks)</option>
                                        <option value="ess">💰 Slip Gaji Diterbitkan (/ess?tab=payslip)</option>
                                        <option value="announcement">📢 Pengumuman Manajemen (/dashboard)</option>
                                        <option value="custom">✏️ Custom / Teks Bebas</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Template judul, pesan, dan tautan deep link otomatis disesuaikan.</p>
                                    <InputError class="mt-2" :message="notifForm.errors.type" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Title -->
                                <div>
                                    <InputLabel for="notif_title" value="Judul Notifikasi" />
                                    <TextInput
                                        id="notif_title"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="notifForm.title"
                                        placeholder="Judul notifikasi..."
                                    />
                                    <InputError class="mt-2" :message="notifForm.errors.title" />
                                </div>

                                <!-- Link -->
                                <div>
                                    <InputLabel for="notif_link" value="Target Deep Link di HP" />
                                    <TextInput
                                        id="notif_link"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="notifForm.link"
                                        placeholder="/approvals, /cuti-filter, dll."
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Rute di aplikasi mobile yang akan dibuka saat notifikasi di-tap.</p>
                                    <InputError class="mt-2" :message="notifForm.errors.link" />
                                </div>
                            </div>

                            <!-- Message -->
                            <div>
                                <InputLabel for="notif_message" value="Isi Pesan Notifikasi" />
                                <textarea
                                    id="notif_message"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    v-model="notifForm.message"
                                    placeholder="Tuliskan isi pesan notifikasi..."
                                ></textarea>
                                <InputError class="mt-2" :message="notifForm.errors.message" />
                            </div>

                            <!-- Live Mobile Mockup Preview -->
                            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-2">Simulasi Tampilan di Handphone (Notification Bar):</span>
                                <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-start space-x-3 max-w-lg">
                                    <div class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center text-white shrink-0 font-bold text-xs">
                                        PSJ
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate">{{ notifForm.title || 'Judul Notifikasi' }}</span>
                                            <span class="text-[10px] text-gray-400">Sekarang</span>
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5 line-clamp-2">{{ notifForm.message || 'Isi pesan notifikasi...' }}</p>
                                        <span class="inline-block mt-1 text-[10px] font-medium text-red-600 dark:text-red-400">Tujuan: {{ notifForm.link || '/dashboard' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 pt-2">
                                <button
                                    type="submit"
                                    :disabled="notifForm.processing"
                                    class="inline-flex items-center px-5 py-2.5 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold text-sm rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all disabled:opacity-50"
                                >
                                    <svg v-if="notifForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    Kirim Notifikasi ke Mobile
                                </button>
                                <p v-if="notifForm.recentlySuccessful" class="text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Berhasil dikirim ke perangkat!
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
