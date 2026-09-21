<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Toast from '@/Components/Toast.vue';

const props = defineProps({
    settings: Object,
    pageTitle: String,
    can: Object,
});

const page = usePage();
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const form = useForm({
    app_name: props.settings.app_name || '',
    app_logo: null,
    company_name: props.settings.company_name || '',
    company_address: props.settings.company_address || '',
    company_logo: null,
    reimburse_tolerance_limit: props.settings.reimburse_tolerance_limit || 1000000,
});

const logoPreview = ref(props.settings.app_logo ? `/storage/${props.settings.app_logo}` : null);
const companyLogoPreview = ref(props.settings.company_logo ? `/storage/${props.settings.company_logo}` : null);

function onLogoChange(event) {
    const file = event.target.files[0];
    if (!file) return;

    form.app_logo = file;
    logoPreview.value = URL.createObjectURL(file);
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
            document.getElementById('company_logo_input').value = '';
            form.app_logo = null;
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

        <Toast :message="flashMessage" :type="flashType" />

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
                                        <input id="app_logo_input" type="file" @input="onLogoChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100"/>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.app_logo" />
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
