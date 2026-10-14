<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ArrowUpTrayIcon, DocumentArrowUpIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    employee: Object,
});

const emit = defineEmits(['close', 'saved']);

const fileInput = ref(null);
const selectedFileName = ref('');

const form = useForm({
    kategori_dokumen: 'kontrak',
    judul_dokumen: '',
    nomor_dokumen: '',
    tanggal_terbit: '',
    tanggal_kadaluarsa: '',
    catatan: '',
    file: null,
});

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        selectedFileName.value = file.name;
        if (!form.judul_dokumen) {
            // Auto set title based on file name without extension
            form.judul_dokumen = file.name.replace(/\.[^/.]+$/, "");
        }
    }
};

const submit = () => {
    if (!form.file) {
        Swal.fire('File Belum Dipilih', 'Silakan pilih berkas dokumen yang ingin diunggah.', 'warning');
        return;
    }

    form.post(route('admin.karyawan.dokumen.upload', props.employee.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Dokumen berhasil diunggah ke berkas karyawan',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};

const close = () => {
    emit('close');
    form.reset();
    selectedFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
};
</script>

<template>
    <Modal :show="show" maxWidth="2xl" @close="close">
        <form @submit.prevent="submit" class="p-6">
            <div class="flex items-center space-x-2 pb-4 border-b border-gray-200 dark:border-gray-700 mb-5">
                <DocumentArrowUpIcon class="w-6 h-6 text-indigo-500" />
                <div>
                    <h2 class="text-base md:text-lg font-bold text-gray-900 dark:text-gray-100">
                        Unggah Dokumen & Berkas Karyawan
                    </h2>
                    <p class="text-xs text-gray-500">{{ employee?.nama_lengkap }} (#{{ employee?.nomor_induk_karyawan }})</p>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Dropzone / File Picker -->
                <div>
                    <InputLabel value="Pilih Berkas Dokumen (PDF, Word, Gambar - Maks. 10MB)" />
                    <div 
                        @click="$refs.fileInput.click()" 
                        class="mt-1 border-2 border-dashed border-gray-300 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-600 rounded-2xl p-5 text-center cursor-pointer bg-gray-50/50 dark:bg-gray-900/30 transition-colors"
                    >
                        <ArrowUpTrayIcon class="w-8 h-8 mx-auto text-gray-400 mb-2" />
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                            {{ selectedFileName ? selectedFileName : 'Klik untuk memilih berkas dari komputer' }}
                        </p>
                        <p class="text-[11px] text-gray-400 mt-1">Format didukung: PDF, DOCX, DOC, JPG, PNG (Maks 10MB)</p>
                    </div>
                    <input 
                        type="file" 
                        ref="fileInput" 
                        class="hidden" 
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        @change="handleFileChange"
                    />
                    <InputError class="mt-1" :message="form.errors.file" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="kategori_dokumen" value="Kategori Dokumen" />
                        <select 
                            id="kategori_dokumen" 
                            v-model="form.kategori_dokumen" 
                            class="mt-1 block w-full text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-2xs"
                            required
                        >
                            <option value="kontrak">Surat Kontrak Kerja (PKWT/PKWTT)</option>
                            <option value="sk">Surat Keputusan (SK / Pengangkatan / SP)</option>
                            <option value="ktp">KTP (Kartu Identitas)</option>
                            <option value="npwp">NPWP (Nomor Pokok Wajib Pajak)</option>
                            <option value="bpjs">Kartu BPJS (Ketenagakerjaan / Kesehatan)</option>
                            <option value="ijazah_sertifikat">Ijazah / Sertifikat / Lisensi AFC/PSSI</option>
                            <option value="lainnya">Dokumen Lainnya</option>
                        </select>
                        <InputError class="mt-1" :message="form.errors.kategori_dokumen" />
                    </div>

                    <div>
                        <InputLabel for="judul_dokumen" value="Judul / Nama Dokumen" />
                        <TextInput 
                            id="judul_dokumen" 
                            type="text" 
                            class="mt-1 block w-full text-xs" 
                            placeholder="Contoh: Kontrak PKWT 2026-2027" 
                            v-model="form.judul_dokumen" 
                            required 
                        />
                        <InputError class="mt-1" :message="form.errors.judul_dokumen" />
                    </div>

                    <div>
                        <InputLabel for="nomor_dokumen" value="Nomor Dokumen (Opsional)" />
                        <TextInput 
                            id="nomor_dokumen" 
                            type="text" 
                            class="mt-1 block w-full text-xs" 
                            placeholder="Contoh: 045/SK-DIR/PJ/2026" 
                            v-model="form.nomor_dokumen" 
                        />
                        <InputError class="mt-1" :message="form.errors.nomor_dokumen" />
                    </div>

                    <div>
                        <InputLabel for="tanggal_terbit" value="Tanggal Terbit" />
                        <TextInput 
                            id="tanggal_terbit" 
                            type="date" 
                            class="mt-1 block w-full text-xs" 
                            v-model="form.tanggal_terbit" 
                        />
                        <InputError class="mt-1" :message="form.errors.tanggal_terbit" />
                    </div>

                    <div class="sm:col-span-2">
                        <InputLabel for="catatan" value="Catatan / Keterangan Tambahan" />
                        <TextInput 
                            id="catatan" 
                            type="text" 
                            class="mt-1 block w-full text-xs" 
                            placeholder="Keterangan singkat mengenai dokumen ini..." 
                            v-model="form.catatan" 
                        />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <SecondaryButton @click="close" type="button">Batal</SecondaryButton>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Mengunggah...' : 'Unggah Dokumen' }}
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
