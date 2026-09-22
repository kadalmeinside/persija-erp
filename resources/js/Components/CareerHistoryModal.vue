<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    karyawan: Object,
    departemens: Array
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    tipe_peristiwa: 'Perpanjangan Kontrak',
    tanggal_efektif: '',
    tanggal_berakhir_kontrak: '',
    id_departemen: '',
    jabatan: '',
    status_karyawan: 'Tetap',
    gaji_pokok: 0,
    catatan: '',
    file_sk: null
});

const handleFileChange = (e) => {
    form.file_sk = e.target.files[0];
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        form.reset();
        form.clearErrors();
        
        // Auto-fill using current employee data for better UX during contract extensions
        if (props.karyawan) {
            form.id_departemen = props.karyawan.id_departemen || '';
            form.jabatan = props.karyawan.jabatan || '';
            form.status_karyawan = props.karyawan.status_karyawan || 'Tetap';
            form.gaji_pokok = props.karyawan.gaji_pokok || 0;
            
            // Default to 'Perpanjangan Kontrak' if they were 'Kontrak' or 'Magang'
            if (['Kontrak', 'Magang'].includes(props.karyawan.status_karyawan)) {
                form.tipe_peristiwa = 'Perpanjangan Kontrak';
            } else {
                form.tipe_peristiwa = 'Promosi';
            }
        }
    }
});

const submit = () => {
    form.post(route('admin.karyawan.riwayat-karir.store', props.karyawan.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
            form.reset();
            Swal.fire({
                title: 'Berhasil',
                text: 'Riwayat karir berhasil ditambahkan dan data profil otomatis diperbarui!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
};

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" maxWidth="2xl">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Tambah Riwayat Karir & Kontrak</h2>
            
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Tipe Peristiwa" />
                        <select v-model="form.tipe_peristiwa" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="Pengangkatan Awal">Pengangkatan Awal</option>
                            <option value="Perpanjangan Kontrak">Perpanjangan Kontrak</option>
                            <option value="Pengangkatan Tetap">Pengangkatan Tetap</option>
                            <option value="Promosi">Promosi</option>
                            <option value="Demosi">Demosi</option>
                            <option value="Mutasi">Mutasi</option>
                            <option value="Penyesuaian Gaji">Penyesuaian Gaji</option>
                            <option value="Resign">Resign</option>
                            <option value="PHK">PHK</option>
                            <option value="Habis Kontrak">Habis Kontrak</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                        <InputError :message="form.errors.tipe_peristiwa" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Tanggal Efektif" />
                        <TextInput type="date" v-model="form.tanggal_efektif" class="mt-1 block w-full" />
                        <InputError :message="form.errors.tanggal_efektif" class="mt-2" />
                    </div>
                </div>

                <div v-if="!['Resign', 'PHK', 'Habis Kontrak'].includes(form.tipe_peristiwa)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Status Karyawan" />
                        <select v-model="form.status_karyawan" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="Tetap">Tetap</option>
                            <option value="Kontrak">Kontrak</option>
                            <option value="Magang">Magang</option>
                            <option value="Probation">Probation</option>
                        </select>
                        <InputError :message="form.errors.status_karyawan" class="mt-2" />
                    </div>

                    <div v-if="['Kontrak', 'Magang', 'Probation'].includes(form.status_karyawan)">
                        <InputLabel value="Tanggal Berakhir Kontrak" />
                        <TextInput type="date" v-model="form.tanggal_berakhir_kontrak" class="mt-1 block w-full" />
                        <InputError :message="form.errors.tanggal_berakhir_kontrak" class="mt-2" />
                    </div>
                </div>

                <div v-if="!['Resign', 'PHK', 'Habis Kontrak'].includes(form.tipe_peristiwa)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Departemen" />
                        <select v-model="form.id_departemen" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Pilih Departemen --</option>
                            <option v-for="dept in departemens" :key="dept.id" :value="dept.id">{{ dept.nama_departemen }}</option>
                        </select>
                        <InputError :message="form.errors.id_departemen" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Jabatan" />
                        <TextInput type="text" v-model="form.jabatan" class="mt-1 block w-full" placeholder="e.g. Senior Manager" />
                        <InputError :message="form.errors.jabatan" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-if="!['Resign', 'PHK', 'Habis Kontrak'].includes(form.tipe_peristiwa)">
                        <InputLabel value="Gaji Pokok (Rp)" />
                        <TextInput type="number" v-model="form.gaji_pokok" class="mt-1 block w-full" />
                        <InputError :message="form.errors.gaji_pokok" class="mt-2" />
                    </div>

                    <div :class="['Resign', 'PHK', 'Habis Kontrak'].includes(form.tipe_peristiwa) ? 'col-span-2' : ''">
                        <InputLabel value="File SK / Dokumen Pendukung (Opsional, PDF/JPG, Max 5MB)" />
                        <input type="file" @change="handleFileChange" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        <InputError :message="form.errors.file_sk" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Catatan / Keterangan" />
                    <textarea v-model="form.catatan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <InputError :message="form.errors.catatan" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="close" class="mr-3">Batal</SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Simpan Riwayat
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
