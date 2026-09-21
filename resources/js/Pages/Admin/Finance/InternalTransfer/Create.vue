<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { ArrowLeftIcon, ArrowsRightLeftIcon, ExclamationTriangleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    kasBanks: Array,
});

const form = useForm({
    tgl_transfer:     new Date().toISOString().split('T')[0],
    from_kas_bank_id: '',
    to_kas_bank_id:   '',
    nominal:          '',
    keterangan:       '',
});

const formatCurrency = (v) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v || 0);

// rekening asal dan tujuan harus berbeda
const toBankOptions = computed(() =>
    props.kasBanks.filter(kb => kb.id != form.from_kas_bank_id)
);

const fromBankOptions = computed(() =>
    props.kasBanks.filter(kb => kb.id != form.to_kas_bank_id)
);

const selectedFrom = computed(() => props.kasBanks.find(kb => kb.id == form.from_kas_bank_id));
const selectedTo   = computed(() => props.kasBanks.find(kb => kb.id == form.to_kas_bank_id));

// Saldo GL rekening asal
const saldoFrom = computed(() => selectedFrom.value?.saldo_gl ?? null);

// Warning jika nominal melebihi saldo
const isOverBalance = computed(() =>
    saldoFrom.value !== null &&
    form.nominal > 0 &&
    parseFloat(form.nominal) > saldoFrom.value
);

const submit = () => {
    form.post(route('admin.internal-transfers.store'));
};
</script>

<template>
    <Head title="Buat Internal Transfer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <ArrowsRightLeftIcon class="w-6 h-6 text-indigo-500" />
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Buat Internal Transfer</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto sm:px-6">

                <!-- Back link -->
                <Link :href="route('admin.internal-transfers.index')" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 text-sm">
                    <ArrowLeftIcon class="w-4 h-4" /> Kembali ke Daftar Transfer
                </Link>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-8">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Transfer Antar Rekening</h3>
                    <p class="text-sm text-gray-500 mb-6">Transfer ini akan menunggu persetujuan Finance Manager sebelum GL diposting.</p>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Tanggal -->
                        <div>
                            <InputLabel value="Tanggal Transfer" class="font-semibold" />
                            <TextInput type="date" v-model="form.tgl_transfer" class="mt-1 w-full" required />
                            <InputError :message="form.errors.tgl_transfer" />
                        </div>

                        <!-- From → To Selector -->
                        <div class="grid grid-cols-1 gap-4">
                            <!-- Rekening Asal -->
                            <div>
                                <InputLabel value="Rekening Asal (Dari)" class="font-semibold" />
                                <select v-model="form.from_kas_bank_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled>— Pilih Rekening Asal —</option>
                                    <option v-for="kb in fromBankOptions" :key="kb.id" :value="kb.id">
                                        {{ kb.nama_bank }} ({{ kb.nomor_rekening }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.from_kas_bank_id" />

                                <!-- Saldo Rekening Asal -->
                                <div v-if="selectedFrom" class="mt-2 rounded-lg px-3 py-2 text-sm border"
                                     :class="saldoFrom !== null && saldoFrom < (form.nominal || 0)
                                        ? 'bg-red-50 border-red-200 text-red-700'
                                        : 'bg-green-50 border-green-200 text-green-700'"
                                >
                                    <span class="font-medium">Saldo Tersedia (GL): </span>
                                    <span class="font-bold">{{ saldoFrom !== null ? formatCurrency(saldoFrom) : 'Tidak tersambung ke GL' }}</span>
                                </div>
            </div>

                            <!-- Arrow Visual -->
                            <div class="flex items-center justify-center">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <ArrowsRightLeftIcon class="w-5 h-5 text-indigo-600" />
                                </div>
                            </div>

                            <!-- Rekening Tujuan -->
                            <div>
                                <InputLabel value="Rekening Tujuan (Ke)" class="font-semibold" />
                                <select v-model="form.to_kas_bank_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="" disabled>— Pilih Rekening Tujuan —</option>
                                    <option v-for="kb in toBankOptions" :key="kb.id" :value="kb.id">
                                        {{ kb.nama_bank }} ({{ kb.nomor_rekening }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.to_kas_bank_id" />
                                <p v-if="selectedTo" class="text-xs text-gray-500 mt-1">
                                    Rekening terpilih: <strong>{{ selectedTo.nama_bank }}</strong>
                                </p>
                            </div>
                        </div>

                        <!-- Preview Arah Transfer -->
                        <div v-if="selectedFrom && selectedTo" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                            <p class="text-sm text-center text-indigo-700 font-medium">
                                {{ selectedFrom.nama_bank }}
                                <span class="mx-2 text-indigo-400">→</span>
                                {{ selectedTo.nama_bank }}
                            </p>
                            <p class="text-xs text-center text-indigo-500 mt-1">GL: Dr. {{ selectedTo.nama_bank }} | Cr. {{ selectedFrom.nama_bank }}</p>
                        </div>

                        <!-- Nominal -->
                        <div>
                            <InputLabel value="Nominal Transfer (Rp)" class="font-semibold" />
                            <TextInput
                                type="number"
                                v-model="form.nominal"
                                min="1"
                                step="1000"
                                class="mt-1 w-full"
                                :class="isOverBalance ? 'border-red-400 ring-1 ring-red-400' : ''"
                                placeholder="0"
                                required
                            />
                            <p v-if="form.nominal" class="text-xs text-gray-500 mt-1">{{ formatCurrency(form.nominal) }}</p>
                            <InputError :message="form.errors.nominal" />

                            <!-- Soft Warning: Over Balance -->
                            <div v-if="isOverBalance" class="mt-2 flex items-start gap-2 bg-amber-50 border border-amber-300 rounded-lg p-3">
                                <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm font-semibold text-amber-800">Nominal Melebihi Saldo Tersedia</p>
                                    <p class="text-xs text-amber-700 mt-0.5">
                                        Transfer ini <strong>melebihi saldo GL</strong> rekening asal ({{ formatCurrency(saldoFrom) }}).
                                        Draft tetap bisa disimpan, namun Finance Manager <strong>tidak akan bisa menyetujui</strong> transfer ini
                                        hingga saldo mencukupi.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Keterangan -->
                        <div>
                            <InputLabel value="Keterangan / Tujuan Transfer" />
                            <textarea
                                v-model="form.keterangan"
                                rows="3"
                                placeholder="Contoh: Transfer dana operasional cabang ke rekening induk..."
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            ></textarea>
                            <InputError :message="form.errors.keterangan" />
                        </div>

                        <!-- Info Box -->
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                            <p class="font-semibold mb-1">ℹ️ Proses Approval:</p>
                            <ul class="list-disc ml-4 space-y-0.5 text-xs text-amber-700">
                                <li>Transfer akan dibuat dengan status <strong>Draft</strong></li>
                                <li>Finance Manager perlu menyetujui di halaman Detail Transfer</li>
                                <li>GL akan diposting otomatis setelah disetujui</li>
                            </ul>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end gap-3 pt-2">
                            <Link :href="route('admin.internal-transfers.index')">
                                <SecondaryButton type="button">Batal</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan...' : 'Buat Transfer' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
