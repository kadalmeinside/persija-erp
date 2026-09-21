<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({ show: Boolean });
const emit = defineEmits(['close', 'vendor-created']);

const form = ref({
    nama_vendor: '',
    nama_bank: '',
    nomor_rekening: '',
    atas_nama_rekening: '',
    no_telp: '',
});

const errors = ref({});
const processing = ref(false);

const submit = async () => {
    processing.value = true;
    errors.value = {};
    try {
        // Endpoint ini akan memecah data ke tbl_vendor dan tbl_rekening_bank
        const response = await axios.post(route('admin.pengajuan.storeVendor'), form.value);
        emit('vendor-created', response.data.vendor);
        resetForm();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error(error);
            alert('Gagal menyimpan vendor.');
        }
    } finally {
        processing.value = false;
    }
};

const resetForm = () => {
    form.value = { nama_vendor: '', nama_bank: '', nomor_rekening: '', atas_nama_rekening: '', no_telp: '' };
    errors.value = {};
};
const close = () => { resetForm(); emit('close'); };
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Tambah Vendor Baru</h2>
            <div class="space-y-4">
                <div>
                    <InputLabel value="Nama Vendor / Penerima" />
                    <TextInput v-model="form.nama_vendor" class="w-full mt-1" placeholder="PT. Contoh Sejahtera" />
                    <div v-if="errors.nama_vendor" class="text-red-600 text-sm mt-1">{{ errors.nama_vendor[0] }}</div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Nama Bank" />
                        <TextInput v-model="form.nama_bank" class="w-full mt-1" placeholder="BCA / Mandiri" />
                        <div v-if="errors.nama_bank" class="text-red-600 text-sm mt-1">{{ errors.nama_bank[0] }}</div>
                    </div>
                    <div>
                        <InputLabel value="Nomor Rekening" />
                        <!-- PERBAIKAN: Ganti type="number" ke type="text" agar dianggap string -->
                        <TextInput v-model="form.nomor_rekening" class="w-full mt-1" type="text" placeholder="Contoh: 0123456789" />
                        <div v-if="errors.nomor_rekening" class="text-red-600 text-sm mt-1">{{ errors.nomor_rekening[0] }}</div>
                    </div>
                </div>
                <div>
                    <InputLabel value="Atas Nama Rekening" />
                    <TextInput v-model="form.atas_nama_rekening" class="w-full mt-1" />
                    <div v-if="errors.atas_nama_rekening" class="text-red-600 text-sm mt-1">{{ errors.atas_nama_rekening[0] }}</div>
                </div>
                <div>
                    <InputLabel value="No. Telp (Opsional)" />
                    <TextInput v-model="form.no_telp" class="w-full mt-1" />
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <SecondaryButton @click="close">Batal</SecondaryButton>
                <PrimaryButton @click="submit" :disabled="processing">{{ processing ? 'Menyimpan...' : 'Simpan Vendor' }}</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>