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
    departemens: Array,
    lokasiKantors: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    id_departemen: '',
    jabatan: '',
    status_karyawan: '',
    tgl_bergabung: '',
    id_lokasi_kantor: '',
    is_strict_location: false,
});

watch(() => props.show, (newVal) => {
    if (newVal && props.employee) {
        form.id_departemen = props.employee.id_departemen || '';
        form.jabatan = props.employee.jabatan || '';
        form.status_karyawan = props.employee.status_karyawan || '';
        form.tgl_bergabung = props.employee.tgl_bergabung || '';
        form.id_lokasi_kantor = props.employee.id_lokasi_kantor || '';
        form.is_strict_location = props.employee.is_strict_location == 1;
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
            Swal.fire('Berhasil', 'Data kepegawaian berhasil diperbarui', 'success');
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
                Edit Data Kepegawaian
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="id_departemen" value="Departemen" />
                    <select 
                        id="id_departemen" 
                        v-model="form.id_departemen" 
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        required
                    >
                        <option value="">Pilih Departemen</option>
                        <option v-for="dept in departemens" :key="dept.id" :value="dept.id">
                            {{ dept.nama_departemen }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.id_departemen" />
                </div>

                <div>
                    <InputLabel for="jabatan" value="Jabatan" />
                    <TextInput
                        id="jabatan"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.jabatan"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.jabatan" />
                </div>

                <div>
                    <InputLabel for="status_karyawan" value="Status Karyawan" />
                    <select 
                        id="status_karyawan" 
                        v-model="form.status_karyawan" 
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        required
                    >
                        <option value="">Pilih Status</option>
                        <option value="Tetap">Tetap</option>
                        <option value="Kontrak">Kontrak</option>
                        <option value="Magang">Magang</option>
                        <option value="Probation">Probation</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.status_karyawan" />
                </div>

                <div>
                    <InputLabel for="tgl_bergabung" value="Tanggal Bergabung" />
                    <TextInput
                        id="tgl_bergabung"
                        type="date"
                        class="mt-1 block w-full"
                        v-model="form.tgl_bergabung"
                    />
                    <InputError class="mt-2" :message="form.errors.tgl_bergabung" />
                </div>

                <!-- Penempatan Kantor -->
                <div class="md:col-span-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Penempatan Kantor & Absensi</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="id_lokasi_kantor" value="Penempatan Cabang (Opsional)" />
                            <select 
                                id="id_lokasi_kantor" 
                                v-model="form.id_lokasi_kantor" 
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                            >
                                <option value="">Pusat / Bebas (Default)</option>
                                <option v-for="lokasi in lokasiKantors" :key="lokasi.id" :value="lokasi.id">
                                    {{ lokasi.nama_kantor }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.id_lokasi_kantor" />
                            <p class="text-xs text-gray-500 mt-1">Kosongkan jika karyawan boleh roaming di semua cabang.</p>
                        </div>

                        <div class="flex items-center mt-6">
                            <label class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.is_strict_location" class="sr-only">
                                    <div class="block bg-gray-200 dark:bg-gray-700 w-10 h-6 rounded-full transition-colors" :class="{'bg-indigo-500': form.is_strict_location}"></div>
                                    <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="{'transform translate-x-4': form.is_strict_location}"></div>
                                </div>
                                <div class="ml-3">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Strict Mode</span>
                                    <p class="text-xs text-gray-500">Jika aktif, hanya bisa absen di cabang terpilih.</p>
                                </div>
                            </label>
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

<style scoped>
.dot {
    transition: all 0.3s ease-in-out;
}
</style>
