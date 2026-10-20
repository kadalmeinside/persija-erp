<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import ContractWysiwygEditor from '@/Components/ContractWysiwygEditor.vue';
import { 
    DocumentTextIcon, ArrowPathIcon, CheckCircleIcon, 
    InformationCircleIcon, TagIcon, DocumentDuplicateIcon,
    EyeIcon, PrinterIcon, MagnifyingGlassIcon, SparklesIcon,
    PlusIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    templates: Array,
    availableTags: Array,
});

const toast = useToast();
const selectedTemplateId = ref(props.templates[0]?.id || null);
const showPreviewModal = ref(false);
const searchTag = ref('');

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

const getTypeBadge = (type) => {
    switch (type) {
        case 'PKWT':
            return {
                badge: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                activeBg: 'bg-blue-600 text-white shadow-sm ring-2 ring-blue-400/40',
                label: 'Waktu Tertentu (Kontrak)',
            };
        case 'PKWTT':
            return {
                badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                activeBg: 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-400/40',
                label: 'Karyawan Tetap (Permanen)',
            };
        case 'Professional':
            return {
                badge: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border-purple-200 dark:border-purple-800',
                activeBg: 'bg-purple-600 text-white shadow-sm ring-2 ring-purple-400/40',
                label: 'Tenaga Ahli / Ofisial',
            };
        case 'Freelance':
            return {
                badge: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                activeBg: 'bg-amber-600 text-white shadow-sm ring-2 ring-amber-400/40',
                label: 'Harian Lepas / Project',
            };
        case 'Internship':
            return {
                badge: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800',
                activeBg: 'bg-cyan-600 text-white shadow-sm ring-2 ring-cyan-400/40',
                label: 'Program Magang',
            };
        default:
            return {
                badge: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700',
                activeBg: 'bg-gray-700 text-white shadow-sm',
                label: 'Kontrak Kerja',
            };
    }
};

const filteredTags = computed(() => {
    if (!searchTag.value.trim()) return props.availableTags;
    const q = searchTag.value.toLowerCase();
    return props.availableTags.filter(t => 
        t.tag.toLowerCase().includes(q) || t.label.toLowerCase().includes(q)
    );
});

const copyTag = (tag) => {
    navigator.clipboard.writeText(tag);
    toast.success(`Tag ${tag} disalin ke clipboard`);
};

const insertTag = (tag) => {
    form.konten = (form.konten || '') + ` <strong>${tag}</strong> `;
    toast.info(`Tag ${tag} disisipkan ke draf`);
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
                    const refreshed = props.templates.find(t => t.id === selectedTemplateId.value);
                    if (refreshed) initForm(refreshed);
                }
            });
        }
    });
};

// ── Sample Data Mock for Live Preview ──────────────────────────────────────
const sampleData = {
    '{COMPANY_NAME}': 'PT PERSIJA JAYA JAKARTA',
    '{COMPANY_ADDRESS}': 'Rasuna Office Park Unit GO-05, Komplek Rasuna Epicentrum, Jl. H.R. Rasuna Said, Jakarta Selatan 12960',
    '{NOMOR_KONTRAK}': '025/HRD-PJH/PKWT/X/2026',
    '{NAMA_KARYAWAN}': 'Bambang Pamungkas',
    '{NIK_KARYAWAN}': 'PJH-2026-089',
    '{TEMPAT_LAHIR}': 'Semarang',
    '{TANGGAL_LAHIR}': '10 Juni 1990',
    '{JENIS_KELAMIN}': 'Laki-laki',
    '{ALAMAT_KARYAWAN}': 'Jl. Senopati Raya No. 20, Kebayoran Baru, Jakarta Selatan',
    '{JABATAN}': 'Senior Performance Analyst',
    '{DEPARTEMEN}': 'Tim Utama & Kepelatihan',
    '{LOKASI_KANTOR}': 'Training Ground Bojongsari / Kantor Pusat',
    '{TANGGAL_BERGABUNG}': '01 November 2026',
    '{TANGGAL_BERAKHIR}': '31 Oktober 2027',
    '{DURASI_PROBATION}': '3',
    '{TANGGAL_SELESAI_PROBATION}': '31 Januari 2027',
    '{JAM_KERJA_MINGGUAN}': '40',
    '{NOTICE_PERIOD}': '30',
    '{GAJI_POKOK}': 'Rp 15.000.000',
    '{TUNJANGAN_RUTIN}': '<ul><li>Tunjangan Transportasi & Konsumsi: Rp 2.500.000 per bulan</li><li>Tunjangan Komunikasi: Rp 500.000 per bulan</li></ul>',
    '{REKENING_BANK}': 'Bank BCA | No. Rek: 1234567890 | a.n: Bambang Pamungkas',
    '{STATUS_PTKP}': 'K/1',
    '{NO_BPJS_KETENAGAKERJAAN}': '000192837465',
    '{NO_BPJS_KESEHATAN}': '000987654321',
    '{WORKING_SCOPE}': '<ul><li>Menganalisis data performa fisik dan taktik tim pada setiap sesi latihan dan pertandingan resmi.</li><li>Menyusun laporan statistik evaluasi mingguan untuk disampaikan kepada Head Coach.</li><li>Berkoordinasi dengan staf medis dan fisioterapis terkait tingkat kebugaran atlet.</li></ul>',
    '{TANGGAL_HARI_INI}': new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
};

const renderedPreviewHtml = computed(() => {
    let content = form.konten || '<p class="text-gray-400 italic text-center">Belum ada konten pasal.</p>';
    for (const [tag, val] of Object.entries(sampleData)) {
        content = content.split(tag).join(val);
    }
    return content;
});

const printPreview = () => {
    window.print();
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
                        Kelola dan sesuaikan redaksi pasal, format pembukaan, dan klausul hukum perjanjian kerja secara dinamis.
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="showPreviewModal = true"
                        class="inline-flex items-center px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 rounded-xl text-xs font-bold transition-all shadow-2xs"
                    >
                        <EyeIcon class="w-4 h-4 mr-1.5" />
                        Pratinjau Draf Sampel
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- 1. SELECT TEMPLATE PILLS / TABS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/80 dark:border-gray-700 p-4 shadow-2xs">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pilih Jenis Kontrak yang Ingin Dikelola:</p>
                    <span class="text-[11px] font-medium text-gray-400">{{ templates.length }} Tipe Kontrak Terdaftar</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5">
                    <button
                        v-for="tmpl in templates"
                        :key="tmpl.id"
                        type="button"
                        @click="selectTemplate(tmpl)"
                        :class="[
                            selectedTemplateId === tmpl.id
                                ? getTypeBadge(tmpl.contract_type).activeBg
                                : 'bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200/70 dark:border-gray-700',
                            'p-3 rounded-xl text-left transition-all flex flex-col justify-between group'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-1.5">
                            <span 
                                :class="[
                                    selectedTemplateId === tmpl.id ? 'bg-white/20 text-white' : getTypeBadge(tmpl.contract_type).badge,
                                    'px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider border'
                                ]"
                            >
                                {{ tmpl.contract_type }}
                            </span>
                            <span 
                                class="w-2 h-2 rounded-full shrink-0" 
                                :class="tmpl.is_active ? 'bg-emerald-400 ring-2 ring-emerald-400/30' : 'bg-gray-400'"
                                :title="tmpl.is_active ? 'Template Aktif' : 'Template Nonaktif'"
                            ></span>
                        </div>
                        <p class="text-xs font-bold truncate group-hover:text-current">
                            {{ tmpl.nama_template }}
                        </p>
                        <p 
                            class="text-[10px] mt-1"
                            :class="selectedTemplateId === tmpl.id ? 'text-white/80' : 'text-gray-400'"
                        >
                            {{ getTypeBadge(tmpl.contract_type).label }}
                        </p>
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
                                Editor Template: 
                                <span class="ml-2 px-2.5 py-0.5 rounded-lg text-xs font-extrabold uppercase border" :class="getTypeBadge(activeTemplate?.contract_type).badge">
                                    {{ activeTemplate?.contract_type }}
                                </span>
                            </h2>
                            <p class="text-xs text-gray-500 mt-0.5">Semua tag berformat <code>{NAMA_TAG}</code> akan otomatis diisi dengan data karyawan saat digenerate.</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                type="button"
                                @click="showPreviewModal = true"
                                class="inline-flex items-center px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold transition-colors"
                            >
                                <EyeIcon class="w-3.5 h-3.5 mr-1 text-indigo-500" />
                                Pratinjau
                            </button>
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
                                <span class="ml-2 text-xs font-medium text-gray-700 dark:text-gray-300">Aktifkan Template ini untuk Digunakan HR</span>
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
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center space-x-2">
                            <TagIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Variabel Dinamis ({TAG})</h3>
                                <p class="text-[11px] text-gray-400">Variabel pengganti otomatis saat digenerate.</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                            {{ filteredTags.length }} Tag
                        </span>
                    </div>

                    <!-- Search Input for Tags -->
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
                        <input
                            type="text"
                            v-model="searchTag"
                            placeholder="Cari variabel (cth: gaji, nama, bank)..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-1 focus:ring-indigo-500 text-gray-800 dark:text-gray-200"
                        />
                    </div>

                    <div class="max-h-[580px] overflow-y-auto pr-1 space-y-2">
                        <div
                            v-for="item in filteredTags"
                            :key="item.tag"
                            class="p-2.5 bg-gray-50 dark:bg-gray-900/40 hover:bg-indigo-50/70 dark:hover:bg-indigo-950/40 border border-gray-200/70 dark:border-gray-700 rounded-xl transition-colors group flex items-start justify-between gap-2"
                        >
                            <div class="flex-1 cursor-pointer" @click="copyTag(item.tag)" title="Klik untuk menyalin tag">
                                <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400 group-hover:underline">
                                    {{ item.tag }}
                                </span>
                                <p class="text-[11px] text-gray-600 dark:text-gray-300 mt-0.5 leading-snug">
                                    {{ item.label }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-1 shrink-0 mt-0.5">
                                <button
                                    type="button"
                                    @click="insertTag(item.tag)"
                                    class="p-1 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 rounded-md transition-colors"
                                    title="Sisipkan ke draf"
                                >
                                    <PlusIcon class="w-3.5 h-3.5" />
                                </button>
                                <button
                                    type="button"
                                    @click="copyTag(item.tag)"
                                    class="p-1 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 hover:text-indigo-600 rounded-md transition-colors"
                                    title="Salin tag"
                                >
                                    <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <div v-if="filteredTags.length === 0" class="text-center py-6 text-gray-400 text-xs italic">
                            Tidak ditemukan variabel yang cocok dengan "{{ searchTag }}".
                        </div>
                    </div>

                    <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-xl text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed flex items-start space-x-2">
                        <InformationCircleIcon class="w-4 h-4 shrink-0 mt-0.5 text-amber-600" />
                        <span>Kop surat, logo resmi, dan kotak tanda tangan materai digenerate otomatis di luar konten pasal di atas agar selalu rapi dan konsisten.</span>
                    </div>
                </div>

            </div>

        </div>

        <!-- ═════════════════════════════════════════════════════════════════ -->
        <!-- LIVE SAMPLE PREVIEW MODAL                                         -->
        <!-- ═════════════════════════════════════════════════════════════════ -->
        <Modal :show="showPreviewModal" @close="showPreviewModal = false" maxWidth="4xl">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <SparklesIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                Pratinjau Tampilan Dokumen Kontrak Resmi
                            </h3>
                            <p class="text-xs text-gray-500">
                                Simulasi data nyata karyawan terhadap template <span class="font-bold text-indigo-600">{{ activeTemplate?.nama_template }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button
                            type="button"
                            @click="printPreview"
                            class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold transition-colors"
                        >
                            <PrinterIcon class="w-4 h-4 mr-1 text-gray-500" />
                            Cetak
                        </button>
                        <SecondaryButton @click="showPreviewModal = false" class="text-xs">
                            Tutup
                        </SecondaryButton>
                    </div>
                </div>

                <!-- A4 Simulated Paper Container -->
                <div class="bg-gray-100 dark:bg-gray-900 p-4 md:p-6 rounded-xl max-h-[72vh] overflow-y-auto">
                    <div class="bg-white text-gray-900 max-w-[800px] mx-auto p-8 md:p-12 shadow-md rounded-lg text-xs leading-relaxed font-sans border border-gray-200">
                        
                        <!-- Kop Surat Resmi -->
                        <div class="flex items-center justify-between border-b-2 border-gray-900 pb-3 mb-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-14 h-14 bg-red-600 text-white font-black text-xl rounded-lg flex items-center justify-center shadow-xs">
                                    PJH
                                </div>
                                <div>
                                    <h4 class="font-black text-sm md:text-base uppercase tracking-wider text-gray-900">
                                        PT PERSIJA JAYA JAKARTA
                                    </h4>
                                    <p class="text-[10px] text-gray-600 font-medium leading-tight mt-0.5">
                                        Rasuna Office Park Unit GO-05, Komplek Rasuna Epicentrum, Jl. H.R. Rasuna Said, Jakarta Selatan 12960<br>
                                        Telp: (021) 2994-1234 | Email: hrd@persija.id | Web: https://persija.id
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-2.5 py-1 bg-gray-100 border border-gray-900 font-mono text-[10px] font-bold uppercase tracking-wider">
                                    {{ activeTemplate?.contract_type }}
                                </span>
                            </div>
                        </div>

                        <!-- Dokumen Title -->
                        <div class="text-center my-6">
                            <h2 class="text-sm md:text-base font-extrabold uppercase underline tracking-wider text-gray-900">
                                {{ form.judul_surat }}
                            </h2>
                            <p class="font-mono text-xs text-gray-700 font-semibold mt-1">
                                Nomor: 025/HRD-PJH/{{ activeTemplate?.contract_type }}/X/2026
                            </p>
                        </div>

                        <!-- Rendered HTML Content with Sample Replacements -->
                        <div class="prose prose-xs max-w-none text-gray-800 space-y-3" v-html="renderedPreviewHtml"></div>

                        <!-- Kotak Tanda Tangan Para Pihak (Signature Blocks) -->
                        <div class="mt-12 pt-6 border-t border-gray-200 grid grid-cols-2 gap-8 text-center text-xs">
                            <div>
                                <p class="font-bold text-gray-800">PIHAK PERTAMA</p>
                                <p class="text-[10px] text-gray-500">PT Persija Jaya Jakarta</p>
                                <div class="h-20 flex items-center justify-center">
                                    <span class="text-[10px] text-gray-300 italic">[ Tanda Tangan & Stempel Resmi ]</span>
                                </div>
                                <p class="font-bold underline text-gray-900">MOHAMAD PRAPANCA</p>
                                <p class="text-[10px] text-gray-600">Direktur Utama</p>
                            </div>
                            <div>
                                <p class="font-bold text-gray-800">PIHAK KEDUA</p>
                                <p class="text-[10px] text-gray-500">Pekerja / Karyawan</p>
                                <div class="h-20 flex items-center justify-center">
                                    <div class="border border-dashed border-gray-300 rounded px-2 py-1 text-[9px] text-gray-400">
                                        Materai Rp 10.000
                                    </div>
                                </div>
                                <p class="font-bold underline text-gray-900">BAMBANG PAMUNGKAS</p>
                                <p class="text-[10px] text-gray-600">Pekerja</p>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-gray-100 dark:border-gray-700">
                    <PrimaryButton @click="showPreviewModal = false" class="text-xs">
                        Tutup Pratinjau
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
