<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    accounts: Array
});

const form = useForm({
    tgl_jurnal: new Date().toISOString().substr(0, 10),
    deskripsi_jurnal: '',
    details: [
        { id_akun: '', debit: 0, kredit: 0, keterangan_baris: '' },
        { id_akun: '', debit: 0, kredit: 0, keterangan_baris: '' }
    ]
});

const addLine = () => {
    form.details.push({ id_akun: '', debit: 0, kredit: 0, keterangan_baris: '' });
};

const removeLine = (index) => {
    if (form.details.length > 2) {
        form.details.splice(index, 1);
    }
};

const totalDebit = computed(() => {
    return form.details.reduce((sum, item) => sum + Number(item.debit || 0), 0);
});

const totalCredit = computed(() => {
    return form.details.reduce((sum, item) => sum + Number(item.kredit || 0), 0);
});

const isBalanced = computed(() => {
    return Math.abs(totalDebit.value - totalCredit.value) < 0.01;
});

const submit = () => {
    if (!isBalanced.value) {
        alert('Jurnal tidak seimbang (Unbalanced). Mohon periksa kembali.');
        return;
    }
    form.post(route('admin.journals.store'));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};
</script>

<template>
    <Head title="Buat Jurnal Baru" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Buat Jurnal Baru</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <InputLabel value="Tanggal Jurnal" />
                                <TextInput type="date" v-model="form.tgl_jurnal" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel value="Keterangan / Deskripsi" />
                                <TextInput type="text" v-model="form.deskripsi_jurnal" class="mt-1 block w-full" placeholder="Contoh: Penyesuaian Sewa Dibayar Dimuka" required />
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Rincian Jurnal</h3>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Akun GL</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Keterangan Baris</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Debit</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kredit</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="(detail, index) in form.details" :key="index">
                                            <td class="px-4 py-2 w-1/3">
                                                <select v-model="detail.id_akun" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                                                    <option value="" disabled>Pilih Akun</option>
                                                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                                        {{ acc.kode_akun }} - {{ acc.nama_akun }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="text" v-model="detail.keterangan_baris" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" v-model="detail.debit" min="0" class="w-full text-right border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                            </td>
                                            <td class="px-4 py-2">
                                                <input type="number" v-model="detail.kredit" min="0" class="w-full text-right border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" @click="removeLine(index)" class="text-red-600 hover:text-red-800" :disabled="form.details.length <= 2">
                                                    <TrashIcon class="w-5 h-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50 dark:bg-gray-700 font-bold">
                                        <tr>
                                            <td colspan="2" class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">Total</td>
                                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(totalDebit) }}</td>
                                            <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(totalCredit) }}</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">Status</td>
                                            <td colspan="2" class="px-4 py-2 text-center">
                                                <span :class="isBalanced ? 'text-green-600' : 'text-red-600'">
                                                    {{ isBalanced ? 'Seimbang (Balanced)' : 'Tidak Seimbang (Unbalanced)' }}
                                                </span>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="mt-4">
                                <SecondaryButton type="button" @click="addLine">
                                    <PlusIcon class="w-4 h-4 mr-2" /> Tambah Baris
                                </SecondaryButton>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <Link :href="route('admin.journals.index')" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">Batal</Link>
                            <PrimaryButton :disabled="form.processing || !isBalanced">
                                Simpan & Posting
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
