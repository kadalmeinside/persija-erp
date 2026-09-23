<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { CameraIcon, MapPinIcon, CheckCircleIcon, XCircleIcon, BriefcaseIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    karyawan: Object,
    absensi: Object,
    lokasiKantor: Object
});

const videoRef = ref(null);
const loading = ref(true);
const statusMsg = ref('Memuat Model AI & Kamera...');
const isError = ref(false);
const stream = ref(null);
const location = ref(null);
const isProcessing = ref(false);

// --- Dinas Luar ---
const isDinasLuar = ref(false);
const catatanDinasLuar = ref('');

let faceapiLoaded = false;
let faceMatcher = null;

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

        if (props.karyawan.face_descriptor) {
            const descArray = JSON.parse(props.karyawan.face_descriptor);
            const labeledDescriptor = new faceapi.LabeledFaceDescriptors(
                props.karyawan.nama_lengkap,
                [new Float32Array(descArray)]
            );
            // Toleransi (distance threshold) = 0.6. Lebih kecil = lebih ketat.
            faceMatcher = new faceapi.FaceMatcher([labeledDescriptor], 0.6);
        } else {
            statusMsg.value = 'Wajah belum didaftarkan. Harap daftar ke menu Pendaftaran Wajah.';
            isError.value = true;
            loading.value = false;
            return;
        }

        statusMsg.value = 'Mengambil Lokasi Anda...';
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                location.value = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude
                };
                startCamera();
            },
            (err) => {
                isError.value = true;
                statusMsg.value = 'Akses Lokasi (GPS) Ditolak. Harap izinkan akses lokasi di browser Anda.';
                loading.value = false;
            },
            { enableHighAccuracy: true }
        );
    } catch (error) {
        isError.value = true;
        statusMsg.value = 'Gagal memuat sistem: ' + error.message;
        loading.value = false;
    }
};

const startCamera = async () => {
    try {
        statusMsg.value = 'Membuka Kamera...';
        try {
            stream.value = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        } catch (e) {
            // Fallback for desktop webcams that don't support facingMode
            stream.value = await navigator.mediaDevices.getUserMedia({ video: true });
        }
        loading.value = false;
        await nextTick();
        videoRef.value.srcObject = stream.value;
        statusMsg.value = 'Arahkan wajah Anda ke kamera dan klik tombol Absen.';
    } catch (err) {
        isError.value = true;
        statusMsg.value = 'Gagal membuka kamera: ' + err.message;
        loading.value = false;
    }
};

const performClock = async (type) => {
    if (!faceapiLoaded || isError.value || isProcessing.value) return;

    // Validasi catatan jika dinas luar
    if (isDinasLuar.value && catatanDinasLuar.value.trim().length < 5) {
        Swal.fire({
            title: 'Catatan Wajib Diisi',
            text: 'Harap isi keterangan lokasi atau kegiatan Dinas Luar Anda (minimal 5 karakter).',
            icon: 'warning',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    isProcessing.value = true;
    statusMsg.value = 'Mendeteksi Wajah... (Mohon jangan bergerak)';
    
    try {
        const detection = await faceapi.detectSingleFace(videoRef.value, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();
        
        if (!detection) {
            throw new Error('Wajah tidak terdeteksi. Pastikan pencahayaan cukup dan wajah terlihat penuh.');
        }

        const match = faceMatcher.findBestMatch(detection.descriptor);
        if (match.label === 'unknown') {
            throw new Error('Verifikasi Gagal: Wajah tidak cocok dengan profil Anda!');
        }

        statusMsg.value = 'Wajah Cocok! Mengirim data absen...';

        const canvas = document.createElement('canvas');
        canvas.width = videoRef.value.videoWidth;
        canvas.height = videoRef.value.videoHeight;
        canvas.getContext('2d').drawImage(videoRef.value, 0, 0);
        const photoData = canvas.toDataURL('image/jpeg', 0.8);

        const response = await axios.post(route('admin.absensi.storeClock'), {
            tipe: type,
            latitude: location.value.lat,
            longitude: location.value.lng,
            foto: photoData,
            is_dinas_luar: isDinasLuar.value,
            catatan: catatanDinasLuar.value.trim() || null,
        });

        if (response.data.success) {
            statusMsg.value = response.data.message;
            Swal.fire({
                title: 'Berhasil!',
                text: response.data.message,
                icon: 'success',
                confirmButtonColor: '#4f46e5'
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(response.data.message);
        }

    } catch (err) {
        Swal.fire({
            title: 'Absensi Gagal',
            text: err.message,
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        statusMsg.value = 'Arahkan wajah Anda ke kamera dan klik tombol Absen.';
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Live Absensi" />

        <div class="max-w-2xl mx-auto sm:py-8 sm:px-6 lg:px-8">
            <div class="bg-white sm:rounded-2xl sm:shadow-sm sm:border border-gray-100 overflow-hidden min-h-[calc(100vh-4rem)] sm:min-h-0 flex flex-col">
                <div class="p-4 sm:p-6 bg-indigo-600 text-white flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <h2 class="text-xl font-bold">Live Absensi (Clock In/Out)</h2>
                        <p class="text-indigo-100 text-sm mt-1">Verifikasi biometrik &amp; geolokasi</p>
                    </div>
                </div>

                <div class="p-4 sm:p-6 flex flex-col items-center flex-grow justify-center">
                    
                    <div v-if="loading" class="flex flex-col items-center py-12">
                        <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                        <p class="mt-4 text-gray-500 font-medium text-center px-4">{{ statusMsg }}</p>
                    </div>

                    <div v-else-if="isError" class="flex flex-col items-center py-12 text-center px-4">
                        <XCircleIcon class="w-16 h-16 text-red-500 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Terjadi Kendala</h3>
                        <p class="text-red-500 mb-6">{{ statusMsg }}</p>
                        
                        <div v-if="!karyawan.face_descriptor" class="mt-2">
                            <Link :href="route('admin.absensi.register-face')" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition shadow-md">
                                <CameraIcon class="w-5 h-5" />
                                Daftarkan Wajah Sekarang
                            </Link>
                        </div>
                    </div>

                    <div v-else class="w-full flex flex-col items-center sm:max-w-sm mx-auto">
                        <div class="relative w-full sm:rounded-2xl overflow-hidden shadow-lg bg-black mb-4 aspect-[3/4] sm:aspect-auto">
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

                            <!-- Dinas Luar Badge Overlay -->
                            <div v-if="isDinasLuar" class="absolute top-3 left-3 z-10 bg-amber-500 text-white text-xs font-bold px-2 py-1 rounded-lg flex items-center gap-1 shadow">
                                <BriefcaseIcon class="w-3.5 h-3.5" />
                                DINAS LUAR
                            </div>
                        </div>

                        <!-- Dinas Luar Toggle -->
                        <div class="w-full mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" v-model="isDinasLuar" class="sr-only peer" />
                                    <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-amber-800">Saya sedang Dinas Luar / Meliput</p>
                                    <p class="text-xs text-amber-600">Aktifkan jika Anda tidak berada di lingkungan kantor</p>
                                </div>
                            </label>
                            <div v-if="isDinasLuar" class="mt-3">
                                <textarea
                                    v-model="catatanDinasLuar"
                                    rows="2"
                                    placeholder="Keterangan lokasi/kegiatan, contoh: Meliput latihan tim di Stadion GBK"
                                    class="w-full border border-amber-300 rounded-lg text-sm p-2 focus:ring-amber-400 focus:border-amber-400 bg-white resize-none"
                                ></textarea>
                            </div>
                        </div>

                        <div class="w-full bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100">
                            <div class="flex items-start gap-3">
                                <MapPinIcon class="w-5 h-5 text-indigo-500 mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Lokasi Anda Saat Ini</p>
                                    <p class="text-xs text-gray-500 font-mono mt-1">{{ location?.lat }}, {{ location?.lng }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 w-full">
                            <button 
                                @click="performClock('in')"
                                :disabled="isProcessing || (absensi && absensi.waktu_masuk)"
                                :class="[
                                    'py-4 rounded-xl font-bold flex flex-col items-center justify-center gap-2 transition-all shadow-sm',
                                    (absensi && absensi.waktu_masuk) 
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200' 
                                        : 'bg-green-500 text-white hover:bg-green-600 border border-transparent hover:shadow-green-500/20 active:scale-95'
                                ]"
                            >
                                <CheckCircleIcon class="w-7 h-7" />
                                CLOCK IN
                                <span v-if="absensi && absensi.waktu_masuk" class="text-[10px] font-normal uppercase tracking-wider bg-gray-200 px-2 py-0.5 rounded-full mt-1 text-gray-500">Selesai</span>
                            </button>
                            
                            <button 
                                @click="performClock('out')"
                                :disabled="isProcessing || (!absensi || !absensi.waktu_masuk || absensi.waktu_keluar)"
                                :class="[
                                    'py-4 rounded-xl font-bold flex flex-col items-center justify-center gap-2 transition-all shadow-sm',
                                    (!absensi || !absensi.waktu_masuk || absensi.waktu_keluar) 
                                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200' 
                                        : 'bg-red-500 text-white hover:bg-red-600 border border-transparent hover:shadow-red-500/20 active:scale-95'
                                ]"
                            >
                                <XCircleIcon class="w-7 h-7" />
                                CLOCK OUT
                                <span v-if="absensi && absensi.waktu_keluar" class="text-[10px] font-normal uppercase tracking-wider bg-gray-200 px-2 py-0.5 rounded-full mt-1 text-gray-500">Selesai</span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
