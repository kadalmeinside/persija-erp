<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { DocumentPlusIcon, DocumentMinusIcon, PlusCircleIcon, TrashIcon, PhotoIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const activeTab = ref('merge'); // 'merge' or 'split'

// --- MERGE FORM ---
const mergeFiles = ref([]);
const mergeError = ref('');
const isMerging = ref(false);

const handleMergeFileChange = (e) => {
    const files = Array.from(e.target.files);
    files.forEach(file => {
        if (file.type === 'application/pdf') {
            mergeFiles.value.push(file);
        } else {
            mergeError.value = 'Hanya file PDF yang diperbolehkan.';
        }
    });
    e.target.value = ''; // Reset input
};

const removeMergeFile = (index) => {
    mergeFiles.value.splice(index, 1);
};

const submitMerge = async () => {
    if (mergeFiles.value.length < 2) {
        mergeError.value = 'Pilih minimal 2 file PDF untuk digabungkan.';
        return;
    }
    mergeError.value = '';
    isMerging.value = true;

    const formData = new FormData();
    mergeFiles.value.forEach((file, i) => {
        formData.append(`files[${i}]`, file);
    });

    try {
        const response = await axios.post(route('admin.pdf-tools.merge'), formData, {
            responseType: 'blob', // Important for downloading file
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        const contentDisposition = response.headers['content-disposition'];
        let fileName = 'merged.pdf';
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (fileNameMatch && fileNameMatch.length === 2)
                fileName = fileNameMatch[1];
        }
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        Swal.fire('Berhasil!', 'File PDF berhasil digabungkan.', 'success');
        mergeFiles.value = [];
    } catch (error) {
        let msg = 'Gagal menggabungkan PDF.';
        if (error.response && error.response.data instanceof Blob) {
             const text = await error.response.data.text();
             try {
                 const json = JSON.parse(text);
                 msg = json.message || msg;
             } catch(e) {}
        }
        Swal.fire('Error!', msg, 'error');
    } finally {
        isMerging.value = false;
    }
};

// --- SPLIT FORM ---
const splitFile = ref(null);
const splitPages = ref('');
const splitError = ref('');
const isSplitting = ref(false);

const handleSplitFileChange = (e) => {
    const file = e.target.files[0];
    if (file && file.type === 'application/pdf') {
        splitFile.value = file;
        splitError.value = '';
    } else {
        splitFile.value = null;
        splitError.value = 'Silakan pilih file PDF yang valid.';
    }
    e.target.value = ''; // Reset
};

const submitSplit = async () => {
    if (!splitFile.value) {
        splitError.value = 'Pilih file PDF yang akan dipisahkan.';
        return;
    }
    if (!splitPages.value) {
        splitError.value = 'Masukkan format halaman (contoh: 1-3, 5).';
        return;
    }
    splitError.value = '';
    isSplitting.value = true;

    const formData = new FormData();
    formData.append('file', splitFile.value);
    formData.append('pages', splitPages.value);

    try {
        const response = await axios.post(route('admin.pdf-tools.split'), formData, {
            responseType: 'blob',
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        const contentDisposition = response.headers['content-disposition'];
        let fileName = 'split.pdf';
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (fileNameMatch && fileNameMatch.length === 2)
                fileName = fileNameMatch[1];
        }
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        Swal.fire('Berhasil!', 'File PDF berhasil dipisahkan.', 'success');
        splitFile.value = null;
        splitPages.value = '';
    } catch (error) {
        let msg = 'Gagal memisahkan PDF.';
        if (error.response && error.response.data instanceof Blob) {
             const text = await error.response.data.text();
             try {
                 const json = JSON.parse(text);
                 msg = json.message || msg;
             } catch(e) {}
        }
        Swal.fire('Error!', msg, 'error');
    } finally {
        isSplitting.value = false;
    }
};

// --- CONVERT IMAGE FORM ---
const convertImageFiles = ref([]);
const convertImageError = ref('');
const isConvertingImage = ref(false);

const handleConvertImageChange = (e) => {
    const files = Array.from(e.target.files);
    files.forEach(file => {
        if (file.type.startsWith('image/')) {
            convertImageFiles.value.push(file);
        } else {
            convertImageError.value = 'Hanya file gambar (JPG, PNG) yang diperbolehkan.';
        }
    });
    e.target.value = ''; // Reset input
};

const removeConvertImageFile = (index) => {
    convertImageFiles.value.splice(index, 1);
};

const submitConvertImage = async () => {
    if (convertImageFiles.value.length === 0) {
        convertImageError.value = 'Pilih minimal 1 file gambar.';
        return;
    }
    convertImageError.value = '';
    isConvertingImage.value = true;

    const formData = new FormData();
    convertImageFiles.value.forEach((file, i) => {
        formData.append(`files[${i}]`, file);
    });

    try {
        const response = await axios.post(route('admin.pdf-tools.convert-image'), formData, {
            responseType: 'blob',
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        const contentDisposition = response.headers['content-disposition'];
        let fileName = 'converted.pdf';
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (fileNameMatch && fileNameMatch.length === 2)
                fileName = fileNameMatch[1];
        }
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        Swal.fire('Berhasil!', 'Gambar berhasil dikonversi ke PDF.', 'success');
        convertImageFiles.value = [];
    } catch (error) {
        let msg = 'Gagal mengonversi gambar ke PDF.';
        if (error.response && error.response.data instanceof Blob) {
             const text = await error.response.data.text();
             try {
                 const json = JSON.parse(text);
                 msg = json.message || msg;
             } catch(e) {}
        }
        Swal.fire('Error!', msg, 'error');
    } finally {
        isConvertingImage.value = false;
    }
};

// --- CONVERT WORD FORM ---
const convertWordFile = ref(null);
const convertWordError = ref('');
const isConvertingWord = ref(false);

const handleConvertWordChange = (e) => {
    const file = e.target.files[0];
    if (file && (file.type === 'application/msword' || file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' || file.name.endsWith('.doc') || file.name.endsWith('.docx'))) {
        convertWordFile.value = file;
        convertWordError.value = '';
    } else {
        convertWordFile.value = null;
        convertWordError.value = 'Silakan pilih file Word (.doc atau .docx) yang valid.';
    }
    e.target.value = ''; // Reset
};

const submitConvertWord = async () => {
    if (!convertWordFile.value) {
        convertWordError.value = 'Pilih file Word yang akan dikonversi.';
        return;
    }
    convertWordError.value = '';
    isConvertingWord.value = true;

    const formData = new FormData();
    formData.append('file', convertWordFile.value);

    try {
        const response = await axios.post(route('admin.pdf-tools.convert-word'), formData, {
            responseType: 'blob',
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        const contentDisposition = response.headers['content-disposition'];
        let fileName = 'converted_word.pdf';
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (fileNameMatch && fileNameMatch.length === 2)
                fileName = fileNameMatch[1];
        }
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        Swal.fire('Berhasil!', 'File Word berhasil dikonversi ke PDF.', 'success');
        convertWordFile.value = null;
    } catch (error) {
        let msg = 'Gagal mengonversi Word ke PDF. Beberapa format kompleks mungkin tidak didukung.';
        if (error.response && error.response.data instanceof Blob) {
             const text = await error.response.data.text();
             try {
                 const json = JSON.parse(text);
                 msg = json.message || msg;
             } catch(e) {}
        }
        Swal.fire('Error!', msg, 'error');
    } finally {
        isConvertingWord.value = false;
    }
};

</script>

<template>
    <Head title="Alat PDF" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Alat PDF</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <!-- TABS -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden mb-6">
                    <div class="flex border-b border-gray-200 dark:border-gray-700">
                        <button 
                            @click="activeTab = 'merge'"
                            :class="['flex-1 py-4 px-6 text-center text-sm font-medium transition-colors focus:outline-none flex items-center justify-center space-x-2', 
                                     activeTab === 'merge' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-gray-900/50' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']"
                        >
                            <DocumentPlusIcon class="w-5 h-5" />
                            <span>Gabungkan PDF</span>
                        </button>
                        <button 
                            @click="activeTab = 'split'"
                            :class="['flex-1 py-4 px-6 text-center text-sm font-medium transition-colors focus:outline-none flex items-center justify-center space-x-2', 
                                     activeTab === 'split' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-gray-900/50' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']"
                        >
                            <DocumentMinusIcon class="w-5 h-5" />
                            <span>Pisahkan PDF</span>
                        </button>
                        <button 
                            @click="activeTab = 'convert-image'"
                            :class="['flex-1 py-4 px-6 text-center text-sm font-medium transition-colors focus:outline-none flex items-center justify-center space-x-2', 
                                     activeTab === 'convert-image' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-gray-900/50' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']"
                        >
                            <PhotoIcon class="w-5 h-5" />
                            <span>Gambar ke PDF</span>
                        </button>
                        <button 
                            @click="activeTab = 'convert-word'"
                            :class="['flex-1 py-4 px-6 text-center text-sm font-medium transition-colors focus:outline-none flex items-center justify-center space-x-2', 
                                     activeTab === 'convert-word' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-gray-900/50' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']"
                        >
                            <DocumentTextIcon class="w-5 h-5" />
                            <span>Word ke PDF</span>
                        </button>
                    </div>
                </div>

                <!-- CONTENT TAB MERGE -->
                <div v-show="activeTab === 'merge'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 animate-fade-in-up">
                    <div class="text-center mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Gabungkan File PDF</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Pilih dua atau lebih file PDF untuk digabungkan menjadi satu.</p>
                    </div>

                    <div class="space-y-6">
                        <!-- Upload Area -->
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors relative">
                            <input type="file" multiple accept="application/pdf" @change="handleMergeFileChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            <DocumentPlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Klik atau seret file PDF ke sini
                            </p>
                        </div>
                        
                        <InputError :message="mergeError" class="mt-2 text-center" />

                        <!-- File List -->
                        <ul v-if="mergeFiles.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            <li v-for="(file, index) in mergeFiles" :key="index" class="p-4 flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-800">
                                <div class="flex items-center space-x-3 overflow-hidden">
                                    <span class="flex-shrink-0 bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-300 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold">{{ index + 1 }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ file.name }}</span>
                                </div>
                                <button @click="removeMergeFile(index)" class="text-red-500 hover:text-red-700 p-2 rounded-md hover:bg-red-50 dark:hover:bg-red-900/30">
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </li>
                        </ul>

                        <div class="flex justify-end pt-4">
                            <PrimaryButton @click="submitMerge" :disabled="isMerging || mergeFiles.length < 2" :class="{'opacity-50': isMerging || mergeFiles.length < 2}">
                                <span v-if="isMerging">Menggabungkan...</span>
                                <span v-else>Gabungkan PDF</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- CONTENT TAB SPLIT -->
                <div v-show="activeTab === 'split'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 animate-fade-in-up">
                    <div class="text-center mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pisahkan File PDF</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Ekstrak halaman tertentu dari dokumen PDF Anda.</p>
                    </div>

                    <div class="space-y-6">
                        <!-- Upload Area -->
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors relative" :class="{'bg-primary-50 dark:bg-primary-900/20 border-primary-500': splitFile}">
                            <input type="file" accept="application/pdf" @change="handleSplitFileChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            <DocumentMinusIcon class="mx-auto h-12 w-12" :class="splitFile ? 'text-primary-500' : 'text-gray-400'" />
                            <p v-if="!splitFile" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Klik atau seret file PDF ke sini
                            </p>
                            <p v-else class="mt-2 text-sm font-medium text-primary-600 dark:text-primary-400">
                                {{ splitFile.name }}
                            </p>
                        </div>

                        <div>
                            <InputLabel for="pages" value="Halaman yang diekstrak (contoh: 1-3, 5, 8-10)" />
                            <TextInput
                                id="pages"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="splitPages"
                                placeholder="1-3, 5, 8-10"
                            />
                            <InputError :message="splitError" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Gunakan koma untuk memisahkan dan strip untuk rentang.</p>
                        </div>

                        <div class="flex justify-end pt-4">
                            <PrimaryButton @click="submitSplit" :disabled="isSplitting || !splitFile" :class="{'opacity-50': isSplitting || !splitFile}">
                                <span v-if="isSplitting">Memisahkan...</span>
                                <span v-else>Pisahkan PDF</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- CONTENT TAB CONVERT IMAGE -->
                <div v-show="activeTab === 'convert-image'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 animate-fade-in-up">
                    <div class="text-center mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ubah Gambar ke PDF</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Pilih satu atau lebih file gambar (JPG/PNG) untuk dijadikan dokumen PDF.</p>
                    </div>

                    <div class="space-y-6">
                        <!-- Upload Area -->
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors relative">
                            <input type="file" multiple accept="image/*" @change="handleConvertImageChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Klik atau seret gambar ke sini
                            </p>
                        </div>
                        
                        <InputError :message="convertImageError" class="mt-2 text-center" />

                        <!-- File List -->
                        <ul v-if="convertImageFiles.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            <li v-for="(file, index) in convertImageFiles" :key="index" class="p-4 flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-800">
                                <div class="flex items-center space-x-3 overflow-hidden">
                                    <span class="flex-shrink-0 bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-300 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold">{{ index + 1 }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ file.name }}</span>
                                </div>
                                <button @click="removeConvertImageFile(index)" class="text-red-500 hover:text-red-700 p-2 rounded-md hover:bg-red-50 dark:hover:bg-red-900/30">
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </li>
                        </ul>

                        <div class="flex justify-end pt-4">
                            <PrimaryButton @click="submitConvertImage" :disabled="isConvertingImage || convertImageFiles.length === 0" :class="{'opacity-50': isConvertingImage || convertImageFiles.length === 0}">
                                <span v-if="isConvertingImage">Mengonversi...</span>
                                <span v-else>Konversi ke PDF</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- CONTENT TAB CONVERT WORD -->
                <div v-show="activeTab === 'convert-word'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 animate-fade-in-up">
                    <div class="text-center mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ubah Word ke PDF</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Konversi dokumen Microsoft Word (.doc, .docx) menjadi PDF.</p>
                        <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-2 bg-yellow-50 dark:bg-yellow-900/30 py-1 px-3 rounded inline-block">
                            Catatan: Format dokumen yang kompleks (tabel rumit, gambar tertentu) mungkin mengalami perubahan letak.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <!-- Upload Area -->
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors relative" :class="{'bg-primary-50 dark:bg-primary-900/20 border-primary-500': convertWordFile}">
                            <input type="file" accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" @change="handleConvertWordChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            <DocumentTextIcon class="mx-auto h-12 w-12" :class="convertWordFile ? 'text-primary-500' : 'text-gray-400'" />
                            <p v-if="!convertWordFile" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Klik atau seret file Word ke sini
                            </p>
                            <p v-else class="mt-2 text-sm font-medium text-primary-600 dark:text-primary-400">
                                {{ convertWordFile.name }}
                            </p>
                        </div>
                        
                        <InputError :message="convertWordError" class="mt-2 text-center" />

                        <div class="flex justify-end pt-4">
                            <PrimaryButton @click="submitConvertWord" :disabled="isConvertingWord || !convertWordFile" :class="{'opacity-50': isConvertingWord || !convertWordFile}">
                                <span v-if="isConvertingWord">Mengonversi...</span>
                                <span v-else>Konversi ke PDF</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
