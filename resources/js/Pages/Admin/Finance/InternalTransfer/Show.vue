<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import {
    ArrowLeftIcon,
    ArrowRightIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    ShieldCheckIcon,
    BookOpenIcon,
} from '@heroicons/vue/24/outline';
import { ArrowsRightLeftIcon, CheckBadgeIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    transfer: Object,
});

const formatCurrency = (v) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(v || 0);

const formatDate = (d) =>
    d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';

const formatDateTime = (d) =>
    d ? new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';

// ---- STATUS ----
const statusConfig = computed(() => {
    const map = {
        Draft:      { label: 'Draft — Menunggu Persetujuan', class: 'bg-blue-50 text-blue-800 border-blue-200',   icon: ClockIcon },
        Approved:   { label: 'Disetujui',                    class: 'bg-green-50 text-green-800 border-green-200', icon: CheckCircleIcon },
        Cancelled:  { label: 'Dibatalkan',                   class: 'bg-gray-50 text-gray-600 border-gray-200',   icon: XCircleIcon },
    };
    return map[props.transfer.status] || { label: props.transfer.status, class: 'bg-gray-100 text-gray-600 border-gray-200', icon: ClockIcon };
});

const isDraft = computed(() => props.transfer.status === 'Draft');

// ---- APPROVAL MODAL ----
const showApprovalModal = ref(false);
const approvalForm = useForm({ catatan: '' });

const submitApproval = () => {
    approvalForm.post(route('admin.internal-transfers.approve', props.transfer.id), {
        onSuccess: () => showApprovalModal.value = false,
    });
};

// ---- CANCEL ----
const confirmCancel = () => {
    if (confirm('Apakah Anda yakin ingin membatalkan transfer ini? Transfer yang sudah Draft akan dihapus dari antrean.')) {
        router.post(route('admin.internal-transfers.cancel', props.transfer.id));
    }
};
</script>

<template>
    <Head :title="`Transfer ${transfer.nomor_transfer}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <ArrowsRightLeftIcon class="w-6 h-6 text-indigo-500" />
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Detail Internal Transfer</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 space-y-6">

                <!-- Header Bar -->
                <div class="flex justify-between items-center flex-wrap gap-3">
                    <Link :href="route('admin.internal-transfers.index')" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900">
                        <ArrowLeftIcon class="w-4 h-4" /> Kembali
                    </Link>

                    <div class="flex gap-2 flex-wrap">
                        <!-- Sahkan Transfer -->
                        <PrimaryButton
                            v-if="isDraft"
                            @click="showApprovalModal = true"
                            class="!bg-emerald-600 hover:!bg-emerald-700 flex items-center gap-2"
                        >
                            <ShieldCheckIcon class="w-4 h-4" /> Setujui Transfer
                        </PrimaryButton>

                        <!-- Batalkan -->
                        <SecondaryButton
                            v-if="isDraft"
                            @click="confirmCancel"
                            class="!text-red-600 !border-red-600 hover:!bg-red-50"
                        >
                            Batalkan Transfer
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Status Banner -->
                <div :class="['rounded-xl border p-4 flex items-center gap-3', statusConfig.class]">
                    <component :is="statusConfig.icon" class="w-6 h-6 flex-shrink-0" />
                    <div>
                        <p class="font-bold">{{ statusConfig.label }}</p>
                        <p v-if="isDraft" class="text-xs mt-0.5 opacity-75">GL belum diposting. Transfer akan efektif setelah disetujui oleh Finance Manager.</p>
                        <p v-else-if="transfer.status === 'Approved'" class="text-xs mt-0.5 opacity-75">
                            Disetujui oleh <strong>{{ transfer.approver?.name }}</strong> pada {{ formatDateTime(transfer.approved_at) }}
                        </p>
                    </div>
                </div>

                <!-- Main Info Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ transfer.nomor_transfer }}</h3>
                            <p class="text-sm text-gray-500">Tanggal: {{ formatDate(transfer.tgl_transfer) }}</p>
                            <p class="text-xs text-gray-400">Dibuat oleh: {{ transfer.creator?.name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Nominal Transfer</p>
                            <p class="text-3xl font-bold text-indigo-700">{{ formatCurrency(transfer.nominal) }}</p>
                        </div>
                    </div>

                    <!-- Transfer Flow Visualization -->
                    <div class="grid grid-cols-[1fr_auto_1fr] gap-4 items-center bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6">
                        <!-- From Account -->
                        <div class="text-center">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <span class="text-red-600 font-bold text-lg">−</span>
                            </div>
                            <p class="font-bold text-gray-900 dark:text-white text-sm">{{ transfer.from_kas_bank?.nama_bank }}</p>
                            <p class="text-xs text-gray-500">{{ transfer.from_kas_bank?.nomor_rekening }}</p>
                            <p class="text-xs italic text-gray-400 mt-1">Kredit: {{ formatCurrency(transfer.nominal) }}</p>
                        </div>

                        <!-- Arrow -->
                        <div class="flex flex-col items-center gap-1">
                            <ArrowRightIcon class="w-8 h-8 text-indigo-500" />
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider">Transfer</span>
                        </div>

                        <!-- To Account -->
                        <div class="text-center">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <span class="text-green-600 font-bold text-lg">+</span>
                            </div>
                            <p class="font-bold text-gray-900 dark:text-white text-sm">{{ transfer.to_kas_bank?.nama_bank }}</p>
                            <p class="text-xs text-gray-500">{{ transfer.to_kas_bank?.nomor_rekening }}</p>
                            <p class="text-xs italic text-gray-400 mt-1">Debit: {{ formatCurrency(transfer.nominal) }}</p>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div v-if="transfer.keterangan" class="mt-4 bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Keterangan</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ transfer.keterangan }}</p>
                    </div>
                </div>

                <!-- GL Journal Card (hanya jika sudah Approved) -->
                <div v-if="transfer.status === 'Approved'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h4 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <BookOpenIcon class="w-5 h-5 text-indigo-500" />
                        Jurnal GL yang Diposting
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center border-b pb-2">
                            <div>
                                <span class="font-medium text-gray-700">Dr.</span>
                                <span class="ml-2 text-gray-800 dark:text-gray-200">{{ transfer.to_kas_bank?.nama_bank }}</span>
                                <span class="ml-2 text-xs text-gray-400">({{ transfer.to_kas_bank?.akun_gl?.kode_akun }})</span>
                            </div>
                            <span class="font-bold text-green-700">{{ formatCurrency(transfer.nominal) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="ml-4">
                                <span class="font-medium text-gray-700">Cr.</span>
                                <span class="ml-2 text-gray-800 dark:text-gray-200">{{ transfer.from_kas_bank?.nama_bank }}</span>
                                <span class="ml-2 text-xs text-gray-400">({{ transfer.from_kas_bank?.akun_gl?.kode_akun }})</span>
                            </div>
                            <span class="font-bold text-red-600">{{ formatCurrency(transfer.nominal) }}</span>
                        </div>
                    </div>

                    <div v-if="transfer.id_jurnal" class="mt-3 pt-3 border-t text-xs text-gray-400 flex items-center gap-1">
                        <CheckBadgeIcon class="w-4 h-4 text-green-500" />
                        Jurnal #{{ transfer.id_jurnal }} telah diposting pada {{ formatDateTime(transfer.approved_at) }}
                    </div>
                </div>

            </div>
        </div>

        <!-- Approval Confirmation Modal -->
        <Modal :show="showApprovalModal" @close="showApprovalModal = false">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                        <ShieldCheckIcon class="w-6 h-6 text-emerald-600" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">Setujui Internal Transfer</h2>
                        <p class="text-sm text-gray-500">{{ transfer.nomor_transfer }} — {{ formatCurrency(transfer.nominal) }}</p>
                    </div>
                </div>

                <!-- Transfer Summary -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4 grid grid-cols-3 items-center text-sm text-center gap-2">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Dari</p>
                        <p class="font-semibold text-gray-800">{{ transfer.from_kas_bank?.nama_bank }}</p>
                    </div>
                    <ArrowRightIcon class="w-5 h-5 text-gray-400 mx-auto" />
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Ke</p>
                        <p class="font-semibold text-gray-800">{{ transfer.to_kas_bank?.nama_bank }}</p>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-amber-800">
                        ⚠️ Persetujuan ini akan memposting jurnal GL secara otomatis.
                        Rekening <strong>{{ transfer.to_kas_bank?.nama_bank }}</strong> akan didebit dan
                        <strong>{{ transfer.from_kas_bank?.nama_bank }}</strong> akan dikredit sebesar
                        <strong>{{ formatCurrency(transfer.nominal) }}</strong>.
                    </p>
                </div>

                <form @submit.prevent="submitApproval">
                    <div class="mb-5">
                        <InputLabel value="Catatan Persetujuan (Opsional)" />
                        <textarea
                            v-model="approvalForm.catatan"
                            rows="2"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="showApprovalModal = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="approvalForm.processing" class="!bg-emerald-600 hover:!bg-emerald-700">
                            <ShieldCheckIcon class="w-4 h-4 mr-1" />
                            {{ approvalForm.processing ? 'Memproses...' : 'Setujui & Posting GL' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
