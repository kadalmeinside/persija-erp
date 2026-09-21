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
    customers: Array,
    revenueAccounts: Array,
    taxTypes: Array,
    departments: Array
});

const form = useForm({
    id_pelanggan: '',
    id_departemen: '',
    tgl_invoice: new Date().toISOString().substr(0, 10),
    tgl_jatuh_tempo: new Date().toISOString().substr(0, 10),
    catatan: '',
    items: [
        { deskripsi_item: '', kuantitas: 1, harga_satuan: 0, id_akun_pendapatan: '' }
    ],
    id_tax_ppn: null,
    id_tax_pph: null
});

// Filter Tax Options (Sales Only)
const ppnOptions = computed(() => props.taxTypes.filter(t => t.tipe === 'PPN' && t.transaction_type === 'sales'));
const pphOptions = computed(() => props.taxTypes.filter(t => t.tipe === 'PPh' && t.transaction_type === 'sales'));

// Set Default PPN if available
if (ppnOptions.value.length > 0) {
    // Try to find 11% or first
    const defaultPPN = ppnOptions.value.find(t => t.rate == 11) || ppnOptions.value[0];
    form.id_tax_ppn = defaultPPN.id;
}

const addItem = () => {
    form.items.push({ deskripsi_item: '', kuantitas: 1, harga_satuan: 0, id_akun_pendapatan: '' });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.kuantitas * item.harga_satuan), 0);
});

const ppnAmount = computed(() => {
    if (!form.id_tax_ppn) return 0;
    const tax = props.taxTypes.find(t => t.id === form.id_tax_ppn);
    return tax ? (subtotal.value * (tax.rate / 100)) : 0;
});

const pphAmount = computed(() => {
    if (!form.id_tax_pph) return 0;
    const tax = props.taxTypes.find(t => t.id === form.id_tax_pph);
    return tax ? (subtotal.value * (tax.rate / 100)) : 0;
});

const totalTagihan = computed(() => {
    return subtotal.value + ppnAmount.value;
});

const netReceived = computed(() => {
    return totalTagihan.value - pphAmount.value;
});

const submit = () => {
    form.post(route('admin.invoices.store'));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
};
</script>

<template>
    <Head title="Buat Invoice Baru" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Buat Invoice Baru</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <InputLabel value="Pelanggan" />
                                <select v-model="form.id_pelanggan" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled>Pilih Pelanggan</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.nama_pelanggan }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Departemen (Profit Center)" />
                                <select v-model="form.id_departemen" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled>Pilih Departemen</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.nama_departemen }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <InputLabel value="Tanggal Invoice" />
                                <TextInput v-model="form.tgl_invoice" type="date" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel value="Jatuh Tempo" />
                                <TextInput v-model="form.tgl_jatuh_tempo" type="date" class="mt-1 block w-full" required />
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <InputLabel value="Catatan" />
                            <TextInput type="text" v-model="form.catatan" class="mt-1 block w-full" placeholder="Catatan tambahan..." />
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Item Tagihan</h3>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Akun Pendapatan</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="(item, index) in form.items" :key="index">
                                            <td class="px-4 py-2">
                                                <TextInput type="text" v-model="item.deskripsi_item" class="w-full" required />
                                            </td>
                                            <td class="px-4 py-2 w-1/4">
                                                <select v-model="item.id_akun_pendapatan" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                                                    <option value="" disabled>Pilih Akun</option>
                                                    <option v-for="acc in revenueAccounts" :key="acc.id" :value="acc.id">{{ acc.kode_akun }} - {{ acc.nama_akun }}</option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-2 w-24">
                                                <TextInput type="number" v-model="item.kuantitas" min="1" class="w-full text-right" required />
                                            </td>
                                            <td class="px-4 py-2 w-40">
                                                <TextInput type="number" v-model="item.harga_satuan" min="0" class="w-full text-right" required />
                                            </td>
                                            <td class="px-4 py-2 text-right font-medium">
                                                {{ formatCurrency(item.kuantitas * item.harga_satuan) }}
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <button type="button" @click="removeItem(index)" class="text-red-600 hover:text-red-800" :disabled="form.items.length <= 1">
                                                    <TrashIcon class="w-5 h-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50 dark:bg-gray-700 font-bold">
                                        <tr>
                                            <td colspan="4" class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">Subtotal</td>
                                            <td class="px-4 py-2 text-right text-gray-900 dark:text-white">{{ formatCurrency(subtotal) }}</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">
                                                <div class="flex items-center justify-end gap-2">
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">PPN (VAT):</span>
                                                    <select v-model="form.id_tax_ppn" class="text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option :value="null">Tanpa PPN</option>
                                                        <option v-for="tax in ppnOptions" :key="tax.id" :value="tax.id">{{ tax.nama_pajak }} ({{ tax.rate }}%)</option>
                                                    </select>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-right text-gray-900 dark:text-white">{{ formatCurrency(ppnAmount) }}</td>
                                            <td></td>
                                        </tr>
                                        <tr class="text-lg border-t-2 border-gray-300">
                                            <td colspan="4" class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">Total Tagihan</td>
                                            <td class="px-4 py-2 text-right text-indigo-600 dark:text-indigo-400">{{ formatCurrency(totalTagihan) }}</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">
                                                <div class="flex items-center justify-end gap-2">
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">PPh (Withholding):</span>
                                                    <select v-model="form.id_tax_pph" class="text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option :value="null">Tanpa PPh</option>
                                                        <option v-for="tax in pphOptions" :key="tax.id" :value="tax.id">{{ tax.nama_pajak }} ({{ tax.rate }}%)</option>
                                                    </select>
                                                </div>
                                                <p class="text-xs text-gray-500 italic text-right mt-1">*PPh mengurangi jumlah yang diterima, bukan tagihan.</p>
                                            </td>
                                            <td class="px-4 py-2 text-right text-red-600 dark:text-red-400">- {{ formatCurrency(pphAmount) }}</td>
                                            <td></td>
                                        </tr>
                                        <tr class="bg-indigo-50 dark:bg-indigo-900/20">
                                            <td colspan="4" class="px-4 py-2 text-right text-gray-700 dark:text-gray-300 font-bold">Estimasi Diterima (Net)</td>
                                            <td class="px-4 py-2 text-right text-green-600 dark:text-green-400 font-bold">{{ formatCurrency(netReceived) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="mt-4">
                                <SecondaryButton type="button" @click="addItem">
                                    <PlusIcon class="w-4 h-4 mr-2" /> Tambah Item
                                </SecondaryButton>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <Link :href="route('admin.invoices.index')" class="text-gray-600 hover:text-gray-900">Batal</Link>
                            <PrimaryButton :disabled="form.processing">
                                Simpan & Posting Invoice
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
