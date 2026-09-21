<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref, watch, computed } from 'vue';
import { TrashIcon, PlusIcon, PencilSquareIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    departemens: Array,
    karyawans: Array,
    rules: Array,
    filters: Object
});

const selectedDepartemen = ref(props.filters.id_departemen || '');
const selectedTipe = ref(props.filters.tipe || 'Pengajuan');
const isEditing = ref(false);
const editingRuleId = ref(null);

// Apakah tipe ini perlu field min_amount (threshold Direktur)?
const hasMinAmount = computed(() =>
    ['Pengajuan', 'Invoice'].includes(selectedTipe.value)
);

// Label tipe
const tipeLabels = {
    Pengajuan:   'Pengajuan Dana / Reimbursement',
    Cuti:        'Cuti / Izin',
    Pinjaman:    'Pinjaman Karyawan',
    Invoice:     'Invoice (Piutang Penjualan)',
    PettyCash:   'Petty Cash (Kas Kecil)',
};

// Watch filter changes → reload
watch([selectedDepartemen, selectedTipe], ([newDept, newTipe]) => {
    if (newDept) {
        router.get(route('admin.approval-rules.index'), {
            id_departemen: newDept,
            tipe: newTipe,
        }, { preserveState: true });
    }
});

const form = useForm({
    id_departemen:        props.filters.id_departemen || '',
    id_karyawan_approver: '',
    level_order:          '',
    label_aksi:           '',
    tipe:                 props.filters.tipe || 'Pengajuan',
    min_amount:           '',  // Threshold — hanya berlaku jika diisi
});

watch([selectedDepartemen, selectedTipe], ([newDept, newTipe]) => {
    form.id_departemen = newDept;
    form.tipe = newTipe;
    form.min_amount = '';
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.approval-rules.update', editingRuleId.value), {
            onSuccess: () => resetForm()
        });
    } else {
        form.post(route('admin.approval-rules.store'), {
            onSuccess: () => resetForm()
        });
    }
};

const editRule = (rule) => {
    isEditing.value = true;
    editingRuleId.value = rule.id;
    form.id_departemen        = rule.id_departemen;
    form.id_karyawan_approver = rule.id_karyawan_approver;
    form.level_order          = rule.level_order;
    form.label_aksi           = rule.label_aksi;
    form.tipe                 = rule.tipe;
    form.min_amount           = rule.min_amount || '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const resetForm = () => {
    isEditing.value = false;
    editingRuleId.value = null;
    form.id_karyawan_approver = '';
    form.level_order          = '';
    form.label_aksi           = '';
    form.min_amount           = '';
    form.clearErrors();
    form.id_departemen = selectedDepartemen.value;
    form.tipe          = selectedTipe.value;
};

const deleteRule = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus rule ini? Langkah approval ini akan dihapus dari alur.')) {
        router.delete(route('admin.approval-rules.destroy', id));
    }
};

const formatCurrency = (v) =>
    v ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v) : null;
</script>

<template>
    <Head title="Workflow Approval" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manajemen Workflow Approval</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Filter Departemen & Tipe -->
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                        <div>
                            <InputLabel value="Pilih Departemen" />
                            <select v-model="selectedDepartemen" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="" disabled>-- Pilih Departemen --</option>
                                <option v-for="dept in departemens" :key="dept.id" :value="dept.id">{{ dept.nama_departemen }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Tipe Workflow" />
                            <select v-model="selectedTipe" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="Pengajuan">Pengajuan Dana / Reimbursement</option>
                                <option value="Cuti">Cuti / Izin</option>
                                <option value="Pinjaman">Pinjaman Karyawan</option>
                                <option value="Invoice">Invoice (Piutang Penjualan)</option>
                                <option value="PettyCash">Petty Cash (Kas Kecil)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Info konteks per tipe -->
                    <div v-if="selectedTipe === 'Invoice'" class="mt-4 flex items-start gap-2 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700 max-w-2xl">
                        <InformationCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        <p>Untuk Invoice: gunakan <strong>Min. Nominal</strong> agar level Direktur hanya aktif jika tagihan ≥ threshold. Contoh: Level 1 = Finance Manager (tanpa min), Level 2 = Direktur (min Rp 1.000.000).</p>
                    </div>
                    <div v-else-if="selectedTipe === 'PettyCash'" class="mt-4 flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700 max-w-2xl">
                        <InformationCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5" />
                        <p>Untuk Petty Cash: biasanya cukup 1 level approver (Finance Manager yang ditunjuk). Approver dipilih spesifik per orang, bukan per role.</p>
                    </div>
                </div>

                <div v-if="selectedDepartemen" class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- Form Tambah/Edit -->
                    <div class="md:col-span-1">
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 sticky top-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                <component :is="isEditing ? PencilSquareIcon : PlusIcon" class="w-5 h-5 text-indigo-500" />
                                {{ isEditing ? 'Edit Approver' : 'Tambah Approver' }}
                            </h3>

                            <form @submit.prevent="submit" class="space-y-4">
                                <!-- Tipe (readonly display) -->
                                <div>
                                    <InputLabel value="Tipe Workflow" />
                                    <div class="mt-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-md text-gray-700 dark:text-gray-300 font-medium text-sm">
                                        {{ tipeLabels[form.tipe] || form.tipe }}
                                    </div>
                                </div>

                                <!-- Level Order -->
                                <div>
                                    <InputLabel value="Level (Urutan)" />
                                    <TextInput v-model="form.level_order" type="number" min="1" class="mt-1 block w-full" placeholder="1" required />
                                    <p class="text-xs text-gray-500 mt-1">Semakin kecil angka → semakin awal disetujui.</p>
                                </div>

                                <!-- Approver -->
                                <div>
                                    <InputLabel value="Approver (Karyawan)" />
                                    <select v-model="form.id_karyawan_approver" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                                        <option value="" disabled>Pilih Karyawan</option>
                                        <option v-for="k in karyawans" :key="k.id" :value="k.id">{{ k.nama_lengkap }} — {{ k.jabatan }}</option>
                                    </select>
                                </div>

                                <!-- Label Aksi -->
                                <div>
                                    <InputLabel value="Label Aksi" />
                                    <TextInput v-model="form.label_aksi" type="text" class="mt-1 block w-full" placeholder="Contoh: Disetujui Finance Manager" required />
                                </div>

                                <!-- Min Amount (conditional) -->
                                <div v-if="hasMinAmount">
                                    <InputLabel value="Min. Nominal (Threshold)" />
                                    <TextInput v-model="form.min_amount" type="number" min="0" step="1000" class="mt-1 block w-full" placeholder="Opsional — kosongkan jika selalu berlaku" />
                                    <p class="text-xs text-gray-500 mt-1">
                                        Jika diisi, level ini hanya berlaku saat nominal dokumen ≥ nilai ini.
                                        Jika nominal di bawah threshold → level ini dilewati otomatis (Auto-skip).
                                    </p>
                                </div>

                                <!-- Buttons -->
                                <div class="flex justify-end gap-2 pt-2">
                                    <DangerButton v-if="isEditing" @click="resetForm" type="button">Batal</DangerButton>
                                    <PrimaryButton :disabled="form.processing">
                                        <component :is="isEditing ? PencilSquareIcon : PlusIcon" class="w-4 h-4 mr-1.5" />
                                        {{ isEditing ? 'Simpan' : 'Tambah' }}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Daftar Rules — Timeline -->
                    <div class="md:col-span-2">
                        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
                                Alur Persetujuan Saat Ini
                                <span class="ml-2 text-sm font-normal text-gray-400">— {{ tipeLabels[selectedTipe] || selectedTipe }}</span>
                            </h3>

                            <div v-if="rules.length > 0" class="relative border-l-2 border-indigo-200 dark:border-indigo-900 ml-4 space-y-6">
                                <div v-for="rule in rules" :key="rule.id" class="relative pl-8">
                                    <!-- Timeline dot -->
                                    <span class="absolute top-1.5 left-[-9px] bg-indigo-500 w-4 h-4 rounded-full border-2 border-white dark:border-gray-800"></span>

                                    <div class="bg-gray-50 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600 rounded-xl p-4">
                                        <div class="flex justify-between items-start gap-4">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">Level {{ rule.level_order }}</span>
                                                    <!-- Min Amount Badge -->
                                                    <span v-if="rule.min_amount" class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-semibold rounded-full">
                                                        ≥ {{ formatCurrency(rule.min_amount) }}
                                                    </span>
                                                    <span v-else-if="hasMinAmount" class="inline-flex items-center px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-semibold rounded-full">
                                                        Semua Nominal
                                                    </span>
                                                </div>
                                                <p class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                                    {{ rule.karyawan_approver?.nama_lengkap }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ rule.karyawan_approver?.jabatan }}</p>
                                                <div class="mt-2">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                                        {{ rule.label_aksi }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex gap-1 flex-shrink-0">
                                                <button @click="editRule(rule)" class="p-1.5 rounded-md text-blue-500 hover:bg-blue-50 hover:text-blue-700 transition" title="Edit">
                                                    <PencilSquareIcon class="w-4 h-4" />
                                                </button>
                                                <button @click="deleteRule(rule.id)" class="p-1.5 rounded-md text-red-500 hover:bg-red-50 hover:text-red-700 transition" title="Hapus">
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-12 text-gray-400">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <PlusIcon class="w-6 h-6 text-gray-400" />
                                </div>
                                <p class="text-sm">Belum ada rule approval untuk departemen ini.</p>
                                <p class="text-xs mt-1">Tambahkan approver menggunakan form di sebelah kiri.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prompt jika departemen belum dipilih -->
                <div v-else class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-12 text-center text-gray-400">
                    <InformationCircleIcon class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                    <p class="font-medium">Pilih departemen dan tipe workflow</p>
                    <p class="text-sm mt-1">untuk melihat dan mengelola alur persetujuan.</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
