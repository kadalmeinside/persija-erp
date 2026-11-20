<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import {
    BanknotesIcon,
    BellAlertIcon,
    BuildingOffice2Icon,
    EnvelopeIcon,
    PaperAirplaneIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Object,
    users: Array,
    roles: {
        type: Array,
        default: () => [],
    },
    currentUserId: [Number, String],
    pageTitle: String,
    can: Object,
});

const tabs = [
    { id: 'general', label: 'Umum & Perusahaan', icon: BuildingOffice2Icon },
    { id: 'finance', label: 'Finansial', icon: BanknotesIcon },
    { id: 'mail', label: 'Email (SMTP)', icon: EnvelopeIcon },
    { id: 'fcm', label: 'Firebase FCM', icon: BellAlertIcon },
    { id: 'push', label: 'Push & Broadcast', icon: PaperAirplaneIcon },
];

const activeTab = ref('general');

function setTab(tabId) {
    if (tabs.some((t) => t.id === tabId)) {
        activeTab.value = tabId;
    }
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get('tab');
    if (tabParam && tabs.some((t) => t.id === tabParam)) {
        activeTab.value = tabParam;
    }
});

const presetTemplates = {
    approval: {
        title: 'Approval Dibutuhkan: Pengajuan Dana Baru',
        message: 'Ada pengajuan baru yang membutuhkan persetujuan Anda segera.',
        link: '/approvals',
    },
    cuti: {
        title: 'Pembaruan Status & Informasi Cuti',
        message: 'Silakan periksa daftar cuti dan persetujuan pada menu Cuti.',
        link: '/cuti-filter',
    },
    attendance: {
        title: 'Pengingat Absensi Kerja',
        message: 'Waktu presensi kerja telah dibuka. Pastikan Anda melakukan absensi tepat waktu.',
        link: '/absensi-history',
    },
    task: {
        title: 'Pembaruan Tugas Operasional',
        message: 'Terdapat pembaruan pada daftar tugas tim Anda. Silakan tinjau detail tugas.',
        link: '/tasks',
    },
    ess: {
        title: 'Slip Gaji Telah Diterbitkan',
        message: 'Slip gaji periode berjalan telah tersedia dan dapat diunduh pada menu ESS.',
        link: '/ess?tab=payslip',
    },
    announcement: {
        title: 'Pengumuman Manajemen Perusahaan',
        message: 'Mohon perhatian seluruh karyawan terkait informasi jadwal kerja dan koordinasi internal.',
        link: '/dashboard',
    },
    custom: {
        title: '',
        message: '',
        link: '/dashboard',
    },
};

const routeCatalog = [
    { value: '/dashboard', label: 'Dashboard Utama (/dashboard)' },
    { value: '/approvals', label: 'Approval Center (/approvals)' },
    { value: '/absensi-history', label: 'Riwayat Absensi (/absensi-history)' },
    { value: '/cuti', label: 'Cuti & Saldo Cuti (/cuti)' },
    { value: '/cuti-filter', label: 'Filter & Laporan Cuti (/cuti-filter)' },
    { value: '/tasks', label: 'Manajemen Tugas (/tasks)' },
    { value: '/calendar', label: 'Kalender Perusahaan (/calendar)' },
    { value: '/ess?tab=payslip', label: 'ESS: Slip Gaji (/ess?tab=payslip)' },
    { value: '/ess?tab=koreksi', label: 'ESS: Koreksi Absensi (/ess?tab=koreksi)' },
    { value: '/ess?tab=dinas', label: 'ESS: Dinas Luar (/ess?tab=dinas)' },
    { value: '/ess?tab=reimbursement', label: 'ESS: Status Pengajuan Dana (/ess?tab=reimbursement)' },
    { value: '/ess?tab=kontrak', label: 'ESS: Informasi Kontrak (/ess?tab=kontrak)' },
    { value: '/ess?tab=dokumen', label: 'ESS: Dokumen Karyawan (/ess?tab=dokumen)' },
    { value: '/profile', label: 'Profil & Keamanan Akun (/profile)' },
    { value: 'custom', label: 'Rute Custom Manual...' },
];

const userSearchQuery = ref('');
const selectedRoutePreset = ref('/approvals');

const notifForm = useForm({
    target_mode: 'single_user',
    target_user_id: props.currentUserId || (props.users && props.users[0]?.id) || '',
    target_user_ids: [],
    target_role: (props.roles && props.roles[0]) || '',
    type: 'approval',
    click_action: 'open_page',
    title: presetTemplates.approval.title,
    message: presetTemplates.approval.message,
    link: presetTemplates.approval.link,
});

const filteredUsers = computed(() => {
    const q = userSearchQuery.value.trim().toLowerCase();
    const list = props.users || [];
    if (!q) return list;
    return list.filter((u) =>
        u.name.toLowerCase().includes(q)
        || u.email.toLowerCase().includes(q)
        || (u.role || '').toLowerCase().includes(q)
        || (u.jabatan || '').toLowerCase().includes(q)
    );
});

const connectedDeviceUsersCount = computed(() =>
    (props.users || []).filter((u) => u.has_device_token).length
);

const targetSummaryBadge = computed(() => {
    if (notifForm.target_mode === 'blast_all') {
        return `Blast Semua (${(props.users || []).length} User)`;
    }
    if (notifForm.target_mode === 'by_role') {
        const count = (props.users || []).filter((u) => u.role === notifForm.target_role).length;
        return `Role ${notifForm.target_role || '-'} (${count} User)`;
    }
    if (notifForm.target_mode === 'multi_user') {
        return `Multi-User (${notifForm.target_user_ids.length} Dipilih)`;
    }
    const found = (props.users || []).find((u) => u.id === notifForm.target_user_id);
    return found ? `1 User: ${found.name}` : '1 User Target';
});

function selectAllMultiUsers() {
    notifForm.target_user_ids = filteredUsers.value.map((u) => u.id);
}

function selectMobileConnectedUsers() {
    notifForm.target_user_ids = filteredUsers.value.filter((u) => u.has_device_token).map((u) => u.id);
}

function clearMultiUsers() {
    notifForm.target_user_ids = [];
}

function onRoutePresetChange() {
    if (selectedRoutePreset.value !== 'custom') {
        notifForm.link = selectedRoutePreset.value;
    }
}

function onNotifTypeChange() {
    const preset = presetTemplates[notifForm.type];
    if (preset) {
        notifForm.title = preset.title;
        notifForm.message = preset.message;
        notifForm.link = preset.link;
        const known = routeCatalog.some((r) => r.value === preset.link);
        selectedRoutePreset.value = known ? preset.link : 'custom';
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
    mail_enabled: props.settings.mail_enabled === '1' || props.settings.mail_enabled === true,
    mail_mailer: props.settings.mail_mailer || 'smtp',
    mail_host: props.settings.mail_host || '',
    mail_port: props.settings.mail_port || 587,
    mail_username: props.settings.mail_username || '',
    mail_password: '',
    mail_encryption: props.settings.mail_encryption || 'tls',
    mail_from_address: props.settings.mail_from_address || '',
    mail_from_name: props.settings.mail_from_name || 'Persija ERP',
    fcm_enabled: props.settings.fcm_enabled === '1' || props.settings.fcm_enabled === true,
    fcm_project_id: props.settings.fcm_project_id || '',
    fcm_service_account_json: '',
});

const testMailForm = useForm({
    test_email: '',
});

function sendTestMail() {
    testMailForm.post(route('admin.settings.test-email'), {
        preserveScroll: true,
    });
}

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
        preserveScroll: true,
        onSuccess: () => {
            const appLogoInput = document.getElementById('app_logo_input');
            const appFaviconInput = document.getElementById('app_favicon_input');
            const companyLogoInput = document.getElementById('company_logo_input');
            if (appLogoInput) appLogoInput.value = '';
            if (appFaviconInput) appFaviconInput.value = '';
            if (companyLogoInput) companyLogoInput.value = '';
            form.app_logo = null;
            form.app_favicon = null;
            form.company_logo = null;
            form.mail_password = '';
            form.fcm_service_account_json = '';
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

        <div class="py-4 space-y-6">
            <div class="max-w-full mx-auto space-y-6">
                <!-- Tab Card Navigasi Pengaturan Global -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-2">
                    <div
                        role="tablist"
                        aria-label="Kategori Pengaturan Global"
                        class="flex flex-wrap sm:flex-nowrap overflow-x-auto gap-1.5"
                    >
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            :id="'tab-' + tab.id"
                            type="button"
                            role="tab"
                            :aria-selected="activeTab === tab.id"
                            :aria-controls="'panel-' + tab.id"
                            @click="setTab(tab.id)"
                            :class="activeTab === tab.id
                                ? 'bg-red-600 text-white shadow-sm'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60'"
                            class="inline-flex items-center gap-2 min-h-[44px] px-4 py-2.5 rounded-lg text-sm font-semibold whitespace-nowrap transition-colors cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
                        >
                            <component :is="tab.icon" class="w-4 h-4 shrink-0" aria-hidden="true" />
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Tab 1: Umum & Perusahaan -->
                <div
                    v-if="activeTab === 'general'"
                    id="panel-general"
                    role="tabpanel"
                    aria-labelledby="tab-general"
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-200 dark:border-gray-700"
                >
                    <form @submit.prevent="submit" class="p-6 space-y-8">
                        <!-- Branding & Identitas Aplikasi -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Identitas & Branding Aplikasi</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Atur nama aplikasi, logo utama, dan favicon browser yang tampil di seluruh sistem.</p>

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
                                    <InputLabel for="app_logo_input" value="Logo Aplikasi" />
                                    <div class="mt-2 flex items-center gap-x-3">
                                        <img v-if="logoPreview" :src="logoPreview" alt="Logo Preview" class="h-16 w-16 object-contain rounded-lg bg-gray-100 dark:bg-gray-700">
                                        <div v-else class="h-16 w-16 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-500 dark:text-gray-400 text-xs">
                                            No Logo
                                        </div>
                                        <input id="app_logo_input" type="file" @input="onLogoChange" accept="image/*" class="block w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 dark:file:bg-red-950 dark:file:text-red-300 cursor-pointer"/>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.app_logo" />
                                </div>

                                <div>
                                    <InputLabel for="app_favicon_input" value="Favicon Aplikasi (PNG/ICO)" />
                                    <div class="mt-2 flex items-center gap-x-3">
                                        <img v-if="faviconPreview" :src="faviconPreview" alt="Favicon Preview" class="h-10 w-10 object-contain rounded-lg bg-gray-100 dark:bg-gray-700">
                                        <div v-else class="h-10 w-10 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-500 dark:text-gray-400 text-xs">
                                            N/A
                                        </div>
                                        <input id="app_favicon_input" type="file" @input="onFaviconChange" accept=".png,.ico" class="block w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 dark:file:bg-red-950 dark:file:text-red-300 cursor-pointer"/>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.app_favicon" />
                                </div>
                            </div>
                        </div>

                        <!-- Identitas Perusahaan -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Identitas Perusahaan (Untuk Dokumen Cetak)</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Informasi resmi perusahaan yang dicetak pada kop invoice, slip gaji, dan laporan keuangan.</p>

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
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500"
                                        rows="3"
                                        v-model="form.company_address"
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.company_address" />
                                </div>

                                <div>
                                    <InputLabel for="company_logo_input" value="Logo Perusahaan" />
                                    <div class="mt-2 flex items-center gap-x-3">
                                        <img v-if="companyLogoPreview" :src="companyLogoPreview" alt="Company Logo Preview" class="h-16 w-16 object-contain rounded-lg bg-gray-100 dark:bg-gray-700">
                                        <div v-else class="h-16 w-16 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-500 dark:text-gray-400 text-xs">
                                            No Logo
                                        </div>
                                        <input id="company_logo_input" type="file" @input="onCompanyLogoChange" accept="image/*" class="block w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 dark:file:bg-red-950 dark:file:text-red-300 cursor-pointer"/>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.company_logo" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <PrimaryButton :disabled="form.processing">Simpan Pengaturan</PrimaryButton>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Pengaturan berhasil disimpan.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- Tab 2: Finansial & Persetujuan -->
                <div
                    v-else-if="activeTab === 'finance'"
                    id="panel-finance"
                    role="tabpanel"
                    aria-labelledby="tab-finance"
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-200 dark:border-gray-700"
                >
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Pengaturan Finansial & Persetujuan</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Atur ambang batas toleransi persetujuan untuk penyelesaian laporan pertanggungjawaban dana.</p>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <InputLabel for="reimburse_tolerance_limit" value="Batas Toleransi Kurang Bayar (Rp) - Laporan Tanpa Re-Approval" />
                                    <TextInput
                                        id="reimburse_tolerance_limit"
                                        type="number"
                                        class="mt-1 block w-full tabular-nums"
                                        v-model="form.reimburse_tolerance_limit"
                                    />
                                    <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">Jika selisih kurang bayar laporan melebihi nilai ini, maka pengajuan wajib melalui Re-Approval ke atasan.</p>
                                    <InputError class="mt-2" :message="form.errors.reimburse_tolerance_limit" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <PrimaryButton :disabled="form.processing">Simpan Pengaturan</PrimaryButton>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Pengaturan berhasil disimpan.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- Tab 3: Email Server (SMTP) -->
                <div
                    v-else-if="activeTab === 'mail'"
                    id="panel-mail"
                    role="tabpanel"
                    aria-labelledby="tab-mail"
                    class="space-y-6"
                >
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
                        <form @submit.prevent="submit" class="p-6 space-y-6">
                            <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2.5">
                                    <EnvelopeIcon class="w-5 h-5 text-red-600 dark:text-red-400" aria-hidden="true" />
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Konfigurasi Email Server (SMTP)</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Pengaturan SMTP tersimpan terenkripsi di database untuk Lupa Password Mobile dan notifikasi email sistem.</p>
                                    </div>
                                </div>
                                <span
                                    :class="settings.has_mail_password
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'
                                        : 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border text-xs font-semibold"
                                >
                                    <ShieldCheckIcon class="w-4 h-4" aria-hidden="true" />
                                    {{ settings.has_mail_password ? 'Password SMTP Terenkripsi Tersimpan' : 'Password SMTP Belum Diatur' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3">
                                <input
                                    id="mail_enabled"
                                    type="checkbox"
                                    v-model="form.mail_enabled"
                                    class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 cursor-pointer"
                                />
                                <label for="mail_enabled" class="text-sm font-medium text-gray-800 dark:text-gray-200 cursor-pointer">
                                    Aktifkan Konfigurasi Email Database (Override .env)
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="mail_host" value="SMTP Host" />
                                    <TextInput id="mail_host" type="text" class="mt-1 block w-full" v-model="form.mail_host" placeholder="smtp.gmail.com" />
                                    <InputError class="mt-1" :message="form.errors.mail_host" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel for="mail_port" value="Port" />
                                        <TextInput id="mail_port" type="number" class="mt-1 block w-full tabular-nums" v-model="form.mail_port" placeholder="587" />
                                        <InputError class="mt-1" :message="form.errors.mail_port" />
                                    </div>
                                    <div>
                                        <InputLabel for="mail_encryption" value="Enkripsi" />
                                        <select
                                            id="mail_encryption"
                                            v-model="form.mail_encryption"
                                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                        >
                                            <option value="tls">TLS (STARTTLS - 587)</option>
                                            <option value="ssl">SSL (SMTPS - 465)</option>
                                            <option value="none">Tanpa Enkripsi</option>
                                        </select>
                                        <InputError class="mt-1" :message="form.errors.mail_encryption" />
                                    </div>
                                </div>
                                <div>
                                    <InputLabel for="mail_username" value="SMTP Username / Email Login" />
                                    <TextInput id="mail_username" type="text" class="mt-1 block w-full" v-model="form.mail_username" placeholder="noreply@persija.id" />
                                    <InputError class="mt-1" :message="form.errors.mail_username" />
                                </div>
                                <div>
                                    <InputLabel for="mail_password" value="SMTP Password / App Password (Kosongkan jika tidak diubah)" />
                                    <TextInput
                                        id="mail_password"
                                        type="password"
                                        class="mt-1 block w-full"
                                        v-model="form.mail_password"
                                        :placeholder="settings.has_mail_password ? 'Tersimpan terenkripsi (isi untuk mengganti)' : 'Masukkan App Password SMTP'"
                                    />
                                    <InputError class="mt-1" :message="form.errors.mail_password" />
                                </div>
                                <div>
                                    <InputLabel for="mail_from_address" value="Alamat Pengirim (From Address)" />
                                    <TextInput id="mail_from_address" type="email" class="mt-1 block w-full" v-model="form.mail_from_address" placeholder="erp@persija.id" />
                                    <InputError class="mt-1" :message="form.errors.mail_from_address" />
                                </div>
                                <div>
                                    <InputLabel for="mail_from_name" value="Nama Pengirim (From Name)" />
                                    <TextInput id="mail_from_name" type="text" class="mt-1 block w-full" v-model="form.mail_from_name" placeholder="Persija ERP" />
                                    <InputError class="mt-1" :message="form.errors.mail_from_name" />
                                </div>
                            </div>

                            <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <PrimaryButton :disabled="form.processing">Simpan Pengaturan</PrimaryButton>
                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Pengaturan berhasil disimpan.</p>
                                </Transition>
                            </div>
                        </form>
                    </div>

                    <!-- Uji Coba Kirim Email SMTP -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="p-6">
                            <div class="flex items-center gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="p-2.5 bg-red-50 dark:bg-red-950/50 rounded-xl text-red-600 dark:text-red-400">
                                    <PaperAirplaneIcon class="w-5 h-5" aria-hidden="true" />
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">Uji Coba Pengiriman Email SMTP</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Pastikan pengaturan SMTP di atas sudah disimpan terlebih dahulu sebelum melakukan tes kirim.</p>
                                </div>
                            </div>
                            <form @submit.prevent="sendTestMail" class="mt-4 flex flex-col sm:flex-row sm:items-end gap-4">
                                <div class="flex-1">
                                    <InputLabel for="test_email" value="Alamat Email Penerima Uji Coba" />
                                    <TextInput
                                        id="test_email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        v-model="testMailForm.test_email"
                                        placeholder="nama@persija.id"
                                        required
                                    />
                                    <InputError class="mt-1" :message="testMailForm.errors.test_email" />
                                </div>
                                <button
                                    type="submit"
                                    :disabled="testMailForm.processing"
                                    class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 disabled:opacity-50 cursor-pointer"
                                >
                                    <PaperAirplaneIcon class="w-4 h-4" aria-hidden="true" />
                                    <span>Kirim Email Uji Coba</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Firebase FCM HTTP v1 -->
                <div
                    v-else-if="activeTab === 'fcm'"
                    id="panel-fcm"
                    role="tabpanel"
                    aria-labelledby="tab-fcm"
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-200 dark:border-gray-700"
                >
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-2.5">
                                <BellAlertIcon class="w-5 h-5 text-red-600 dark:text-red-400" aria-hidden="true" />
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Push Notification Firebase (FCM HTTP v1)</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Kredensial Service Account JSON resmi dari Firebase Console (Project Settings -> Service Accounts) untuk pengiriman push real-time ke aplikasi mobile.</p>
                                </div>
                            </div>
                            <span
                                :class="settings.has_fcm_service_account
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'
                                    : 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800'"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border text-xs font-semibold"
                            >
                                <ShieldCheckIcon class="w-4 h-4" aria-hidden="true" />
                                {{ settings.has_fcm_service_account ? 'Service Account JSON Terenkripsi Tersimpan' : 'Service Account JSON Belum Diatur' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <input
                                id="fcm_enabled"
                                type="checkbox"
                                v-model="form.fcm_enabled"
                                class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 cursor-pointer"
                            />
                            <label for="fcm_enabled" class="text-sm font-medium text-gray-800 dark:text-gray-200 cursor-pointer">
                                Aktifkan Pengiriman Push Notification FCM HTTP v1
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <InputLabel for="fcm_project_id" value="Firebase Project ID" />
                                <TextInput id="fcm_project_id" type="text" class="mt-1 block w-full" v-model="form.fcm_project_id" placeholder="Otomatis terisi dari JSON jika dikosongkan" />
                                <InputError class="mt-1" :message="form.errors.fcm_project_id" />
                            </div>
                            <div>
                                <InputLabel for="fcm_service_account_json" value="Firebase Service Account JSON (Kosongkan jika tidak diubah)" />
                                <textarea
                                    id="fcm_service_account_json"
                                    rows="5"
                                    v-model="form.fcm_service_account_json"
                                    :placeholder="settings.has_fcm_service_account ? 'Service account JSON tersimpan terenkripsi. Tempel JSON baru jika ingin mengganti.' : '{ &quot;type&quot;: &quot;service_account&quot;, &quot;project_id&quot;: &quot;...&quot;, &quot;private_key&quot;: &quot;...&quot;, &quot;client_email&quot;: &quot;...&quot; }'"
                                    class="mt-1 block w-full font-mono text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500"
                                ></textarea>
                                <InputError class="mt-1" :message="form.errors.fcm_service_account_json" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <PrimaryButton :disabled="form.processing">Simpan Pengaturan</PrimaryButton>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Pengaturan berhasil disimpan.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- Tab 5: Push Notification & Broadcast Center -->
                <div
                    v-else-if="activeTab === 'push'"
                    id="panel-push"
                    role="tabpanel"
                    aria-labelledby="tab-push"
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl border border-gray-200 dark:border-gray-700"
                >
                    <div class="p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center space-x-3">
                                <div class="p-2.5 bg-red-50 dark:bg-red-950/50 rounded-xl text-red-600 dark:text-red-400">
                                    <BellAlertIcon class="h-6 w-6" aria-hidden="true" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Pusat Pengiriman Push Notifikasi & Broadcast</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Kirim pengumuman atau notifikasi terarah ke 1 user, multi-user, role tertentu, atau blast seluruh pengguna beserta kontrol aksi klik.</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800 tabular-nums">
                                {{ connectedDeviceUsersCount }} Perangkat Mobile Terdaftar
                            </span>
                        </div>

                        <form @submit.prevent="sendNotification" class="mt-6 space-y-6">
                            <!-- Mode Target Penerima -->
                            <fieldset>
                                <legend class="block font-medium text-sm text-gray-700 dark:text-gray-300">Mode Target Penerima</legend>
                                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                    <label
                                        v-for="mode in [
                                            { id: 'single_user', title: 'Per User', desc: 'Kirim ke 1 karyawan spesifik' },
                                            { id: 'multi_user', title: 'Multi-User', desc: 'Pilih beberapa user manual' },
                                            { id: 'by_role', title: 'Per Role / Divisi', desc: 'Semua user dengan role tertentu' },
                                            { id: 'blast_all', title: 'Blast Semua', desc: 'Seluruh pengguna aktif ERP' },
                                        ]"
                                        :key="mode.id"
                                        :for="'target_mode_' + mode.id"
                                        :class="notifForm.target_mode === mode.id
                                            ? 'border-red-600 bg-red-50/70 dark:bg-red-950/30 dark:border-red-500'
                                            : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/40'"
                                        class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer transition-colors"
                                    >
                                        <input
                                            :id="'target_mode_' + mode.id"
                                            type="radio"
                                            name="target_mode"
                                            :value="mode.id"
                                            v-model="notifForm.target_mode"
                                            class="mt-0.5 text-red-600 focus:ring-red-500"
                                        />
                                        <div>
                                            <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100">{{ mode.title }}</span>
                                            <span class="block text-xs text-gray-600 dark:text-gray-400">{{ mode.desc }}</span>
                                        </div>
                                    </label>
                                </div>
                                <InputError class="mt-2" :message="notifForm.errors.target_mode" />
                            </fieldset>

                            <!-- Input Target Dinamis sesuai Mode -->
                            <div v-if="notifForm.target_mode === 'single_user'">
                                <InputLabel for="target_user_id" value="Pilih Karyawan / Pengguna Target" />
                                <select
                                    id="target_user_id"
                                    v-model="notifForm.target_user_id"
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                >
                                    <option v-for="u in users" :key="u.id" :value="u.id">
                                        {{ u.name }} ({{ u.role }} - {{ u.email }}) {{ u.has_device_token ? '[Mobile: ' + (u.device_name || 'Tersambung') + ']' : '' }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="notifForm.errors.target_user_id" />
                            </div>

                            <div v-else-if="notifForm.target_mode === 'multi_user'" class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/40 space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <InputLabel for="user_search_query" :value="'Pilih Beberapa Pengguna (' + notifForm.target_user_ids.length + ' dipilih)'" />
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="selectAllMultiUsers" class="px-2.5 py-1 text-xs font-semibold rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                                            Pilih Semua ({{ filteredUsers.length }})
                                        </button>
                                        <button type="button" @click="selectMobileConnectedUsers" class="px-2.5 py-1 text-xs font-semibold rounded-md border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 cursor-pointer">
                                            Hanya Mobile Tersambung
                                        </button>
                                        <button type="button" @click="clearMultiUsers" class="px-2.5 py-1 text-xs font-semibold rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 cursor-pointer">
                                            Reset
                                        </button>
                                    </div>
                                </div>
                                <TextInput
                                    id="user_search_query"
                                    type="text"
                                    v-model="userSearchQuery"
                                    placeholder="Cari nama, email, jabatan, atau role..."
                                    class="block w-full text-sm"
                                />
                                <div class="max-h-56 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                                    <label
                                        v-for="u in filteredUsers"
                                        :key="u.id"
                                        :for="'multi_user_' + u.id"
                                        class="flex items-center justify-between gap-3 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <input
                                                :id="'multi_user_' + u.id"
                                                type="checkbox"
                                                :value="u.id"
                                                v-model="notifForm.target_user_ids"
                                                class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500"
                                            />
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ u.name }}</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ u.role }} | {{ u.email }}</p>
                                            </div>
                                        </div>
                                        <span
                                            v-if="u.has_device_token"
                                            class="shrink-0 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                                        >
                                            Mobile Aktif
                                        </span>
                                    </label>
                                </div>
                                <InputError class="mt-1" :message="notifForm.errors.target_user_ids" />
                            </div>

                            <div v-else-if="notifForm.target_mode === 'by_role'">
                                <InputLabel for="target_role" value="Pilih Role Pengguna Target" />
                                <select
                                    id="target_role"
                                    v-model="notifForm.target_role"
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                >
                                    <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                                </select>
                                <InputError class="mt-2" :message="notifForm.errors.target_role" />
                            </div>

                            <div v-else class="p-4 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/30 text-sm text-amber-900 dark:text-amber-200">
                                <p class="font-semibold">Mode Blast Semua Pengguna Aktif</p>
                                <p class="text-xs mt-1">Pesan ini akan dikirimkan ke seluruh <strong>{{ (users || []).length }} akun pengguna</strong> (notifikasi in-app dan push FCM ke {{ connectedDeviceUsersCount }} perangkat aktif yang menyetujui preferensi kategori terkait).</p>
                            </div>

                            <!-- Tujuan / Kategori & Aksi Ketika Di-klik -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="notif_type" value="Tujuan / Kategori Pesan" />
                                    <select
                                        id="notif_type"
                                        v-model="notifForm.type"
                                        @change="onNotifTypeChange"
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    >
                                        <option value="announcement">Pengumuman Perusahaan</option>
                                        <option value="approval">Approval Pengajuan / Dokumen</option>
                                        <option value="attendance">Pengingat & Informasi Absensi</option>
                                        <option value="cuti">Informasi & Jadwal Cuti</option>
                                        <option value="task">Penugasan & Operasional</option>
                                        <option value="ess">Layanan Mandiri ESS / Slip Gaji</option>
                                        <option value="custom">Pesan Khusus / Custom</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Menentukan ikon kategori dan menghormati preferensi notifikasi masing-masing karyawan.</p>
                                    <InputError class="mt-2" :message="notifForm.errors.type" />
                                </div>

                                <div>
                                    <InputLabel for="click_action" value="Aksi Saat Notifikasi Di-Klik" />
                                    <select
                                        id="click_action"
                                        v-model="notifForm.click_action"
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    >
                                        <option value="open_page">Buka Halaman Aplikasi Spesifik (Deep Link)</option>
                                        <option value="info_only">Hanya Notifikasi (Tampilkan Popup Pesan Tanpa Pindah Halaman)</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                        {{ notifForm.click_action === 'info_only'
                                            ? 'Cocok untuk pengumuman teks: saat di-tap hanya membuka detail isi pesan di kotak notifikasi.'
                                            : 'Saat di-tap langsung mengarahkan pengguna ke halaman modul ERP yang dipilih.' }}
                                    </p>
                                    <InputError class="mt-2" :message="notifForm.errors.click_action" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Title -->
                                <div>
                                    <InputLabel for="notif_title" value="Judul Pesan / Notifikasi" />
                                    <TextInput
                                        id="notif_title"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="notifForm.title"
                                        placeholder="Judul notifikasi atau pengumuman..."
                                    />
                                    <InputError class="mt-2" :message="notifForm.errors.title" />
                                </div>

                                <!-- Target Deep Link (hanya muncul bila open_page) -->
                                <div v-if="notifForm.click_action === 'open_page'">
                                    <InputLabel for="route_preset" value="Halaman Tujuan Saat Di-Klik" />
                                    <select
                                        id="route_preset"
                                        v-model="selectedRoutePreset"
                                        @change="onRoutePresetChange"
                                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    >
                                        <option v-for="r in routeCatalog" :key="r.value" :value="r.value">{{ r.label }}</option>
                                    </select>
                                    <div v-if="selectedRoutePreset === 'custom'" class="mt-2">
                                        <InputLabel for="notif_link" value="Rute Custom (/contoh)" class="sr-only" />
                                        <TextInput
                                            id="notif_link"
                                            type="text"
                                            class="block w-full text-sm"
                                            v-model="notifForm.link"
                                            placeholder="/approvals/detail?id=12&type=Pengajuan"
                                        />
                                    </div>
                                    <InputError class="mt-2" :message="notifForm.errors.link" />
                                </div>
                            </div>

                            <!-- Message -->
                            <div>
                                <InputLabel for="notif_message" value="Isi Pesan Lengkap" />
                                <textarea
                                    id="notif_message"
                                    rows="3"
                                    class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    v-model="notifForm.message"
                                    placeholder="Tuliskan isi pesan atau pengumuman untuk penerima..."
                                ></textarea>
                                <InputError class="mt-2" :message="notifForm.errors.message" />
                            </div>

                            <!-- Live Mobile Mockup Preview -->
                            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pratinjau Notifikasi di Handphone:</span>
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-300">
                                        {{ targetSummaryBadge }}
                                    </span>
                                </div>
                                <div class="bg-white dark:bg-gray-800 p-3.5 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex items-start space-x-3 max-w-lg">
                                    <div class="w-8 h-8 rounded-lg bg-red-600 flex items-center justify-center text-white shrink-0 font-bold text-xs">
                                        PSJ
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate">{{ notifForm.title || 'Judul Notifikasi' }}</span>
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400">Sekarang</span>
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5 line-clamp-2">{{ notifForm.message || 'Isi pesan notifikasi...' }}</p>
                                        <span class="inline-block mt-1 text-[10px] font-medium text-red-600 dark:text-red-400">
                                            {{ notifForm.click_action === 'info_only' ? 'Aksi klik: Hanya tampilkan detail pesan' : 'Aksi klik: Buka ' + (notifForm.link || '/dashboard') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 pt-2 border-t border-gray-200 dark:border-gray-700">
                                <button
                                    type="submit"
                                    :disabled="notifForm.processing"
                                    class="inline-flex items-center gap-2 min-h-[44px] px-5 py-2.5 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold text-sm rounded-lg shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 transition-colors disabled:opacity-50 cursor-pointer"
                                >
                                    <PaperAirplaneIcon class="h-4 w-4" aria-hidden="true" />
                                    <span>{{ notifForm.target_mode === 'blast_all' ? 'Kirim Blast ke Semua Pengguna' : 'Kirim Push Message Sekarang' }}</span>
                                </button>
                                <p v-if="notifForm.recentlySuccessful" class="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                                    Push message berhasil diproses.
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
