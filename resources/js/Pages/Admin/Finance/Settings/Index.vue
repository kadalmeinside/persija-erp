<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Toast from '@/Components/Toast.vue';

const props = defineProps({
    settings: Object,
    assetAccounts: Array,
    equityAccounts: Array,
    expenseAccounts: Array,
    programs: Array,
    pageTitle: String,
});

const page = usePage();
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const form = useForm({
    account_receivable_employee: props.settings.account_receivable_employee || '',
    account_equity_opening: props.settings.account_equity_opening || '',
    account_expense_rounding: props.settings.account_expense_rounding || '',
    account_equity_retained: props.settings.account_equity_retained || '',
    default_program_loans: props.settings.default_program_loans || '',
    reimburse_tolerance_limit: props.settings.reimburse_tolerance_limit || '1000000',
});

function submit() {
    form.post(route('admin.finance-settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: reset logic if needed
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

        <Toast :message="flashMessage" :type="flashType" />

        <div class="py-12 pt-4">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        
                        <!-- Employee Loan Settings -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pengaturan Pinjaman Karyawan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="account_receivable_employee" value="Akun Piutang Karyawan" />
                                    <p class="text-xs text-gray-500 mb-2">Akun aset yang digunakan untuk mencatat pinjaman karyawan.</p>
                                    
                                    <select
                                        id="account_receivable_employee"
                                        v-model="form.account_receivable_employee"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    >
                                        <option value="">-- Pilih Akun --</option>
                                        <option v-for="account in assetAccounts" :key="account.id" :value="account.id">
                                            {{ account.kode_akun }} - {{ account.nama_akun }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.account_receivable_employee" />
                                </div>
                                <div>
                                    <InputLabel for="default_program_loans" value="Program Kerja Default (Pengajuan Otomatis)" />
                                    <p class="text-xs text-gray-500 mb-2">Program kerja yang otomatis digunakan saat mem-posting Pengajuan Dana dari Pinjaman (Jurnal Kasbon).</p>
                                    
                                    <select
                                        id="default_program_loans"
                                        v-model="form.default_program_loans"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    >
                                        <option value="">-- Pilih Program --</option>
                                        <option v-for="program in programs" :key="program.id" :value="program.id">
                                            {{ program.nama_program }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.default_program_loans" />
                                </div>
                            </div>
                        </div>

                        <!-- Capital & Equity Settings -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pengaturan Modal & Ekuitas</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="account_equity_opening" value="Akun Modal Awal (Opening Balance)" />
                                    <p class="text-xs text-gray-500 mb-2">Akun penyeimbang untuk saldo awal kas/bank/aset (Default: 3-0000).</p>
                                    
                                    <select
                                        id="account_equity_opening"
                                        v-model="form.account_equity_opening"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    >
                                        <option value="">-- Pilih Akun --</option>
                                        <option v-for="account in equityAccounts" :key="account.id" :value="account.id">
                                            {{ account.kode_akun }} - {{ account.nama_akun }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.account_equity_opening" />
                                </div>
                                <div>
                                    <InputLabel for="account_equity_retained" value="Akun Laba Ditahan (Retained Earnings)" />
                                    <p class="text-xs text-gray-500 mb-2">Akun untuk menampung laba/rugi tahun tahun sebelumnya.</p>
                                    
                                    <select
                                        id="account_equity_retained"
                                        v-model="form.account_equity_retained"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    >
                                        <option value="">-- Pilih Akun --</option>
                                        <option v-for="account in equityAccounts" :key="account.id" :value="account.id">
                                            {{ account.kode_akun }} - {{ account.nama_akun }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.account_equity_retained" />
                                </div>
                            </div>
                        </div>

                        <!-- Other Settings -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Lainnya</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="account_expense_rounding" value="Akun Selisih Pembulatan" />
                                    <p class="text-xs text-gray-500 mb-2">Akun untuk menampung selisih desimal atau pembulatan (Rounding Error).</p>
                                    
                                    <select
                                        id="account_expense_rounding"
                                        v-model="form.account_expense_rounding"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    >
                                        <option value="">-- Pilih Akun --</option>
                                        <option v-for="account in expenseAccounts" :key="account.id" :value="account.id">
                                            {{ account.kode_akun }} - {{ account.nama_akun }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.account_expense_rounding" />
                                </div>
                                <div>
                                    <InputLabel for="reimburse_tolerance_limit" value="Batas Toleransi Nombok (Reimbursement)" />
                                    <p class="text-xs text-gray-500 mb-2">Jika kelebihan bayar laporan Uang Muka di bawah batas ini, tidak perlu re-approval. Jika di atas, otomatis buat pengajuan baru.</p>
                                    
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">Rp</span>
                                        </div>
                                        <input
                                            type="number"
                                            id="reimburse_tolerance_limit"
                                            v-model="form.reimburse_tolerance_limit"
                                            class="block w-full pl-10 pr-12 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                            placeholder="0"
                                        />
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.reimburse_tolerance_limit" />
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
