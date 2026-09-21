<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { DocumentTextIcon, EyeIcon, BanknotesIcon, DocumentDuplicateIcon } from '@heroicons/vue/24/solid';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    form: Object,
    isTaxPaymentLocked: Boolean,
    frontendErrors: Object,
    masterKasKecil: { type: Array, default: () => [] }, // List KasBank type kas kecil
    isFinance: Boolean,
});

const emit = defineEmits(['update:attachment']);

const showFilePreview = ref(false);
const previewUrl = ref('');
const fileInputRef = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        props.form.attachment = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const handlePaste = (e) => {
    // Hanya proses paste jika form membutuhkan lampiran atau form bertipe PettyCash
    if (!needsAttachment() && props.form.tipe_pengajuan !== 'PettyCash') return;

    const items = e.clipboardData?.items;
    if (!items) return;

    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
            const file = items[i].getAsFile();
            if (file) {
                const ext = file.type.split('/')[1] || 'png';
                const newFile = new File([file], `paste_${Date.now()}.${ext}`, { type: file.type });
                props.form.attachment = newFile;
                previewUrl.value = URL.createObjectURL(newFile);
                
                // Update file input visually
                if (fileInputRef.value) {
                    const dt = new DataTransfer();
                    dt.items.add(newFile);
                    fileInputRef.value.files = dt.files;
                }
                
                e.preventDefault();
                break;
            }
        }
    }
};

onMounted(() => {
    window.addEventListener('paste', handlePaste);
});

onUnmounted(() => {
    window.removeEventListener('paste', handlePaste);
});

const isPettyCash = () => props.form.tipe_pengajuan === 'PettyCash';
const needsAttachment = () => ['Langsung', 'TaxPayment'].includes(props.form.tipe_pengajuan);
</script>

<template>
    <div class="border-b border-gray-200 pb-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
            <DocumentTextIcon class="w-5 h-5 mr-2 text-indigo-500"/>
            Informasi Dasar
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Kolom Kiri -->
            <div class="space-y-5">
                <div>
                    <InputLabel for="judul" value="Judul Pengajuan" class="font-bold" />
                    <TextInput id="judul" v-model="form.judul_pengajuan" class="w-full mt-1" placeholder="Contoh: Biaya Perjalanan Dinas ke Bali" />
                    <InputError class="mt-1" :message="frontendErrors['judul_pengajuan'] || form.errors.judul_pengajuan" />
                </div>

                <div>
                    <InputLabel for="tipe" value="Kategori Pengajuan" class="font-bold" />
                    <select
                        id="tipe"
                        v-model="form.tipe_pengajuan"
                        :disabled="isTaxPaymentLocked"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                    >
                        <option value="Langsung">Pembayaran Langsung (Invoice/Reimburse)</option>
                        <option value="UangMuka">Permintaan Uang Muka (Cash Advance)</option>
                        <option v-if="isFinance" value="PettyCash">Petty Cash (Kas Kecil)</option>
                        <option v-if="isTaxPaymentLocked" value="TaxPayment">Pembayaran Pajak (Tax Payment)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        <span v-if="form.tipe_pengajuan === 'Langsung'">Untuk tagihan vendor atau reimburse biaya.</span>
                        <span v-else-if="form.tipe_pengajuan === 'UangMuka'">Untuk meminta dana di muka sebelum kegiatan.</span>
                        <span v-else-if="form.tipe_pengajuan === 'TaxPayment'">Pelunasan kewajiban pajak ke Kas Negara.</span>
                        <span v-else-if="form.tipe_pengajuan === 'PettyCash'" class="text-amber-600 font-medium">
                            Pengeluaran langsung dari kas kecil. GL akan diposting otomatis saat disetujui.
                        </span>
                    </p>
                </div>

                <!-- Pilih Akun Kas Kecil — hanya muncul saat PettyCash -->
                <div v-if="form.tipe_pengajuan === 'PettyCash'">
                    <InputLabel value="Akun Kas Kecil" class="font-bold" />
                    <div class="mt-1 flex items-center gap-2">
                        <BanknotesIcon class="w-5 h-5 text-amber-500 flex-shrink-0" />
                        <select
                            v-model="form.id_kas_kecil"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="null" disabled>— Pilih Kas Kecil —</option>
                            <option v-for="kb in masterKasKecil" :key="kb.id" :value="kb.id">
                                {{ kb.nama_bank }} (Saldo: {{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(kb.saldo_gl ?? 0) }})
                            </option>
                        </select>
                    </div>
                    <InputError class="mt-1" :message="frontendErrors['id_kas_kecil'] || form.errors.id_kas_kecil" />
                    <p class="text-xs text-amber-600 mt-1">Pastikan saldo kas kecil mencukupi sebelum mengajukan.</p>
                </div>

                <div>
                    <InputLabel value="Metode Penyaluran Dana" />
                    <select v-model="form.metode_pembayaran" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="Transfer">Transfer Bank</option>
                        <option value="Cash">Tunai / Kas Kecil</option>
                    </select>
                </div>
            </div>

            <!-- Kolom Kanan -->
            <div class="space-y-5">
                <div>
                    <InputLabel value="Tanggal Pengajuan" />
                    <TextInput type="date" v-model="form.tgl_pengajuan" class="w-full mt-1" />
                    <InputError :message="frontendErrors['tgl_pengajuan'] || form.errors.tgl_pengajuan" />
                </div>

                <!-- Lampiran: wajib untuk Langsung & TaxPayment, opsional untuk PettyCash -->
                <div v-if="needsAttachment() || form.tipe_pengajuan === 'PettyCash'">
                    <InputLabel
                        :value="form.tipe_pengajuan === 'TaxPayment' ? 'Bukti Tagihan Pajak / SSP'
                               : form.tipe_pengajuan === 'PettyCash' ? 'Nota / Kuitansi (Opsional)'
                               : 'Lampiran Bukti (Faktur/Nota)'"
                        :class="{ 'font-bold': needsAttachment() }"
                    />
                    <div class="flex flex-col gap-2 mt-1">
                        <div class="flex gap-2 items-center">
                            <input
                                type="file"
                                ref="fileInputRef"
                                @change="handleFileChange"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md p-1"
                            />
                            <SecondaryButton v-if="previewUrl" type="button" @click="$emit('preview-file', { url: previewUrl, type: form.attachment?.type?.includes('pdf') ? 'pdf' : 'image', name: form.attachment?.name })" title="Lihat Preview">
                                <EyeIcon class="w-5 h-5"/>
                            </SecondaryButton>
                        </div>
                        <p class="text-xs text-gray-500 flex items-center">
                            <DocumentDuplicateIcon class="w-3 h-3 mr-1" />
                            <em>Tip: Anda bisa langsung <kbd class="bg-gray-100 border border-gray-300 rounded px-1">Ctrl+V</kbd> / Paste gambar dari clipboard</em>
                        </p>
                    </div>
                    <InputError class="mt-1" :message="frontendErrors['attachment'] || form.errors.attachment" />
                </div>

                <div v-else class="p-3 bg-blue-50 text-blue-700 rounded text-sm border border-blue-100">
                    <span class="font-bold">Info:</span> Bukti transaksi (bon/kuitansi) disusulkan nanti saat laporan pertanggungjawaban (Settlement).
                </div>

                <!-- PettyCash info banner -->
                <div v-if="form.tipe_pengajuan === 'PettyCash'" class="p-3 bg-amber-50 border border-amber-200 rounded-md text-sm">
                    <p class="font-semibold text-amber-800 mb-1">📋 Alur Petty Cash:</p>
                    <ol class="list-decimal ml-4 text-amber-700 space-y-1 text-xs">
                        <li>Ajukan pengajuan → tunggu persetujuan Finance Manager</li>
                        <li>Saat disetujui, GL otomatis: Dr. Beban / Cr. Kas Kecil</li>
                        <li>Status langsung PAID (tidak perlu catat pembayaran manual)</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</template>
