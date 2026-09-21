<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputCurrency from '@/Components/InputCurrency.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { DocumentDuplicateIcon, EyeIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    show: Boolean,
    pengajuanId: Number,
    sisaTagihan: Number,
    listKasBank: Array
});

const emit = defineEmits(['close']);

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const form = useForm({
    tgl_bayar: new Date().toISOString().split('T')[0],
    nominal_bayar: 0,
    id_kas_bank: '',
    catatan: '',
    bukti_bayar: null
});

const fileInputRef = ref(null);
const previewUrl = ref('');

// Reset form saat modal dibuka
watch(() => props.show, (val) => {
    if (val) {
        form.reset();
        form.clearErrors();
        form.nominal_bayar = props.sisaTagihan; 
        previewUrl.value = '';
        if(fileInputRef.value) fileInputRef.value.value = null;
    }
});

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.bukti_bayar = file;
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = '';
    }
};

const handlePaste = (e) => {
    if (!props.show) return;

    const items = e.clipboardData?.items;
    if (!items) return;

    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
            const file = items[i].getAsFile();
            if (file) {
                const ext = file.type.split('/')[1] || 'png';
                const newFile = new File([file], `paste_${Date.now()}.${ext}`, { type: file.type });
                form.bukti_bayar = newFile;
                previewUrl.value = URL.createObjectURL(newFile);
                
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

onMounted(() => window.addEventListener('paste', handlePaste));
onUnmounted(() => window.removeEventListener('paste', handlePaste));

const submit = () => {
    form.post(route('admin.pengajuan.pay', props.pengajuanId), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Input Pembayaran (Finance)</h2>
            
            <!-- Info Sisa Tagihan -->
            <div class="mb-4 p-3 bg-blue-50 rounded border border-blue-100 text-sm text-blue-800 flex justify-between items-center">
                <span>Sisa Tagihan saat ini:</span>
                <span class="font-bold text-lg">{{ formatCurrency(sisaTagihan) }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                
                <!-- Tanggal & Sumber Dana -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Tanggal Bayar" />
                        <TextInput type="date" v-model="form.tgl_bayar" class="w-full mt-1" />
                        <InputError :message="form.errors.tgl_bayar" />
                    </div>
                    <div>
                        <InputLabel value="Sumber Dana (Bank Kita)" />
                        <select v-model="form.id_kas_bank" class="w-full border-gray-300 rounded-md shadow-sm mt-1 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="" disabled>Pilih Sumber Dana...</option>
                            <option v-for="bank in listKasBank" :key="bank.id" :value="bank.id">
                                {{ bank.nama_bank }} ({{ bank.nomor_rekening }})
                            </option>
                        </select>
                        <InputError :message="form.errors.id_kas_bank" />
                    </div>
                </div>

                <!-- Nominal -->
                <div>
                    <InputLabel value="Nominal Bayar" />
                    <InputCurrency v-model="form.nominal_bayar" class="mt-1" placeholder="0" />
                    <p class="text-xs text-gray-500 mt-1">
                        Maksimal: {{ formatCurrency(sisaTagihan) }}. <br>
                        <span v-if="form.nominal_bayar < sisaTagihan && form.nominal_bayar > 0" class="text-yellow-600 font-bold">
                            Status akan menjadi: Partial Payment (Belum Lunas)
                        </span>
                        <span v-if="form.nominal_bayar == sisaTagihan" class="text-green-600 font-bold">
                            Status akan menjadi: Paid (Lunas)
                        </span>
                    </p>
                    <InputError :message="form.errors.nominal_bayar" />
                </div>

                <!-- Bukti Upload -->
                <div>
                    <InputLabel value="Bukti Transfer (Struk/Slip)" />
                    <div class="flex flex-col gap-2 mt-1">
                        <div class="flex items-center gap-2">
                            <input type="file" ref="fileInputRef" @change="handleFileChange" accept="image/*,.pdf" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md p-1" />
                            <a v-if="previewUrl" :href="previewUrl" target="_blank" class="p-2 bg-gray-200 hover:bg-gray-300 rounded text-gray-700">
                                <EyeIcon class="w-5 h-5" />
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 flex items-center">
                            <DocumentDuplicateIcon class="w-3 h-3 mr-1" />
                            <em>Tip: Anda bisa langsung <kbd class="bg-gray-100 border border-gray-300 rounded px-1">Ctrl+V</kbd> / Paste gambar dari clipboard</em>
                        </p>
                    </div>
                    <InputError :message="form.errors.bukti_bayar" />
                </div>

                <!-- Catatan -->
                <div>
                    <InputLabel value="Catatan Pembayaran" />
                    <TextInput v-model="form.catatan" class="w-full mt-1" placeholder="No Ref Bank / Keterangan lain..." />
                </div>

                <div class="mt-6 flex justify-end space-x-3 pt-4 border-t">
                    <SecondaryButton @click="$emit('close')" :disabled="form.processing">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing || form.nominal_bayar <= 0">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Pembayaran' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>