<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { PlusIcon, TrashIcon, DocumentTextIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    employee: Object,
    lokasiKantors: Array,
});

const emit = defineEmits(['close', 'saved']);

const activeSubTab = ref('clauses'); // 'clauses' or 'scope'

const form = useForm({
    nomor_kontrak: '',
    contract_type: 'PKWT',
    durasi_probation_bulan: 3,
    tanggal_selesai_probation: '',
    notice_period_hari: 30,
    jam_kerja_mingguan: 40,
    id_lokasi_kantor: '',
    is_strict_location: true,
    working_scope: [],
});

const newScopeItem = ref('');

watch(() => props.show, (newVal) => {
    if (newVal && props.employee) {
        form.nomor_kontrak = props.employee.nomor_kontrak || '';
        form.contract_type = props.employee.contract_type || 'PKWT';
        form.durasi_probation_bulan = props.employee.durasi_probation_bulan ?? 3;
        form.tanggal_selesai_probation = props.employee.tanggal_selesai_probation || '';
        form.notice_period_hari = props.employee.notice_period_hari ?? 30;
        form.jam_kerja_mingguan = props.employee.jam_kerja_mingguan ?? 40;
        form.id_lokasi_kantor = props.employee.id_lokasi_kantor || '';
        form.is_strict_location = props.employee.is_strict_location == 1;

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

const submit = () => {
    form.post(route('admin.karyawan.contract-details.update', props.employee.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
            Swal.fire({
                icon: 'success',
                title: 'Tersimpan',
                text: 'Klausul kontrak dan lingkup kerja berhasil diperbarui',
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
                        Pengaturan Klausul Kontrak & Working Scope
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ employee?.nama_lengkap }} ({{ employee?.jabatan }})</p>
                </div>
            </div>

            <!-- Sub Tabs -->
            <div class="flex space-x-2 border-b border-gray-100 dark:border-gray-700/60 mb-5">
                <button
                    type="button"
                    @click="activeSubTab = 'clauses'"
                    :class="[
                        activeSubTab === 'clauses'
                            ? 'border-indigo-600 text-indigo-600 font-bold border-b-2'
                            : 'text-gray-500 hover:text-gray-700',
                        'px-4 py-2 text-xs flex items-center transition-colors'
                    ]"
                >
                    <DocumentTextIcon class="w-4 h-4 mr-1.5" />
                    Klausul & Kebijakan Kontrak
                </button>
                <button
                    type="button"
                    @click="activeSubTab = 'scope'"
                    :class="[
                        activeSubTab === 'scope'
                            ? 'border-indigo-600 text-indigo-600 font-bold border-b-2'
                            : 'text-gray-500 hover:text-gray-700',
                        'px-4 py-2 text-xs flex items-center transition-colors'
                    ]"
                >
                    <ClipboardDocumentListIcon class="w-4 h-4 mr-1.5" />
                    Working Scope / Tupoksi ({{ form.working_scope.length }})
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
                            class="mt-1 block w-full"
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
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-xs text-sm"
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
                        <InputLabel for="durasi_probation_bulan" value="Masa Percobaan (Bulan)" />
                        <TextInput
                            id="durasi_probation_bulan"
                            type="number"
                            min="0"
                            max="12"
                            class="mt-1 block w-full"
                            v-model="form.durasi_probation_bulan"
                        />
                        <InputError class="mt-1" :message="form.errors.durasi_probation_bulan" />
                    </div>

                    <div>
                        <InputLabel for="notice_period_hari" value="Notice Period Resign (Hari)" />
                        <TextInput
                            id="notice_period_hari"
                            type="number"
                            min="0"
                            max="180"
                            class="mt-1 block w-full"
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
                            class="mt-1 block w-full"
                            v-model="form.jam_kerja_mingguan"
                        />
                        <InputError class="mt-1" :message="form.errors.jam_kerja_mingguan" />
                    </div>

                    <div>
                        <InputLabel for="id_lokasi_kantor" value="Lokasi Kantor Utama" />
                        <select
                            id="id_lokasi_kantor"
                            v-model="form.id_lokasi_kantor"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-xs text-sm"
                        >
                            <option value="">Kantor Pusat / Bebas (Default)</option>
                            <option v-for="lok in lokasiKantors" :key="lok.id" :value="lok.id">
                                {{ lok.nama_kantor }}
                            </option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.id_lokasi_kantor" />
                    </div>
                </div>

                <!-- Strict Geofencing Toggle -->
                <div class="p-3.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-900 dark:text-gray-200">Kebijakan Presensi Lokasi (Strict Geofence)</p>
                        <p class="text-[11px] text-gray-500 mt-0.5">Jika aktif, karyawan hanya dapat check-in dalam radius GPS kantor yang ditentukan.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.is_strict_location" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
            </div>

            <!-- SubTab 2: Working Scope -->
            <div v-show="activeSubTab === 'scope'" class="space-y-3">
                <p class="text-xs text-gray-500">Tentukan butir-butir tugas pokok & fungsi (Tupoksi) profesional yang tercantum dalam kontrak:</p>

                <!-- List of Current Scope items -->
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    <div 
                        v-for="(item, idx) in form.working_scope" 
                        :key="idx"
                        class="flex items-start justify-between gap-2 p-2.5 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-700 dark:text-gray-300"
                    >
                        <div class="flex items-start space-x-2 flex-1">
                            <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 font-bold flex items-center justify-center shrink-0 text-[10px]">
                                {{ idx + 1 }}
                            </span>
                            <textarea 
                                v-model="form.working_scope[idx]" 
                                rows="2" 
                                class="w-full text-xs bg-transparent border-0 focus:ring-1 focus:ring-indigo-500 rounded p-1 resize-none"
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
