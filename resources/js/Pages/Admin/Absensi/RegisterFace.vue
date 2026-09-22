<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CameraIcon, CheckCircleIcon, XCircleIcon, UserIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    karyawan: Object
});

const videoRef = ref(null);
const loading = ref(true);
const statusMsg = ref('Memuat Model AI & Kamera...');
const isError = ref(false);
const stream = ref(null);
const hasRegistered = ref(!!props.karyawan.face_descriptor);
const isProcessing = ref(false);

let faceapiLoaded = false;

onMounted(async () => {
    // Muat script face-api.js secara dinamis
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js';
    script.onload = () => {
        faceapiLoaded = true;
        initSystem();
    };
    document.head.appendChild(script);
});

onUnmounted(() => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
    }
});

const initSystem = async () => {
    try {
        statusMsg.value = 'Memuat Model Wajah...';
        await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
        await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
        await faceapi.nets.faceRecognitionNet.loadFromUri('/models');

        startCamera();
    } catch (error) {
        isError.value = true;
        statusMsg.value = 'Gagal memuat AI Model: ' + error.message;
        loading.value = false;
    }
};

const startCamera = async () => {
    try {
        statusMsg.value = 'Membuka Kamera...';
        try {
            stream.value = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        } catch(e) {
            stream.value = await navigator.mediaDevices.getUserMedia({ video: true });
        }
        loading.value = false;
        await nextTick();
        videoRef.value.srcObject = stream.value;
        statusMsg.value = 'Arahkan wajah Anda ke kamera dan klik tombol Daftar.';
    } catch (err) {
        isError.value = true;
        statusMsg.value = 'Gagal membuka kamera: ' + err.message;
        loading.value = false;
    }
};

const registerFace = async () => {
    if (!faceapiLoaded || isError.value || isProcessing.value) return;

    isProcessing.value = true;
    statusMsg.value = 'Mendeteksi Wajah... (Tahan posisi Anda)';
    
    try {
        const detection = await faceapi.detectSingleFace(videoRef.value, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();
        
        if (!detection) {
            throw new Error('Wajah tidak terdeteksi. Pastikan pencahayaan cukup dan wajah terlihat penuh.');
        }

        statusMsg.value = 'Wajah Ditemukan! Menyimpan profil biometrik...';

        const descriptorArray = Array.from(detection.descriptor);

        const response = await axios.post(route('admin.absensi.register-face'), {
            descriptor: JSON.stringify(descriptorArray)
        });

        if (response.data.success) {
            statusMsg.value = response.data.message;
            hasRegistered.value = true;
            Swal.fire({
                title: 'Berhasil!',
                text: response.data.message,
                icon: 'success',
                confirmButtonColor: '#4f46e5'
            });
        }

    } catch (err) {
        Swal.fire({
            title: 'Gagal',
            text: err.message,
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        statusMsg.value = 'Coba lagi. Arahkan wajah dengan jelas ke kamera.';
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Registrasi Wajah" />

        <div class="max-w-2xl mx-auto sm:py-8 sm:px-6 lg:px-8">
            <div class="bg-white sm:rounded-2xl sm:shadow-sm sm:border border-gray-100 overflow-hidden min-h-[calc(100vh-4rem)] sm:min-h-0 flex flex-col">
                <div class="p-4 sm:p-6 bg-indigo-600 text-white flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <h2 class="text-xl font-bold">Pendaftaran Wajah (Face ID)</h2>
                        <p class="text-indigo-100 text-sm mt-1">Daftarkan wajah Anda untuk absensi</p>
                    </div>
                    <div v-if="hasRegistered" class="flex items-center gap-2 bg-green-500/20 px-3 py-1.5 rounded-lg border border-green-400/30">
                        <CheckCircleIcon class="w-5 h-5 text-green-300" />
                        <span class="text-sm font-medium text-green-100">Sudah Terdaftar</span>
                    </div>
                </div>

                <div class="p-4 sm:p-6 flex flex-col items-center flex-grow justify-center">
                    
                    <div class="w-full bg-blue-50 text-blue-800 p-4 rounded-xl mb-6 text-sm flex gap-3">
                        <UserIcon class="w-6 h-6 shrink-0 text-blue-600" />
                        <p>Pastikan Anda berada di ruangan dengan <b>pencahayaan yang cukup</b>, tidak memakai kacamata hitam atau masker, dan posisi perangkat sejajar dengan wajah.</p>
                    </div>

                    <div v-if="loading" class="flex flex-col items-center py-12">
                        <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                        <p class="mt-4 text-gray-500 font-medium text-center px-4">{{ statusMsg }}</p>
                    </div>

                    <div v-else-if="isError" class="flex flex-col items-center py-12 text-center px-4">
                        <XCircleIcon class="w-16 h-16 text-red-500 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Terjadi Kendala</h3>
                        <p class="text-red-500">{{ statusMsg }}</p>
                    </div>

                    <div v-else class="w-full flex flex-col items-center sm:max-w-sm mx-auto">
                        <div class="relative w-full sm:rounded-2xl overflow-hidden shadow-lg bg-black mb-6 aspect-[3/4] sm:aspect-auto">
                            <video ref="videoRef" autoplay muted playsinline class="w-full h-full object-cover transform scale-x-[-1]"></video>
                            
                            <!-- Frame Wajah -->
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="w-48 h-64 border-2 border-dashed border-white/50 rounded-[40%] shadow-[0_0_0_9999px_rgba(0,0,0,0.4)]"></div>
                            </div>
                            
                            <!-- Status Overlay -->
                            <div v-if="isProcessing" class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center text-white backdrop-blur-sm z-10">
                                <div class="w-10 h-10 border-4 border-white/20 border-t-white rounded-full animate-spin mb-4"></div>
                                <span class="text-sm font-medium px-6 text-center leading-relaxed">{{ statusMsg }}</span>
                            </div>
                        </div>

                        <div class="w-full">
                            <button 
                                @click="registerFace"
                                :disabled="isProcessing"
                                class="w-full py-4 rounded-xl font-bold flex flex-row items-center justify-center gap-3 transition-all shadow-sm bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <CameraIcon class="w-6 h-6" />
                                {{ hasRegistered ? 'Perbarui Wajah Saya' : 'Daftarkan Wajah Saya' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
