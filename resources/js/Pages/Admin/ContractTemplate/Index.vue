<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import ContractWysiwygEditor from '@/Components/ContractWysiwygEditor.vue';
import { 
    DocumentTextIcon, ArrowPathIcon, CheckCircleIcon, 
    InformationCircleIcon, TagIcon, DocumentDuplicateIcon 
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    templates: Array,
    availableTags: Array,
});

const toast = useToast();
const selectedTemplateId = ref(props.templates[0]?.id || null);

const activeTemplate = computed(() => {
    return props.templates.find(t => t.id === selectedTemplateId.value) || props.templates[0];
});

const form = useForm({
    nama_template: '',
    judul_surat: '',
    konten: '',
    is_active: true,
});

const initForm = (tmpl) => {
    if (!tmpl) return;
    form.nama_template = tmpl.nama_template;
    form.judul_surat = tmpl.judul_surat;
    form.konten = tmpl.konten;
    form.is_active = !!tmpl.is_active;
    form.clearErrors();
};

// Initialize with first template
if (activeTemplate.value) {
    initForm(activeTemplate.value);
}

const selectTemplate = (tmpl) => {
    selectedTemplateId.value = tmpl.id;
    initForm(tmpl);
};

const insertTag = (tag) => {
    form.konten += tag;
};

const copyTag = (tag) => {
    navigator.clipboard.writeText(tag);
    toast.success(`Tag ${tag} disalin ke clipboard`);
};

const submitUpdate = () => {
    form.put(route('admin.contract-templates.update', selectedTemplateId.value), {
        preserveScroll: true
    });
};

const handleResetDefault = () => {
    Swal.fire({
        title: 'Reset ke Standar Resmi?',
        text: 'Template ini akan dikembalikan ke draf hukum resmi default (sesuai UU No. 13/2003 & PP No. 35/2021). Perubahan kustom Anda akan ditimpa.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Reset ke Default',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.contract-templates.reset', selectedTemplateId.value), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    // Update active form with latest template
                    const refreshed = props.templates.find(t => t.id === selectedTemplateId.value);
                    if (refreshed) initForm(refreshed);
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Manajemen Template Kontrak" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <DocumentTextIcon class="w-7 h-7 mr-2.5 text-indigo-600 dark:text-indigo-400" />
                        Manajemen Master Template Kontrak Kerja
                    </h1>
                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Sesuaikan redaksi pasal, format pembukaan, dan klausul hukum perjanjian kerja secara dinamis tanpa perlu mengubah kode sumber.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- 1. SELECT TEMPLATE PILLS / TABS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700 p-4 shadow-2xs">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Pilih Jenis Kontrak yang Ingin Diedit:</p>
                <div class="flex flex-wrap gap-2.5">
                    <button
                        v-for="tmpl in templates"
                        :key="tmpl.id"
                        type="button"
                        @click="selectTemplate(tmpl)"
                        :class="[
                            selectedTemplateId === tmpl.id
                                ? 'bg-indigo-600 text-white shadow-xs font-bold'
                                : 'bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 hover:bg-gray-200 font-medium',
                            'px-4 py-2 rounded-xl text-xs md:text-sm transition-all flex items-center'
                        ]"
                    >
                        <DocumentTextIcon class="w-4 h-4 mr-2" />
                        {{ tmpl.contract_type }} - {{ tmpl.nama_template }}
                    </button>
                </div>
            </div>

            <!-- 2. MAIN WORKSPACE: EDITOR & PLACEHOLDER CHEAT SHEET -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- LEFT: CONTRACT TEMPLATE FORM (lg:col-span-8) -->
                <div class="lg:col-span-8 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700 p-5 md:p-6 shadow-2xs space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <h2 class="text-base md:text-lg font-bold text-gray-900 dark:text-white flex items-center">
                                Editor Template Kontrak: <span class="ml-2 text-indigo-600 dark:text-indigo-400">{{ activeTemplate?.contract_type }}</span>
                            </h2>
                            <p class="text-xs text-gray-500 mt-0.5">Semua tag berformat <code>{NAMA_TAG}</code> akan otomatis diisi dengan data karyawan saat digenerate.</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <SecondaryButton @click="handleResetDefault" type="button" class="text-xs text-rose-600 hover:text-rose-700">
                                <ArrowPathIcon class="w-3.5 h-3.5 mr-1" />
                                Reset Default UU
                            </SecondaryButton>
                        </div>
                    </div>

                    <form @submit.prevent="submitUpdate" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="nama_template" value="Nama Template" />
                                <TextInput
                                    id="nama_template"
                                    type="text"
                                    class="mt-1 block w-full text-xs"
                                    v-model="form.nama_template"
                                    required
                                />
                                <InputError class="mt-1" :message="form.errors.nama_template" />
                            </div>

                            <div>
                                <InputLabel for="judul_surat" value="Judul Dokumen Surat" />
                                <TextInput
                                    id="judul_surat"
                                    type="text"
                                    class="mt-1 block w-full text-xs font-mono"
                                    v-model="form.judul_surat"
                                    required
                                />
                                <InputError class="mt-1" :message="form.errors.judul_surat" />
                            </div>
                        </div>

                        <!-- WYSIWYG Editor for Contract Master Template -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <InputLabel value="Isi Pasal-Pasal & Format Kontrak (WYSIWYG Rich Text)" />
                                <span class="text-[11px] text-gray-400">Gunakan toolbar atau klik tag di samping untuk menyisipkan variabel</span>
                            </div>

                            <ContractWysiwygEditor
                                v-model="form.konten"
                                :availableTags="availableTags"
                                minHeight="460px"
                            />
                            <InputError class="mt-1" :message="form.errors.konten" />
                        </div>

                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.is_active"
                                    class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-2xs focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-xs font-medium text-gray-700 dark:text-gray-300">Aktifkan Template ini</span>
                            </label>

                            <PrimaryButton :disabled="form.processing" class="text-xs">
                                <CheckCircleIcon class="w-4 h-4 mr-1.5" />
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan Template' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- RIGHT: AVAILABLE TAGS CHEAT SHEET (lg:col-span-4) -->
                <div class="lg:col-span-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700 p-5 shadow-2xs space-y-4">
                    <div class="flex items-center space-x-2 pb-3 border-b border-gray-100 dark:border-gray-700">
                        <TagIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Variabel Dinamis ({TAG})</h3>
                            <p class="text-[11px] text-gray-400">Klik tag untuk menyalin langsung ke template.</p>
                        </div>
                    </div>

                    <div class="max-h-[620px] overflow-y-auto pr-1 space-y-2">
                        <div
                            v-for="item in availableTags"
                            :key="item.tag"
                            @click="copyTag(item.tag)"
                            class="p-2.5 bg-gray-50 dark:bg-gray-900/40 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 border border-gray-200/70 dark:border-gray-700 rounded-xl cursor-pointer transition-colors group flex items-start justify-between gap-2"
                            title="Klik untuk menyalin tag"
                        >
                            <div>
                                <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400 group-hover:underline">
                                    {{ item.tag }}
                                </span>
                                <p class="text-[11px] text-gray-600 dark:text-gray-300 mt-0.5 leading-snug">
                                    {{ item.label }}
                                </p>
                            </div>
                            <DocumentDuplicateIcon class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-600 shrink-0 mt-0.5" />
                        </div>
                    </div>

                    <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-xl text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed flex items-start space-x-2">
                        <InformationCircleIcon class="w-4 h-4 shrink-0 mt-0.5 text-amber-600" />
                        <span>Kop surat, logo, dan kotak tanda tangan materai digenerate otomatis di luar konten pasal di atas agar selalu rapi dan konsisten.</span>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
