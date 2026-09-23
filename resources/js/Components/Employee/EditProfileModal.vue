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
    nama_lengkap: '',
    jenis_kelamin: '',
    nomor_induk_karyawan: '',
    tempat_lahir: '',
    tgl_lahir: '',
    alamat: '',
});

watch(() => props.show, (newVal) => {
    if (newVal && props.employee) {
        form.nama_lengkap = props.employee.nama_lengkap;
        form.jenis_kelamin = props.employee.jenis_kelamin || '';
        form.nomor_induk_karyawan = props.employee.nomor_induk_karyawan;
        form.tempat_lahir = props.employee.tempat_lahir || '';
        form.tgl_lahir = props.employee.tgl_lahir || '';
        form.alamat = props.employee.alamat || '';
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
            Swal.fire('Berhasil', 'Biodata karyawan berhasil diperbarui', 'success');
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
                Edit Biodata Diri
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <InputLabel for="nik" value="Nomor Induk Karyawan (NIK)" />
                    <TextInput
                        id="nik"
                        type="text"
                        class="mt-1 block w-full bg-gray-50"
                        v-model="form.nomor_induk_karyawan"
                        required
                        disabled
                    />
                    <InputError class="mt-2" :message="form.errors.nomor_induk_karyawan" />
                </div>

                <div class="md:col-span-2">
                    <InputLabel for="nama_lengkap" value="Nama Lengkap" />
                    <TextInput
                        id="nama_lengkap"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.nama_lengkap"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.nama_lengkap" />
                </div>

                <div>
                    <InputLabel for="jenis_kelamin" value="Jenis Kelamin" />
                    <select 
                        id="jenis_kelamin" 
                        v-model="form.jenis_kelamin" 
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    >
                        <option value="">Pilih Jenis Kelamin</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.jenis_kelamin" />
                </div>

                <div>
                    <InputLabel for="tempat_lahir" value="Tempat Lahir" />
                    <TextInput
                        id="tempat_lahir"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.tempat_lahir"
                    />
                    <InputError class="mt-2" :message="form.errors.tempat_lahir" />
                </div>

                <div>
                    <InputLabel for="tgl_lahir" value="Tanggal Lahir" />
                    <TextInput
                        id="tgl_lahir"
                        type="date"
                        class="mt-1 block w-full"
                        v-model="form.tgl_lahir"
                    />
                    <InputError class="mt-2" :message="form.errors.tgl_lahir" />
                </div>

                <div class="md:col-span-2">
                    <InputLabel for="alamat" value="Alamat Lengkap" />
                    <textarea
                        id="alamat"
                        v-model="form.alamat"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        rows="3"
                    ></textarea>
                    <InputError class="mt-2" :message="form.errors.alamat" />
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
