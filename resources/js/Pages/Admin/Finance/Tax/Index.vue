<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    taxTypes: Object,
    glAccounts: Array,
    programs: Array,
    filters: Object
});

const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
    id: null,
    kode_pajak: '',
    nama_pajak: '',
    rate: 0,
    tipe: 'PPN',
    transaction_type: 'purchase',
    id_akun_gl: '',
    id_program: '',
    is_active: true
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.is_active = true;
    form.tipe = 'PPN';
    form.transaction_type = 'purchase';
    showModal.value = true;
};

const openEditModal = (tax) => {
    isEditing.value = true;
    form.id = tax.id;
    form.kode_pajak = tax.kode_pajak;
    form.nama_pajak = tax.nama_pajak;
    form.rate = tax.rate;
    form.tipe = tax.tipe;
    form.transaction_type = tax.transaction_type;
    form.id_akun_gl = tax.id_akun_gl;
    form.id_program = tax.id_program;
    form.is_active = !!tax.is_active;
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.tax-types.update', form.id), {
            onSuccess: () => showModal.value = false
        });
    } else {
        form.post(route('admin.tax-types.store'), {
            onSuccess: () => showModal.value = false
        });
    }
};

const deleteTax = (tax) => {
    if (confirm(`Apakah Anda yakin ingin menghapus tipe pajak ${tax.nama_pajak}?`)) {
        router.delete(route('admin.tax-types.destroy', tax.id));
    }
};
</script>

<template>
    <Head title="Master Data Pajak" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Master Data Pajak</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div class="flex-1 max-w-sm">
                        <TextInput
                            v-model="filters.search"
                            type="text"
                            placeholder="Cari pajak..."
                            class="w-full"
                            @input="router.get(route('admin.tax-types.index'), { search: $event.target.value }, { preserveState: true, replace: true })"
                        />
                    </div>
                    <PrimaryButton @click="openCreateModal">
                        <PlusIcon class="w-4 h-4 mr-2" /> Tambah Pajak
                    </PrimaryButton>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-x-auto shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pajak</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate (%)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaksi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akun GL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program Kerja</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="tax in taxTypes.data" :key="tax.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ tax.kode_pajak }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ tax.nama_pajak }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ tax.rate }}%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span :class="tax.tipe === 'PPN' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ tax.tipe }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span :class="{
                                        'bg-green-100 text-green-800': tax.transaction_type === 'purchase',
                                        'bg-yellow-100 text-yellow-800': tax.transaction_type === 'sales',
                                        'bg-gray-100 text-gray-800': tax.transaction_type === 'both'
                                    }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full uppercase">
                                        {{ tax.transaction_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ tax.akun_gl?.kode_akun }} - {{ tax.akun_gl?.nama_akun }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ tax.program_kerja?.nama_program || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="tax.is_active" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button @click="openEditModal(tax)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <PencilIcon class="w-4 h-4" />
                                    </button>
                                    <button @click="deleteTax(tax)" class="text-red-600 hover:text-red-900">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="taxTypes.data.length === 0">
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">Belum ada data pajak.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ isEditing ? 'Edit Pajak' : 'Tambah Pajak Baru' }}</h2>
                
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel value="Kode Pajak" />
                            <TextInput v-model="form.kode_pajak" type="text" class="mt-1 block w-full" placeholder="Contoh: PPN11" required />
                        </div>
                        <div>
                            <InputLabel value="Nama Pajak" />
                            <TextInput v-model="form.nama_pajak" type="text" class="mt-1 block w-full" placeholder="Contoh: PPN 11%" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel value="Rate (%)" />
                            <TextInput v-model="form.rate" type="number" step="0.01" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel value="Tipe Pajak" />
                            <select v-model="form.tipe" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="PPN">PPN (Menambah Tagihan)</option>
                                <option value="PPh">PPh (Memotong Tagihan)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Jenis Transaksi (Peruntukan)" />
                        <select v-model="form.transaction_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="purchase">Purchase (Expense / Pengeluaran)</option>
                            <option value="sales">Sales (Income / Pendapatan)</option>
                            <option value="both">Both (Keduanya)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Menentukan di mana pajak ini akan muncul (Form Pengajuan atau Invoice).</p>
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Akun GL Terkait" />
                        <select v-model="form.id_akun_gl" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="" disabled>Pilih Akun GL</option>
                            <option v-for="acc in glAccounts" :key="acc.id" :value="acc.id">{{ acc.kode_akun }} - {{ acc.nama_akun }}</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1" v-if="form.tipe === 'PPN'">Pilih akun Kewajiban (Hutang PPN).</p>
                        <p class="text-xs text-gray-500 mt-1" v-if="form.tipe === 'PPh'">Pilih akun Aset (Piutang PPh / Prepaid Tax).</p>
                    </div>

                    <div class="mb-4">
                        <InputLabel value="Program Kerja (Cost Center)" />
                        <select v-model="form.id_program" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="" disabled>Pilih Program Kerja</option>
                            <option v-for="prog in programs" :key="prog.id" :value="prog.id">{{ prog.nama_program }}</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Program yang bertanggung jawab atas pembayaran pajak ini (misal: Finance).</p>
                    </div>

                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Aktif</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-4">
                        <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
