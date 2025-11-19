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
    app_logo: null, // Input file akan mengisi ini
});

const logoPreview = ref(props.settings.app_logo ? `/storage/${props.settings.app_logo}` : null);

function onLogoChange(event) {
    const file = event.target.files[0];
    if (!file) return;

    form.app_logo = file;
    logoPreview.value = URL.createObjectURL(file);
}

function submit() {
    form.post(route('admin.settings.update'), {
        forceFormData: true, // Penting untuk upload file
        onSuccess: () => {
            // Reset input file setelah sukses
            document.getElementById('app_logo_input').value = '';
            form.app_logo = null;
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
