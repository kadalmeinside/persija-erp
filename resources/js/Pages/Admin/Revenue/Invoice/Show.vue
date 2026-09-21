<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import FilePreviewModal from '@/Components/FilePreviewModal.vue';
import {
    ArrowLeftIcon,
    BanknotesIcon,
    PrinterIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    ShieldCheckIcon,
    DocumentCheckIcon,
} from '@heroicons/vue/24/outline';
import { CheckBadgeIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    invoice: Object,
    kasBanks: Array,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// ---- STATUS BADGE ----
const statusClass = computed(() => {
    const map = {
        Draft:      'bg-blue-100 text-blue-800 border-blue-200',
        Unpaid:     'bg-red-100 text-red-800 border-red-200',
        Partial:    'bg-yellow-100 text-yellow-800 border-yellow-200',
        Paid:       'bg-green-100 text-green-800 border-green-200',
        Cancelled:  'bg-gray-100 text-gray-600 border-gray-200',
    };
    return map[props.invoice.status] || 'bg-gray-100 text-gray-600';
});

const statusLabel = computed(() => {
    const map = {
        Draft: 'Menunggu Persetujuan',
        Unpaid: 'Belum Dibayar',
        Partial: 'Dibayar Sebagian',
        Paid: 'Lunas',
        Cancelled: 'Dibatalkan',
    };
    return map[props.invoice.status] || props.invoice.status;
});

// ---- PAYMENT MODAL ----
const showPaymentModal = ref(false);
const paymentForm = useForm({
    tgl_bayar: new Date().toISOString().substr(0, 10),
    nominal_bayar: props.invoice.sisa_tagihan,
    id_kas_bank: '',
    catatan: '',
    bukti_bayar: null
});

const openPaymentModal = () => {
    paymentForm.nominal_bayar = props.invoice.sisa_tagihan;
    showPaymentModal.value = true;
};

const submitPayment = () => {
    paymentForm.post(route('admin.invoices.payment.store', props.invoice.id), {
        onSuccess: () => showPaymentModal.value = false
    });
};

// ---- APPROVAL MODAL ----
const showApprovalModal = ref(false);
const approvalForm = useForm({ catatan: '' });

const submitApproval = () => {
    approvalForm.post(route('admin.invoices.approve', props.invoice.id), {
        onSuccess: () => showApprovalModal.value = false,
        onError: () => {},
    });
};

// ---- CANCEL ----
const confirmCancel = () => {
    if (confirm('Apakah Anda yakin ingin membatalkan (VOID) invoice ini? Status akan menjadi Cancelled dan jurnal akan dibalik.')) {
        router.put(route('admin.invoices.cancel', props.invoice.id));
    }
};

// ---- FILE PREVIEW ----
const showFilePreview = ref(false);
const activeFileUrl = ref('');
const activeFileType = ref('');
const activeFileName = ref('');

const setPreviewFile = (path) => {
    if (!path) return;
    activeFileUrl.value = `/storage/${path}`;
    activeFileName.value = path.split('/').pop();
    activeFileType.value = path.toLowerCase().endsWith('.pdf') ? 'pdf' : 'image';
    showFilePreview.value = true;
};

// ---- APPROVAL PROCESS HELPERS ----
const approvalSteps = computed(() => props.invoice.approval_process || []);

const stepStatusClass = (status) => {
    const map = {
        Approved: 'bg-green-100 text-green-700 border-green-200',
        Pending:  'bg-yellow-50 text-yellow-700 border-yellow-200',
        Rejected: 'bg-red-100 text-red-700 border-red-200',
        Skipped:  'bg-gray-100 text-gray-500 border-gray-200',
    };
    return map[status] || 'bg-gray-100 text-gray-500';
};

const stepStatusIcon = (status) => {
    if (status === 'Approved') return CheckCircleIcon;
    if (status === 'Rejected') return XCircleIcon;
    if (status === 'Skipped') return DocumentCheckIcon;
    return ClockIcon;
};

// Apakah user ini adalah approver yang pending?
const currentPendingStep = computed(() => {
    return approvalSteps.value.find(s => s.status === 'Pending');
});
</script>

<template>
    <Head :title="`Invoice ${invoice.nomor_invoice}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Detail Invoice</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Top Actions Bar -->
                <div class="mb-6 flex justify-between items-center flex-wrap gap-3">
                    <Link :href="route('admin.invoices.index')" class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        <ArrowLeftIcon class="w-4 h-4 mr-2" /> Kembali ke Daftar Invoice
                    </Link>

                    <div class="flex items-center gap-2 flex-wrap">
                        <a :href="route('admin.invoices.print', invoice.id)" target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition ease-in-out duration-150">
                            <PrinterIcon class="w-4 h-4 mr-2" /> Print PDF
                        </a>

                        <!-- Tombol Sahkan Invoice — hanya muncul saat status Draft, untuk approver -->
                        <PrimaryButton
                            v-if="invoice.status === 'Draft'"
                            @click="showApprovalModal = true"
                            class="!bg-emerald-600 hover:!bg-emerald-700 flex items-center gap-2"
                        >
                            <ShieldCheckIcon class="w-4 h-4" />
                            Sahkan Invoice
                        </PrimaryButton>

                        <!-- Tombol Void — hanya muncul saat status Unpaid/Partial -->
                        <SecondaryButton
                            v-if="invoice.status === 'Unpaid' || invoice.status === 'Partial'"
                            @click="confirmCancel"
                            class="!text-red-600 !border-red-600 hover:!bg-red-50"
                        >
                            Batalkan (Void)
                        </SecondaryButton>

                        <!-- Tombol Catat Pembayaran — hanya saat Unpaid atau Partial -->
                        <PrimaryButton
                            v-if="invoice.status === 'Unpaid' || invoice.status === 'Partial'"
                            @click="openPaymentModal"
                        >
                            <BanknotesIcon class="w-4 h-4 mr-2" /> Catat Pembayaran
                        </PrimaryButton>
                    </div>
                </div>

                <!-- BANNER: Draft Status Alert -->
                <div v-if="invoice.status === 'Draft'" class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
                    <ClockIcon class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-semibold text-blue-800">Invoice Menunggu Persetujuan</p>
                        <p class="text-sm text-blue-600 mt-1">
                            Invoice ini masih berstatus Draft. GL belum diposting dan invoice belum sah dikirim ke pelanggan.
                            Approver yang ditunjuk harus menyahkan invoice ini sebelum dapat dicatat pembayarannya.
                        </p>
                    </div>
                </div>

                <!-- Main Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left: Invoice Details (2/3) -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <!-- Header Invoice -->
                            <div class="flex justify-between mb-6">
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ invoice.nomor_invoice }}</h1>
                                    <p class="text-sm text-gray-500">Tanggal: {{ formatDate(invoice.tgl_invoice) }}</p>
                                </div>
                                <div class="text-right">
                                    <span :class="statusClass" class="px-3 py-1.5 inline-flex items-center gap-1.5 text-sm leading-5 font-semibold rounded-full border">
                                        {{ statusLabel }}
                                    </span>
                                    <p class="mt-2 text-sm text-red-500">Jatuh Tempo: {{ formatDate(invoice.tgl_jatuh_tempo) }}</p>
                                </div>
                            </div>

                            <!-- Buyer & Financial Summary -->
                            <div class="grid grid-cols-2 gap-4 mb-6 border-t border-b py-4 border-gray-200 dark:border-gray-700">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Kepada</h4>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ invoice.pelanggan?.nama_pelanggan }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ invoice.pelanggan?.alamat }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ invoice.pelanggan?.telepon }}</p>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Subtotal</h4>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ formatCurrency(invoice.subtotal) }}</p>

                                    <div v-if="invoice.ppn_amount > 0" class="mt-2">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">PPN ({{ invoice.ppn_rate }}%)</h4>
                                        <p class="font-bold text-gray-900 dark:text-white">{{ formatCurrency(invoice.ppn_amount) }}</p>
                                    </div>

                                    <div class="mt-4 border-t pt-2 border-gray-200 dark:border-gray-700">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Total Nilai Invoice</h4>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(invoice.total_tagihan) }}</p>
                                    </div>

                                    <div v-if="invoice.pph_amount > 0" class="mt-2 text-red-600">
                                        <h4 class="text-xs font-bold uppercase mb-1">Dikurangi: PPh ({{ invoice.pph_rate }}%)</h4>
                                        <p class="font-bold">- {{ formatCurrency(invoice.pph_amount) }}</p>
                                        <p class="text-xs italic text-gray-500">Bukti Potong PPh Wajib Diminta</p>
                                    </div>

                                    <div class="mt-4 border-t-2 border-indigo-100 pt-2 bg-indigo-50 p-2 rounded">
                                        <h4 class="text-xs font-bold text-indigo-600 uppercase mb-1">Jumlah yang Harus Dibayar</h4>
                                        <p class="text-2xl font-bold text-indigo-700">{{ formatCurrency(invoice.total_tagihan - invoice.pph_amount) }}</p>
                                        <p class="text-xs text-indigo-500">Sisa Tagihan: {{ formatCurrency(invoice.sisa_tagihan) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Item Table -->
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">Rincian Item</h3>
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 mb-6">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Harga</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="item in invoice.detail" :key="item.id">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                            {{ item.deskripsi_item }}
                                            <div class="text-xs text-gray-500">{{ item.akun_pendapatan?.nama_akun }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">{{ item.kuantitas }}</td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(item.harga_satuan) }}</td>
                                        <td class="px-4 py-2 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(item.total_harga) }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div v-if="invoice.catatan" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                                <p class="text-sm text-gray-600 dark:text-gray-300"><span class="font-bold">Catatan:</span> {{ invoice.catatan }}</p>
                            </div>

                            <!-- Approved By Info -->
                            <div v-if="invoice.approvedBy" class="mt-4 bg-emerald-50 border border-emerald-200 rounded-lg p-3 flex items-center gap-2">
                                <CheckBadgeIcon class="w-5 h-5 text-emerald-600 flex-shrink-0" />
                                <p class="text-sm text-emerald-700">
                                    Disahkan oleh <strong>{{ invoice.approvedBy?.name }}</strong>
                                    pada {{ formatDateTime(invoice.approved_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Sidebar (1/3) -->
                    <div class="lg:col-span-1 space-y-6">

                        <!-- APPROVAL TIMELINE CARD -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <ShieldCheckIcon class="w-5 h-5 text-indigo-500" />
                                Status Persetujuan
                            </h3>

                            <!-- Belum ada proses approval -->
                            <p v-if="approvalSteps.length === 0" class="text-sm text-gray-500 italic">
                                {{ invoice.status === 'Draft' ? 'Belum ada rule persetujuan yang dikonfigurasi untuk Invoice.' : 'Tidak ada data persetujuan.' }}
                            </p>

                            <!-- Timeline Steps -->
                            <div v-else class="relative">
                                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                                <div class="space-y-4">
                                    <div v-for="step in approvalSteps" :key="step.id" class="relative pl-10">
                                        <div :class="[
                                            'absolute left-2.5 top-1.5 w-3 h-3 rounded-full border-2',
                                            step.status === 'Approved' ? 'bg-green-500 border-green-500' :
                                            step.status === 'Rejected' ? 'bg-red-500 border-red-500' :
                                            step.status === 'Skipped'  ? 'bg-gray-300 border-gray-300' :
                                            'bg-white border-yellow-400'
                                        ]"></div>

                                        <div :class="['rounded-lg border p-3 text-sm', stepStatusClass(step.status)]">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="font-semibold">{{ step.label_aksi || `Level ${step.level_order}` }}</p>
                                                    <p class="text-xs opacity-80 mt-0.5">{{ step.karyawan_target?.nama_lengkap }}</p>
                                                </div>
                                                <span class="text-[10px] font-bold uppercase tracking-wide opacity-70">{{ step.status }}</span>
                                            </div>
                                            <p v-if="step.catatan" class="text-xs mt-2 italic opacity-70">"{{ step.catatan }}"</p>
                                            <p v-if="step.approved_at" class="text-[10px] mt-1 opacity-50">{{ formatDateTime(step.approved_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">Riwayat Pembayaran</h3>

                            <div v-if="invoice.pembayaran && invoice.pembayaran.length > 0" class="space-y-4">
                                <div v-for="pay in invoice.pembayaran" :key="pay.id" class="border-l-4 border-green-500 pl-4 py-2">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ formatCurrency(pay.nominal_bayar) }}</p>
                                    <p class="text-xs text-gray-500">{{ formatDate(pay.tgl_bayar) }}</p>
                                    <p class="text-xs text-gray-500">Via: {{ pay.kas_bank?.nama_bank }}</p>
                                    <button v-if="pay.bukti_bayar_path" @click="setPreviewFile(pay.bukti_bayar_path)" class="text-xs text-indigo-600 hover:underline mt-1 block font-bold">Lihat Bukti</button>
                                </div>
                            </div>
                            <p v-else class="text-sm text-gray-500 italic">Belum ada pembayaran.</p>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- File Preview Modal -->
        <FilePreviewModal
            :show="showFilePreview"
            :file-url="activeFileUrl"
            :file-type="activeFileType"
            :file-name="activeFileName"
            @close="showFilePreview = false"
        />

        <!-- Approval Confirmation Modal -->
        <Modal :show="showApprovalModal" @close="showApprovalModal = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <ShieldCheckIcon class="w-6 h-6 text-emerald-600" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Sahkan Invoice</h2>
                        <p class="text-sm text-gray-500">{{ invoice.nomor_invoice }} — {{ formatCurrency(invoice.total_tagihan) }}</p>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-amber-800">
                        ⚠️ Setelah disahkan, jurnal GL akan diposting secara otomatis dan invoice akan berstatus
                        <strong>Unpaid</strong> (aktif). Tindakan ini tidak dapat dibatalkan kecuali melalui proses Void.
                    </p>
                </div>

                <form @submit.prevent="submitApproval">
                    <div class="mb-4">
                        <InputLabel value="Catatan Persetujuan (Opsional)" />
                        <textarea
                            v-model="approvalForm.catatan"
                            rows="3"
                            placeholder="Tambahkan catatan jika diperlukan..."
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="showApprovalModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="approvalForm.processing" class="!bg-emerald-600 hover:!bg-emerald-700">
                            <ShieldCheckIcon class="w-4 h-4 mr-2" />
                            {{ approvalForm.processing ? 'Memproses...' : 'Sahkan Sekarang' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Payment Modal -->
        <Modal :show="showPaymentModal" @close="showPaymentModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Catat Pembayaran Masuk</h2>

                <form @submit.prevent="submitPayment">
                    <div class="mb-4">
                        <InputLabel value="Tanggal Bayar" />
                        <TextInput type="date" v-model="paymentForm.tgl_bayar" class="mt-1 block w-full" required />
                    </div>
                    <div class="mb-4">
                        <InputLabel value="Nominal Bayar" />
                        <TextInput type="number" v-model="paymentForm.nominal_bayar" class="mt-1 block w-full" :max="invoice.sisa_tagihan" required />
                        <p class="text-xs text-gray-500 mt-1">Maksimal: {{ formatCurrency(invoice.sisa_tagihan) }}</p>
                    </div>
                    <div class="mb-4">
                        <InputLabel value="Masuk ke Kas/Bank" />
                        <select v-model="paymentForm.id_kas_bank" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="" disabled>Pilih Bank</option>
                            <option v-for="bank in kasBanks" :key="bank.id" :value="bank.id">{{ bank.nama_bank }} ({{ bank.nomor_rekening }})</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <InputLabel value="Bukti Pembayaran (Opsional)" />
                        <input type="file" @input="paymentForm.bukti_bayar = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 dark:border-gray-600 rounded-md p-1 bg-white dark:bg-gray-700 cursor-pointer focus:outline-none" />
                        <InputError class="mt-2" :message="paymentForm.errors.bukti_bayar" />
                    </div>
                    <div class="mb-6">
                        <InputLabel value="Catatan" />
                        <TextInput v-model="paymentForm.catatan" class="mt-1 block w-full" />
                    </div>

                    <div class="flex justify-end gap-4">
                        <SecondaryButton @click="showPaymentModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="paymentForm.processing">Simpan Pembayaran</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
