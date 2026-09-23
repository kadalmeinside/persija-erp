<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    employee: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    gaji_pokok: '',
    status_ptkp: '',
    nama_bank: '',
    nomor_rekening: '',
    atas_nama_rekening: '',
});

watch(() => props.show, (newVal) => {
    if (newVal && props.employee) {
        form.gaji_pokok = props.employee.gaji_pokok || 0;
        form.status_ptkp = props.employee.status_ptkp || 'TK/0';
        
        if (props.employee.primary_bank) {
            form.nama_bank = props.employee.primary_bank.nama_bank || '';
            form.nomor_rekening = props.employee.primary_bank.nomor_rekening || '';
            form.atas_nama_rekening = props.employee.primary_bank.atas_nama_rekening || '';
        } else {
            form.nama_bank = '';
            form.nomor_rekening = '';
            form.atas_nama_rekening = '';
        }
        form.clearErrors();
    }
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PUT'
    })).post(route('admin.karyawan.update', props.employee.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
            Swal.fire('Berhasil', 'Data finansial berhasil diperbarui', 'success');
        }
    });
};

const close = () => {
    emit('close');
    form.reset();
};
</script>

<template>
    <Modal :show="show" maxWidth="2xl" @close="close">
        <form @submit.prevent="submit" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6 border-b pb-2">
                Edit Data Finansial & Gaji
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="gaji_pokok" value="Gaji Pokok (Rp)" />
                    <TextInput
                        id="gaji_pokok"
                        type="number"
                        class="mt-1 block w-full"
                        v-model="form.gaji_pokok"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.gaji_pokok" />
                </div>

                <div>
                    <InputLabel for="status_ptkp" value="Status PTKP" />
                    <select 
                        id="status_ptkp" 
                        v-model="form.status_ptkp" 
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    >
                        <option value="TK/0">TK/0 - Tidak Kawin / Tanpa Tanggungan</option>
                        <option value="TK/1">TK/1 - Tidak Kawin / 1 Tanggungan</option>
                        <option value="TK/2">TK/2 - Tidak Kawin / 2 Tanggungan</option>
                        <option value="TK/3">TK/3 - Tidak Kawin / 3 Tanggungan</option>
                        <option value="K/0">K/0 - Kawin / Tanpa Tanggungan</option>
                        <option value="K/1">K/1 - Kawin / 1 Tanggungan</option>
                        <option value="K/2">K/2 - Kawin / 2 Tanggungan</option>
                        <option value="K/3">K/3 - Kawin / 3 Tanggungan</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.status_ptkp" />
                </div>

                <div class="md:col-span-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Informasi Rekening Bank (Primary)</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="nama_bank" value="Nama Bank" />
                            <TextInput
                                id="nama_bank"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.nama_bank"
                                placeholder="Contoh: BCA, BNI, Mandiri"
                            />
                            <InputError class="mt-2" :message="form.errors.nama_bank" />
                        </div>

                        <div>
                            <InputLabel for="nomor_rekening" value="Nomor Rekening" />
                            <TextInput
                                id="nomor_rekening"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.nomor_rekening"
                            />
                            <InputError class="mt-2" :message="form.errors.nomor_rekening" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="atas_nama_rekening" value="Atas Nama (Pemilik Rekening)" />
                            <TextInput
                                id="atas_nama_rekening"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.atas_nama_rekening"
                            />
                            <InputError class="mt-2" :message="form.errors.atas_nama_rekening" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <SecondaryButton @click="close">Batal</SecondaryButton>
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Simpan Perubahan
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
