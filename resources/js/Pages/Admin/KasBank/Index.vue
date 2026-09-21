<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useClientValidation } from '@/Composables/useClientValidation';
import { 
    PlusIcon, PencilSquareIcon, TrashIcon, MagnifyingGlassIcon,
    BanknotesIcon
} from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    kasBanks: Object,
    akunGls: Array,
    filters: Object
});

const search = ref(props.filters.search);

watch(search, debounce((value) => {
    router.get(route('admin.kas-bank.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
    id: null,
    nama_bank: '',
    nomor_rekening: '',
    atas_nama: '',
    id_akun_gl: '',
    saldo_awal: 0,
    is_active: true
});

const { clientErrors, validate, clearClientError, clearAllClientErrors, hasClientErrors } = useClientValidation();

const kasBankRules = {
    nama_bank:      ['required', 'string', 'max:100'],
    nomor_rekening: ['required', 'string', 'max:50'],
    atas_nama:      ['required', 'string', 'max:100'],
    id_akun_gl:     ['required'],
    saldo_awal:     ['numeric', 'min:0'],
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    clearAllClientErrors();
    showModal.value = true;
};

const openEditModal = (bank) => {
    isEditing.value = true;
    form.id = bank.id;
    form.nama_bank = bank.nama_bank;
    form.nomor_rekening = bank.nomor_rekening;
    form.atas_nama = bank.atas_nama;
    form.id_akun_gl = bank.id_akun_gl;
    form.saldo_awal = bank.saldo_awal;
    form.is_active = Boolean(bank.is_active);
    form.clearErrors();
    clearAllClientErrors();
    showModal.value = true;
};

const submit = () => {
    const errors = validate(form, kasBankRules, {
        'nama_bank.label':       'Nama Bank/Kas',
        'nomor_rekening.label':  'Nomor Rekening',
        'atas_nama.label':       'Atas Nama',
        'id_akun_gl.required':   'Akun GL wajib dipilih.',
        'saldo_awal.label':      'Saldo Awal',
    });
    if (hasClientErrors(errors)) return;

    if (isEditing.value) {
        form.put(route('admin.kas-bank.update', form.id), {
            onSuccess: () => showModal.value = false
        });
    } else {
        form.post(route('admin.kas-bank.store'), {
            onSuccess: () => showModal.value = false
        });
    }
};

const deleteBank = (id) => {
    if (confirm('Yakin ingin menghapus data ini?')) {
        router.delete(route('admin.kas-bank.destroy', id));
    }
};
</script>

<template>
    <Head title="Manajemen Kas & Bank" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen Kas & Bank</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <div class="flex justify-between items-center">
                    <div class="relative w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400"/>
                        </span>
                        <input v-model="search" type="text" placeholder="Cari Bank..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <PrimaryButton @click="openCreateModal">
                        <PlusIcon class="w-5 h-5 mr-2"/> Tambah Kas/Bank
                    </PrimaryButton>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Bank / Kas</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nomor Rekening</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Atas Nama</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Saldo Awal</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Link Akun GL</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="kasBanks.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 italic">Tidak ada data.</td>
                            </tr>
                            <tr v-for="bank in kasBanks.data" :key="bank.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                                            <BanknotesIcon class="w-4 h-4"/>
                                        </div>
                                        <div class="ml-4 text-sm font-medium text-gray-900">{{ bank.nama_bank }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">{{ bank.nomor_rekening }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ bank.atas_nama }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-mono text-gray-900">
                                    {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(bank.saldo_awal || 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span v-if="bank.akun_gl" class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">{{ bank.akun_gl.kode_akun }}</span>
                                    <span v-else class="text-red-500 text-xs italic">Unlinked</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="bank.is_active" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Non-Aktif</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button @click="openEditModal(bank)" class="text-indigo-600 hover:text-indigo-900"><PencilSquareIcon class="w-5 h-5"/></button>
                                    <button @click="deleteBank(bank.id)" class="text-red-600 hover:text-red-900"><TrashIcon class="w-5 h-5"/></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <Pagination :links="kasBanks.links" class="p-6 border-t border-gray-200" />
                </div>
            </div>
        </div>

        <!-- MODAL FORM -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">{{ isEditing ? 'Edit Kas/Bank' : 'Tambah Kas/Bank Baru' }}</h2>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nama Bank / Kas" />
                        <TextInput v-model="form.nama_bank" @input="clearClientError('nama_bank')" class="w-full mt-1" :class="{'border-red-500': clientErrors.nama_bank || form.errors.nama_bank}" placeholder="Contoh: Bank BCA Operasional" />
                        <InputError :message="clientErrors.nama_bank || form.errors.nama_bank" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Nomor Rekening" />
                            <TextInput v-model="form.nomor_rekening" @input="clearClientError('nomor_rekening')" class="w-full mt-1" :class="{'border-red-500': clientErrors.nomor_rekening || form.errors.nomor_rekening}" />
                            <InputError :message="clientErrors.nomor_rekening || form.errors.nomor_rekening" />
                        </div>
                        <div>
                            <InputLabel value="Atas Nama" />
                            <TextInput v-model="form.atas_nama" @input="clearClientError('atas_nama')" class="w-full mt-1" :class="{'border-red-500': clientErrors.atas_nama || form.errors.atas_nama}" />
                            <InputError :message="clientErrors.atas_nama || form.errors.atas_nama" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Saldo Awal (IDR)" />
                        <TextInput v-model="form.saldo_awal" @input="clearClientError('saldo_awal')" type="number" class="w-full mt-1" placeholder="0" :class="{'border-red-500': clientErrors.saldo_awal || form.errors.saldo_awal}" />
                        <InputError :message="clientErrors.saldo_awal || form.errors.saldo_awal" />
                    </div>

                    <div>
                        <InputLabel value="Link ke Akun GL (Aset)" />
                        <select v-model="form.id_akun_gl" @change="clearClientError('id_akun_gl')" class="w-full border-gray-300 rounded-md shadow-sm mt-1 focus:ring-indigo-500 focus:border-indigo-500" :class="{'border-red-500': clientErrors.id_akun_gl || form.errors.id_akun_gl}">
                            <option value="">Pilih Akun GL...</option>
                            <option v-for="akun in akunGls" :key="akun.id" :value="akun.id">{{ akun.kode_akun }} - {{ akun.nama_akun }}</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Pilih akun aset yang merepresentasikan saldo kas/bank ini di neraca.</p>
                        <InputError :message="clientErrors.id_akun_gl || form.errors.id_akun_gl" />
                    </div>

                    <div v-if="isEditing" class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" id="active" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <label for="active" class="text-sm text-gray-700">Status Aktif</label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submit" :disabled="form.processing">{{ isEditing ? 'Simpan Perubahan' : 'Simpan Data' }}</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
