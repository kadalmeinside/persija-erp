<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ArrowsPointingInIcon, ArrowsPointingOutIcon, PhotoIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const activeTab = ref('compress'); // 'compress' or 'upscale'

// --- COMPRESS FORM ---
const compressFile = ref(null);
const compressLevel = ref('medium');
const compressError = ref('');
const isCompressing = ref(false);

const handleCompressFileChange = (e) => {
    const file = e.target.files[0];
    if (file && (file.type === 'image/jpeg' || file.type === 'image/png' || file.type === 'image/jpg')) {
        compressFile.value = file;
        compressError.value = '';
    } else {
        compressFile.value = null;
        compressError.value = 'Silakan pilih file gambar (JPG, PNG) yang valid.';
    }
    e.target.value = ''; // Reset input
};

const submitCompress = async () => {
    if (!compressFile.value) {
        compressError.value = 'Pilih file gambar untuk dikompres.';
        return;
    }
    compressError.value = '';
    isCompressing.value = true;

    const formData = new FormData();
    formData.append('file', compressFile.value);
    formData.append('level', compressLevel.value);

    try {
        const response = await axios.post(route('admin.image-tools.compress'), formData, {
            responseType: 'blob', // Important for downloading file
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        const contentDisposition = response.headers['content-disposition'];
        let fileName = 'compressed.jpg';
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (fileNameMatch && fileNameMatch.length === 2)
                fileName = fileNameMatch[1];
        }
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        Swal.fire('Berhasil!', 'Gambar berhasil dikompres dan diunduh.', 'success');
        compressFile.value = null;
    } catch (error) {
        let msg = 'Gagal mengompres gambar.';
        if (error.response && error.response.data instanceof Blob) {
             const text = await error.response.data.text();
             try {
                 const json = JSON.parse(text);
                 msg = json.message || msg;
             } catch(e) {}
        }
        Swal.fire('Error!', msg, 'error');
    } finally {
        isCompressing.value = false;
    }
};

// --- UPSCALE FORM ---
const upscaleFile = ref(null);
const upscaleMode = ref('hd');
const upscaleError = ref('');
const isUpscaling = ref(false);

const handleUpscaleFileChange = (e) => {
    const file = e.target.files[0];
    if (file && (file.type === 'image/jpeg' || file.type === 'image/png' || file.type === 'image/jpg')) {
        upscaleFile.value = file;
        upscaleError.value = '';
    } else {
        upscaleFile.value = null;
        upscaleError.value = 'Silakan pilih file gambar (JPG, PNG) yang valid.';
    }
    e.target.value = ''; // Reset input
};

const submitUpscale = async () => {
    if (!upscaleFile.value) {
        upscaleError.value = 'Pilih file gambar untuk dibesarkan resolusinya.';
        return;
    }
    upscaleError.value = '';
    isUpscaling.value = true;

    const formData = new FormData();
    formData.append('file', upscaleFile.value);
    formData.append('mode', upscaleMode.value);

    try {
        const response = await axios.post(route('admin.image-tools.upscale'), formData, {
            responseType: 'blob', // Important for downloading file
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        const contentDisposition = response.headers['content-disposition'];
        let fileName = 'upscaled.jpg';
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (fileNameMatch && fileNameMatch.length === 2)
                fileName = fileNameMatch[1];
        }
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        Swal.fire('Berhasil!', 'Resolusi gambar berhasil dibesarkan.', 'success');
        upscaleFile.value = null;
    } catch (error) {
        let msg = 'Gagal membesarkan gambar.';
        if (error.response && error.response.data instanceof Blob) {
             const text = await error.response.data.text();
             try {
                 const json = JSON.parse(text);
                 msg = json.message || msg;
             } catch(e) {}
        }
        Swal.fire('Error!', msg, 'error');
    } finally {
        isUpscaling.value = false;
    }
};
</script>

<template>
    <Head title="Alat Gambar" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Alat Gambar</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <!-- TABS -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden mb-6">
                    <div class="flex border-b border-gray-200 dark:border-gray-700">
                        <button 
                            @click="activeTab = 'compress'"
                            :class="['flex-1 py-4 px-6 text-center text-sm font-medium transition-colors focus:outline-none flex items-center justify-center space-x-2 border-r border-gray-200 dark:border-gray-700', 
                                     activeTab === 'compress' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-gray-900/50' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']"
                        >
                            <ArrowsPointingInIcon class="w-5 h-5" />
                            <span>Kompres Gambar</span>
                        </button>
                        <button 
                            @click="activeTab = 'upscale'"
                            :class="['flex-1 py-4 px-6 text-center text-sm font-medium transition-colors focus:outline-none flex items-center justify-center space-x-2', 
                                     activeTab === 'upscale' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-gray-900/50' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']"
                        >
                            <ArrowsPointingOutIcon class="w-5 h-5" />
                            <span>Upscale ke HD</span>
                        </button>
                    </div>
                </div>

                <!-- CONTENT TAB COMPRESS -->
                <div v-show="activeTab === 'compress'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 animate-fade-in-up">
                    <div class="text-center mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Kompres Ukuran File Gambar</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Perkecil ukuran memori (KB/MB) file JPG/PNG Anda agar lebih ringan.</p>
                    </div>

                    <div class="space-y-6">
                        <!-- Upload Area -->
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors relative" :class="{'bg-primary-50 dark:bg-primary-900/20 border-primary-500': compressFile}">
                            <input type="file" accept="image/jpeg, image/png, image/jpg" @change="handleCompressFileChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            <PhotoIcon class="mx-auto h-12 w-12" :class="compressFile ? 'text-primary-500' : 'text-gray-400'" />
                            <p v-if="!compressFile" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Klik atau seret gambar (JPG/PNG) ke sini
                            </p>
                            <p v-else class="mt-2 text-sm font-medium text-primary-600 dark:text-primary-400">
                                {{ compressFile.name }}
                            </p>
                        </div>
                        
                        <InputError :message="compressError" class="mt-2 text-center" />

                        <div>
                            <InputLabel value="Tingkat Kompresi" />
                            <div class="mt-2 flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="compressLevel" value="low" class="text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Ringan (Kualitas Tinggi)</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="compressLevel" value="medium" class="text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Sedang (Rekomendasi)</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="compressLevel" value="high" class="text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Ekstrem (Kualitas Rendah)</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <PrimaryButton @click="submitCompress" :disabled="isCompressing || !compressFile" :class="{'opacity-50': isCompressing || !compressFile}">
                                <span v-if="isCompressing">Mengompres...</span>
                                <span v-else>Kompres Gambar</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <!-- CONTENT TAB UPSCALE -->
                <div v-show="activeTab === 'upscale'" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 animate-fade-in-up">
                    <div class="text-center mb-8">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Upscale Resolusi Gambar</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Perbesar dimensi piksel gambar Anda ke format HD atau 2x lipat (menggunakan Interpolasi Standar).</p>
                    </div>

                    <div class="space-y-6">
                        <!-- Upload Area -->
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors relative" :class="{'bg-primary-50 dark:bg-primary-900/20 border-primary-500': upscaleFile}">
                            <input type="file" accept="image/jpeg, image/png, image/jpg" @change="handleUpscaleFileChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            <PhotoIcon class="mx-auto h-12 w-12" :class="upscaleFile ? 'text-primary-500' : 'text-gray-400'" />
                            <p v-if="!upscaleFile" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Klik atau seret gambar ke sini
                            </p>
                            <p v-else class="mt-2 text-sm font-medium text-primary-600 dark:text-primary-400">
                                {{ upscaleFile.name }}
                            </p>
                        </div>
                        
                        <InputError :message="upscaleError" class="mt-2 text-center" />

                        <div>
                            <InputLabel value="Mode Pembesaran" />
                            <div class="mt-2 flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="upscaleMode" value="hd" class="text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Paskan ke Lebar HD (1920px)</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="upscaleMode" value="2x" class="text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Skala 2x Lipat</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <PrimaryButton @click="submitUpscale" :disabled="isUpscaling || !upscaleFile" :class="{'opacity-50': isUpscaling || !upscaleFile}">
                                <span v-if="isUpscaling">Memproses...</span>
                                <span v-else>Upscale Gambar</span>
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
