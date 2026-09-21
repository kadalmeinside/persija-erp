<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { formatCurrency, formatDate } from '@/utils/helpers';
import { 
    ArrowLeftIcon, 
    FunnelIcon 
} from '@heroicons/vue/24/outline';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    account: Object,
    transactions: Array,
    filters: Object,
    summary: Object,
    is_pph: Boolean
});

const selectedMonth = ref(props.filters.month);
const selectedYear = ref(props.filters.year);

const months = [
    { value: '01', label: 'Januari' },
    { value: '02', label: 'Februari' },
    { value: '03', label: 'Maret' },
    { value: '04', label: 'April' },
    { value: '05', label: 'Mei' },
    { value: '06', label: 'Juni' },
    { value: '07', label: 'Juli' },
    { value: '08', label: 'Agustus' },
    { value: '09', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const currentYear = new Date().getFullYear();
const years = Array.from({length: 5}, (_, i) => currentYear - i);

const applyFilter = () => {
    router.get(route('admin.tax-report.show', props.account.id), {
        month: selectedMonth.value,
        year: selectedYear.value
    }, { preserveState: true, preserveScroll: true });
};

// Upload Modal Logic
const showUploadModal = ref(false);
const selectedInvoiceId = ref(null);
const uploadForm = useForm({
    file: null
});

const openUploadModal = (invoiceId) => {
    selectedInvoiceId.value = invoiceId;
    showUploadModal.value = true;
    uploadForm.reset();
};

const closeUploadModal = () => {
    showUploadModal.value = false;
    selectedInvoiceId.value = null;
    uploadForm.reset();
};

const submitUpload = () => {
    if (!selectedInvoiceId.value) return;
    
    uploadForm.post(route('admin.tax-report.upload-bukti-potong', selectedInvoiceId.value), {
        onSuccess: () => {
            closeUploadModal();
            // Optional: Show toast or notification
        }
    });
};
</script>

<template>
    <Head :title="`Detail Pajak - ${account.nama_akun}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.tax-report.index')" class="p-2 rounded-full hover:bg-gray-200 transition">
                    <ArrowLeftIcon class="w-5 h-5 text-gray-600" />
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Transaksi Pajak</h2>
                    <p class="text-sm text-gray-500">{{ account.kode_akun }} - {{ account.nama_akun }}</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filter & Summary -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-2">
                        <FunnelIcon class="w-5 h-5 text-gray-400" />
                        <select v-model="selectedMonth" @change="applyFilter" class="border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                        <select v-model="selectedYear" @change="applyFilter" class="border-gray-300 rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>

                    <div class="flex gap-6 text-right">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Total Kredit (Kewajiban)</p>
                            <p class="font-bold text-red-600">{{ formatCurrency(summary.total_credit) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Total Debit (Dibayar)</p>
                            <p class="font-bold text-green-600">{{ formatCurrency(summary.total_debit) }}</p>
                        </div>
                        <div class="border-l pl-6">
                            <p class="text-xs text-gray-500 uppercase">Saldo Akhir (Net)</p>
                            <p class="font-bold text-lg text-gray-900">{{ formatCurrency(summary.ending_balance) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Transaction Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Jurnal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kredit</th>
                                    <th v-if="is_pph" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Bukti Potong</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="transactions.length === 0">
                                    <td :colspan="is_pph ? 6 : 5" class="px-6 py-8 text-center text-gray-500 italic">
                                        Tidak ada transaksi pada periode ini.
                                    </td>
                                </tr>
                                <tr v-for="trx in transactions" :key="trx.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDate(trx.header.tgl_jurnal) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-indigo-600">
                                        {{ trx.header.nomor_jurnal }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ trx.header.deskripsi_jurnal }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                        {{ trx.debit > 0 ? formatCurrency(trx.debit) : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                        {{ trx.kredit > 0 ? formatCurrency(trx.kredit) : '-' }}
                                    </td>
                                    <td v-if="is_pph" class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <div v-if="trx.invoice_id">
                                            <a v-if="trx.has_bukti_potong" 
                                               :href="`/storage/${trx.bukti_potong_path}`" 
                                               target="_blank"
                                               class="text-indigo-600 hover:underline font-medium">
                                                Lihat Bukti
                                            </a>
                                            <button v-else 
                                                    @click="openUploadModal(trx.invoice_id)"
                                                    class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded border border-gray-300">
                                                Upload
                                            </button>
                                        </div>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Upload Modal -->
        <Modal :show="showUploadModal" @close="closeUploadModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Upload Bukti Potong PPh</h2>
                <form @submit.prevent="submitUpload">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">File Bukti/Dokumen</label>
                        <input type="file" @change="uploadForm.file = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 dark:border-gray-600 rounded-md p-1 bg-white dark:bg-gray-700 cursor-pointer focus:outline-none" required />
                        <p v-if="uploadForm.errors.file" class="text-sm text-red-600 mt-1">{{ uploadForm.errors.file }}</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="closeUploadModal">Batal</SecondaryButton>
                        <PrimaryButton :disabled="uploadForm.processing">Upload</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
