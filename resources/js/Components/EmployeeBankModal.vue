<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    employeeId: Number, // ID Karyawan yang akan ditambahkan banknya
    employeeName: String
});

const emit = defineEmits(['close', 'bank-created']);

const form = ref({
    nama_bank: '',
    nomor_rekening: '',
    atas_nama_rekening: '',
    cabang: '',
});

const errors = ref({});
const processing = ref(false);

// Reset form saat modal dibuka/ditutup
watch(() => props.show, (val) => {
    if (!val) resetForm();
    else if (props.employeeName) {
        // Auto-fill atas nama dengan nama karyawan
        form.value.atas_nama_rekening = props.employeeName;
    }
});

const submit = async () => {
    if (!props.employeeId) {
        alert("Pilih karyawan terlebih dahulu.");
        return;
    }

    processing.value = true;
    errors.value = {};

    try {
        const payload = { ...form.value, id_karyawan: props.employeeId };
        const response = await axios.post(route('admin.pengajuan.storeEmployeeBank'), payload);
        
        emit('bank-created', response.data.bank);
        emit('close');
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error(error);
            alert('Gagal menyimpan rekening.');
        }
    } finally {
        processing.value = false;
    }
};

const resetForm = () => {
    form.value = { nama_bank: '', nomor_rekening: '', atas_nama_rekening: '', cabang: '' };
    errors.value = {};
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                Tambah Rekening: {{ employeeName }}
            </h2>
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Nama Bank" />
                        <TextInput v-model="form.nama_bank" class="w-full mt-1" placeholder="BCA / Mandiri" />
                        <div v-if="errors.nama_bank" class="text-red-600 text-sm mt-1">{{ errors.nama_bank[0] }}</div>
                    </div>
                    <div>
                        <InputLabel value="Nomor Rekening" />
                        <TextInput v-model="form.nomor_rekening" class="w-full mt-1" type="text" />
                        <div v-if="errors.nomor_rekening" class="text-red-600 text-sm mt-1">{{ errors.nomor_rekening[0] }}</div>
                    </div>
                </div>

                <div>
                    <InputLabel value="Atas Nama Rekening" />
                    <TextInput v-model="form.atas_nama_rekening" class="w-full mt-1" />
                    <div v-if="errors.atas_nama_rekening" class="text-red-600 text-sm mt-1">{{ errors.atas_nama_rekening[0] }}</div>
                </div>
                
                <div>
                    <InputLabel value="Cabang (Opsional)" />
                    <TextInput v-model="form.cabang" class="w-full mt-1" />
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <SecondaryButton @click="$emit('close')">Batal</SecondaryButton>
                <PrimaryButton @click="submit" :disabled="processing">
                    {{ processing ? 'Menyimpan...' : 'Simpan Rekening' }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>