<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ContractWysiwygEditor from '@/Components/ContractWysiwygEditor.vue';
import { 
    PlusIcon, TrashIcon, DocumentTextIcon, ClipboardDocumentListIcon, 
    PencilSquareIcon, ArrowPathIcon, DocumentDuplicateIcon, ClockIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    employee: Object,
    lokasiKantors: Array,
});

const emit = defineEmits(['close', 'saved']);

const activeSubTab = ref('clauses'); // 'clauses', 'scope', 'content'

const form = useForm({
    nomor_kontrak: '',
    contract_type: 'PKWT',
    durasi_probation_bulan: 0,
    tanggal_selesai_probation: '',
    notice_period_hari: 30,
    jam_kerja_mingguan: 40,
    id_lokasi_kantor: '',
    is_strict_location: true,
    working_scope: [],
    custom_contract_content: '',
    is_new_version: false,
    catatan_perubahan: '',
});

const newScopeItem = ref('');
const isLoadingTemplate = ref(false);

watch(() => props.show, (newVal) => {
    if (newVal && props.employee) {
        form.nomor_kontrak = props.employee.nomor_kontrak || '';
        form.contract_type = props.employee.contract_type || 'PKWT';
        form.durasi_probation_bulan = props.employee.contract_type === 'PKWT' ? 0 : (props.employee.durasi_probation_bulan ?? 3);
        form.tanggal_selesai_probation = props.employee.contract_type === 'PKWT' ? '' : (props.employee.tanggal_selesai_probation || '');
        form.notice_period_hari = props.employee.notice_period_hari ?? 30;
        form.jam_kerja_mingguan = props.employee.jam_kerja_mingguan ?? 40;
        form.id_lokasi_kantor = props.employee.id_lokasi_kantor || '';
        form.is_strict_location = props.employee.is_strict_location == 1;
        form.custom_contract_content = props.employee.custom_contract_content || '';
        form.is_new_version = false;
        form.catatan_perubahan = '';

        if (Array.isArray(props.employee.working_scope) && props.employee.working_scope.length > 0) {
            form.working_scope = [...props.employee.working_scope];
        } else {
            form.working_scope = [
                `Menjalankan tugas dan tanggung jawab profesional sesuai jabatan ${props.employee.jabatan || 'karyawan'}.`,
                `Berkoordinasi secara aktif dengan tim kerja di bawah naungan ${props.employee.departemen?.nama_departemen || 'departemen'}.`,
                'Mematuhi SOP operasional, kode etik, dan peraturan internal PT Persija Jaya Jakarta.',
                'Melaporkan perkembangan tugas dan performa kerja secara berkala kepada atasan langsung.',
                'Mengelola kerahasiaan data serta aset perusahaan dengan penuh integritas dan tanggung jawab.'
            ];
        }

        form.clearErrors();
        activeSubTab.value = 'clauses';
    }
});

const addScopeItem = () => {
    if (newScopeItem.value.trim()) {
        form.working_scope.push(newScopeItem.value.trim());
        newScopeItem.value = '';
    }
};

const removeScopeItem = (index) => {
    form.working_scope.splice(index, 1);
};

const loadMasterTemplate = async () => {
    if (!props.employee) return;
    isLoadingTemplate.value = true;
    try {
        const response = await fetch(route('admin.karyawan.contract-preview', props.employee.id));
        const data = await response.json();
        form.custom_contract_content = data.custom_content || data.master_content || '';
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Teks template master berhasil dimuat',
            showConfirmButton: false,
            timer: 1500
        });
    } catch (err) {
        console.error(err);
    } finally {
        isLoadingTemplate.value = false;
    }
};

const clearCustomContent = () => {
    form.custom_contract_content = '';
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Kembali menggunakan Template Master standar',
        showConfirmButton: false,
        timer: 1500
    });
};

const submit = () => {
    form.post(route('admin.karyawan.contract-details.update', props.employee.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
            Swal.fire({
                icon: 'success',
                title: 'Tersimpan',
                text: 'Klausul kontrak, lingkup kerja & draf isi kontrak berhasil diperbarui',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};

const close = () => {
    emit('close');
    form.reset();
};
</script>

<template>
    <Modal :show="show" maxWidth="3xl" @close="close">
        <form @submit.prevent="submit" class="p-6">
            <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700 mb-5">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center">
                        <DocumentTextIcon class="w-5 h-5 mr-2 text-indigo-500" />
                        Pengaturan Klausul & Generator Kontrak
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ employee?.nama_lengkap }} ({{ employee?.jabatan }})</p>
                </div>
            </div>

            <!-- Sub Tabs -->
            <div class="flex space-x-2 border-b border-gray-100 dark:border-gray-700/60 mb-5 overflow-x-auto">
                <button
                    type="button"
                    @click="activeSubTab = 'clauses'"
                    :class="[
                        activeSubTab === 'clauses'
                            ? 'border-indigo-600 text-indigo-600 font-bold border-b-2'
                            : 'text-gray-500 hover:text-gray-700',
                        'px-4 py-2 text-xs flex items-center transition-colors shrink-0'
                    ]"
                >
                    <DocumentTextIcon class="w-4 h-4 mr-1.5" />
                    Klausul & Parameter
                </button>
                <button
                    type="button"
                    @click="activeSubTab = 'scope'"
                    :class="[
                        activeSubTab === 'scope'
                            ? 'border-indigo-600 text-indigo-600 font-bold border-b-2'
                            : 'text-gray-500 hover:text-gray-700',
                        'px-4 py-2 text-xs flex items-center transition-colors shrink-0'
                    ]"
                >
                    <ClipboardDocumentListIcon class="w-4 h-4 mr-1.5" />
                    Working Scope ({{ form.working_scope.length }})
                </button>
                <button
                    type="button"
                    @click="activeSubTab = 'content'"
                    :class="[
                        activeSubTab === 'content'
                            ? 'border-indigo-600 text-indigo-600 font-bold border-b-2'
                            : 'text-gray-500 hover:text-gray-700',
                        'px-4 py-2 text-xs flex items-center transition-colors shrink-0'
                    ]"
                >
                    <PencilSquareIcon class="w-4 h-4 mr-1.5" />
                    Kustomisasi Isi Kontrak
                    <span v-if="form.custom_contract_content" class="ml-1.5 px-1.5 py-0.5 text-[9px] rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 font-bold">
                        Custom
                    </span>
                </button>
            </div>

            <!-- SubTab 1: Clauses & Policy -->
            <div v-show="activeSubTab === 'clauses'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="nomor_kontrak" value="Nomor Kontrak Resmi" />
                        <TextInput
                            id="nomor_kontrak"
                            type="text"
                            class="mt-1 block w-full text-xs"
                            placeholder="Contoh: 024/PKWT-HRD/PJ/X/2026"
                            v-model="form.nomor_kontrak"
                        />
                        <InputError class="mt-1" :message="form.errors.nomor_kontrak" />
                    </div>

                    <div>
                        <InputLabel for="contract_type" value="Tipe / Jenis Kontrak" />
                        <select
                            id="contract_type"
                            v-model="form.contract_type"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-xs text-xs"
                            required
                        >
                            <option value="PKWT">PKWT (Perjanjian Kerja Waktu Tertentu)</option>
                            <option value="PKWTT">PKWTT (Karyawan Tetap)</option>
                            <option value="Professional">Professional Contract (Pelatih / Ofisial)</option>
                            <option value="Freelance">Freelance / Harian Lepas</option>
                            <option value="Internship">Internship / Magang</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.contract_type" />
                    </div>

                    <div>
                        <InputLabel 
                            for="durasi_probation_bulan" 
                            :value="form.contract_type === 'PKWT' ? 'Periode Evaluasi Kinerja Berkala (Bulan)' : 'Masa Percobaan / Probation (Bulan)'" 
                        />
                        <TextInput
                            id="durasi_probation_bulan"
                            type="number"
                            min="0"
                            max="12"
                            class="mt-1 block w-full text-xs"
                            v-model="form.durasi_probation_bulan"
                        />
                        <p v-if="form.contract_type === 'PKWT'" class="text-[10px] text-amber-600 dark:text-amber-400 mt-1">
                            *UU 13/2003 Ps. 58: PKWT tidak mensyaratkan masa percobaan; difungsikan sebagai evaluasi kinerja berkala.
                        </p>
                        <p v-else-if="form.contract_type === 'PKWTT'" class="text-[10px] text-gray-500 mt-1">
                            *UU 13/2003 Ps. 60: Masa percobaan kerja untuk PKWTT paling lama 3 bulan.
                        </p>
                        <InputError class="mt-1" :message="form.errors.durasi_probation_bulan" />
                    </div>

                    <div>
                        <InputLabel for="notice_period_hari" value="Notice Period Resign (Hari)" />
                        <TextInput
                            id="notice_period_hari"
                            type="number"
                            min="0"
                            max="180"
                            class="mt-1 block w-full text-xs"
                            v-model="form.notice_period_hari"
                        />
                        <InputError class="mt-1" :message="form.errors.notice_period_hari" />
                    </div>

                    <div>
                        <InputLabel for="jam_kerja_mingguan" value="Jam Kerja per Minggu" />
                        <TextInput
                            id="jam_kerja_mingguan"
                            type="number"
                            min="1"
                            max="80"
                            class="mt-1 block w-full text-xs"
                            v-model="form.jam_kerja_mingguan"
                        />
                        <InputError class="mt-1" :message="form.errors.jam_kerja_mingguan" />
                    </div>

                    <div>
                        <InputLabel for="id_lokasi_kantor" value="Lokasi Kantor Utama" />
                        <select
                            id="id_lokasi_kantor"
                            v-model="form.id_lokasi_kantor"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-xs text-xs"
                        >
                            <option value="">Kantor Pusat / Bebas (Default)</option>
                            <option v-for="lok in lokasiKantors" :key="lok.id" :value="lok.id">
                                {{ lok.nama_kantor }}
                            </option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.id_lokasi_kantor" />
                    </div>

                    <div class="md:col-span-2 pt-2">
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_strict_location"
                                class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-2xs focus:ring-indigo-500"
                            />
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Strict Location Attendance (Wajib presensi di dalam radius koordinat kantor)
                            </span>
                        </label>
                        <p class="text-[11px] text-gray-400 mt-1 pl-6">
                            Jika dinonaktifkan, karyawan memiliki izin roaming presensi (misal: ofisial, fisioterapis, atau media yang bertugas tandang/lapangan).
                        </p>
                    </div>
                </div>
            </div>

            <!-- SubTab 2: Working Scope -->
            <div v-show="activeSubTab === 'scope'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Daftar Lingkup Tugas (Tupoksi Jabatan)</p>
                        <p class="text-[11px] text-gray-400">Butir tugas ini akan dimasukkan ke Pasal 3 Surat Perjanjian Kerja.</p>
                    </div>
                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                        Total: {{ form.working_scope.length }} Butir
                    </span>
                </div>

                <!-- Scope List -->
                <div class="space-y-2.5 max-h-[320px] overflow-y-auto pr-1">
                    <div 
                        v-for="(item, idx) in form.working_scope" 
                        :key="idx" 
                        class="flex items-start space-x-2 p-2.5 bg-gray-50/70 dark:bg-gray-900/40 border border-gray-200/80 dark:border-gray-700/80 rounded-xl"
                    >
                        <span class="w-5 h-5 flex items-center justify-center bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold rounded-full shrink-0 mt-1">
                            {{ idx + 1 }}
                        </span>
                        <div class="flex-1">
                            <textarea 
                                v-model="form.working_scope[idx]" 
                                rows="2" 
                                class="w-full text-xs border-0 bg-transparent p-0 focus:ring-0 text-gray-800 dark:text-gray-200 leading-relaxed resize-none"
                            ></textarea>
                        </div>
                        <button 
                            type="button" 
                            @click="removeScopeItem(idx)" 
                            class="text-gray-400 hover:text-red-500 p-1 rounded transition-colors"
                            title="Hapus butir tupoksi"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Add New Item -->
                <div class="flex items-center space-x-2 pt-2">
                    <input 
                        type="text" 
                        v-model="newScopeItem" 
                        placeholder="Ketik butir tanggung jawab kerja baru..." 
                        class="flex-1 text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-lg shadow-2xs focus:ring-indigo-500"
                        @keydown.enter.prevent="addScopeItem"
                    />
                    <button 
                        type="button" 
                        @click="addScopeItem" 
                        class="px-3 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-lg text-xs font-semibold flex items-center"
                    >
                        <PlusIcon class="w-4 h-4 mr-1" /> Tambah
                    </button>
                </div>
            </div>

            <!-- SubTab 3: Custom Contract Content (Editor Pasal-Pasal Dinamis) -->
            <div v-show="activeSubTab === 'content'" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 bg-gray-50 dark:bg-gray-900/40 border border-gray-200/80 dark:border-gray-700 rounded-xl">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span 
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                                :class="[
                                    form.custom_contract_content
                                        ? 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800'
                                        : 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'
                                ]"
                            >
                                {{ form.custom_contract_content ? 'Kustom Khusus Karyawan Ini' : `Mengikuti Template Master (${form.contract_type})` }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            Anda dapat mengedit kalimat pasal secara spesifik khusus karyawan ini, atau membiarkannya kosong agar selalu mengikuti template standar perusahaan.
                        </p>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0">
                        <button
                            type="button"
                            @click="loadMasterTemplate"
                            :disabled="isLoadingTemplate"
                            class="inline-flex items-center px-2.5 py-1.5 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold transition-colors"
                        >
                            <ArrowPathIcon class="w-3.5 h-3.5 mr-1" />
                            {{ form.custom_contract_content ? 'Muat Ulang Template' : 'Buka & Kustomisasi' }}
                        </button>
                        <button
                            v-if="form.custom_contract_content"
                            type="button"
                            @click="clearCustomContent"
                            class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold transition-colors"
                        >
                            Reset ke Standar
                        </button>
                    </div>
                </div>

                <div v-if="form.custom_contract_content" class="space-y-1.5">
                    <div class="flex items-center justify-between mb-1">
                        <InputLabel value="Editor Draf Pasal Kontrak (WYSIWYG Rich Text)" />
                        <span class="text-[11px] text-gray-400 font-mono">Draf ini akan dirender langsung ke PDF resmi</span>
                    </div>
                    <ContractWysiwygEditor
                        v-model="form.custom_contract_content"
                        minHeight="350px"
                    />
                </div>
                <div v-else class="text-center py-8 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                    <DocumentTextIcon class="w-8 h-8 mx-auto text-gray-400 mb-2" />
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Kontrak karyawan ini saat ini mengikuti Template Standar {{ form.contract_type }}</p>
                    <p class="text-[11px] text-gray-400 mt-1 mb-3">Klik tombol di bawah jika ingin menambahkan pasal khusus atau mengubah klausul untuk karyawan ini.</p>
                    <button
                        type="button"
                        @click="loadMasterTemplate"
                        class="inline-flex items-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs"
                    >
                        <PencilSquareIcon class="w-4 h-4 mr-1.5" />
                        Mulai Kustomisasi Pasal Kontrak Ini
                    </button>
                </div>
            </div>

            <!-- Pilihan Versi Dokumen Kontrak -->
            <div class="p-3.5 bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-800/80 rounded-xl mt-4">
                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            v-model="form.is_new_version" 
                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-xs focus:ring-indigo-500 h-4 w-4"
                        />
                        <span class="ml-2.5 text-xs font-bold text-gray-800 dark:text-gray-200">
                            Simpan sebagai Versi Baru Dokumen Kontrak (v{{ (props.employee?.contracts?.length || 0) + 1 }})
                        </span>
                    </label>
                    <span 
                        class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                        :class="form.is_new_version ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                    >
                        {{ form.is_new_version ? 'Arsip Snapshot Baru' : 'Koreksi Versi Berjalan' }}
                    </span>
                </div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 pl-6">
                    Centang opsi ini saat perpanjangan masa kerja, kenaikan gaji, atau perubahan klausul penting agar riwayat versi sebelumnya tetap tersimpan utuh dan tidak tertimpa.
                </p>
                <div v-if="form.is_new_version" class="mt-2.5 pl-6">
                    <InputLabel value="Catatan Perubahan Versi Baru (Opsional)" class="text-[11px] mb-1 text-indigo-900 dark:text-indigo-200 font-semibold" />
                    <input 
                        type="text" 
                        v-model="form.catatan_perubahan" 
                        placeholder="Cth: Perpanjangan PKWT Tahun ke-2, Penyesuaian Gaji Pokok, Penambahan Klausul NDA" 
                        class="w-full text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-lg shadow-2xs focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <SecondaryButton @click="close" type="button">Batal</SecondaryButton>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
