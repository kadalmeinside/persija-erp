<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { CameraIcon, MapPinIcon, CheckCircleIcon, XCircleIcon, BriefcaseIcon, ArrowPathIcon, XMarkIcon } from '@heroicons/vue/24/outline';
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
const locationName = ref('Mencari koordinat GPS...');
const isLocating = ref(false);
const isProcessing = ref(false);

const isDinasLuar = ref(false);
const catatanDinasLuar = ref('');

let faceapiLoaded = false;
let faceMatcher = null;

onMounted(async () => {
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

const fetchLocation = () => {
    isLocating.value = true;
    locationName.value = 'Mendeteksi lokasi GPS...';
    
    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            location.value = {
                lat: pos.coords.latitude,
                lng: pos.coords.longitude
            };
            
            try {
                const res = await axios.get(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&zoom=18&addressdetails=1`);
                if (res.data && res.data.display_name) {
                    locationName.value = res.data.display_name;
                } else {
                    locationName.value = 'Lokasi tidak diketahui (Hanya Koordinat)';
                }
            } catch (err) {
                locationName.value = 'Nama jalan tidak ditemukan (Cek Koneksi)';
            }
            
            isLocating.value = false;
        },
        (err) => {
            isError.value = true;
            statusMsg.value = 'Akses Lokasi (GPS) Ditolak. Harap izinkan akses lokasi.';
            loading.value = false;
            isLocating.value = false;
            locationName.value = 'GPS Ditolak';
        },
        { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 }
    );
};

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
            faceMatcher = new faceapi.FaceMatcher([labeledDescriptor], 0.6);
        } else {
            statusMsg.value = 'Wajah belum didaftarkan. Harap daftar ke menu Pendaftaran Wajah.';
            isError.value = true;
            loading.value = false;
            return;
        }

        statusMsg.value = 'Mengambil Lokasi & Kamera...';
        fetchLocation();
        startCamera();
        
    } catch (error) {
        isError.value = true;
        statusMsg.value = 'Gagal memuat sistem: ' + error.message;
        loading.value = false;
    }
};

const startCamera = async () => {
    try {
        try {
            stream.value = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        } catch (e) {
            stream.value = await navigator.mediaDevices.getUserMedia({ video: true });
        }
        loading.value = false;
        await nextTick();
        if (videoRef.value) {
            videoRef.value.srcObject = stream.value;
        }
        statusMsg.value = 'Arahkan wajah ke lingkaran, lalu klik Absen.';
    } catch (err) {
        isError.value = true;
        statusMsg.value = 'Gagal membuka kamera: ' + err.message;
        loading.value = false;
    }
};

const performClock = async (type) => {
    if (!faceapiLoaded || isError.value || isProcessing.value || !location.value) return;

    if (isDinasLuar.value && catatanDinasLuar.value.trim().length < 5) {
        Swal.fire({
            title: 'Catatan Wajib',
            text: 'Isi keterangan Dinas Luar (min. 5 karakter).',
            icon: 'warning',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    isProcessing.value = true;
    statusMsg.value = 'Mendeteksi Wajah...';
    
    try {
        const detection = await faceapi.detectSingleFace(videoRef.value, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();
        
        if (!detection) {
            throw new Error('Wajah tidak terdeteksi. Paskan wajah dalam lingkaran dan pastikan pencahayaan cukup.');
        }

        const match = faceMatcher.findBestMatch(detection.descriptor);
        if (match.label === 'unknown') {
            throw new Error('Verifikasi Gagal: Wajah tidak cocok!');
        }

        statusMsg.value = 'Cocok! Mengirim absen...';

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
        statusMsg.value = 'Arahkan wajah ke lingkaran, lalu klik Absen.';
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <Head title="Live Absensi" />

    <div class="flex flex-col h-[100dvh] bg-gray-50 dark:bg-gray-900 overflow-hidden relative">
        
        <!-- Minimal Header -->
        <div class="bg-indigo-600 px-4 py-3 flex items-center justify-between shadow-md relative z-20 shrink-0">
            <div>
                <h2 class="text-white font-bold text-sm">Live Absensi</h2>
                <p class="text-indigo-200 text-[10px] uppercase tracking-wider font-semibold">Verifikasi Biometrik & Geolokasi</p>
            </div>
            <Link :href="route('admin.absensi.index')" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors shadow-sm backdrop-blur-sm">
                <XMarkIcon class="w-5 h-5 text-white" />
            </Link>
        </div>

        <!-- Loading / Error states -->
        <div v-if="loading" class="flex-1 flex flex-col items-center justify-center p-8">
            <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            <p class="mt-4 text-gray-600 dark:text-gray-400 font-medium text-center text-sm">{{ statusMsg }}</p>
        </div>

        <div v-else-if="isError" class="flex-1 flex flex-col items-center justify-center p-8 text-center">
            <XCircleIcon class="w-16 h-16 text-red-500 mb-4" />
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Terjadi Kendala</h3>
            <p class="text-red-500 text-sm mb-6">{{ statusMsg }}</p>
            <div v-if="!karyawan.face_descriptor">
                <Link :href="route('admin.absensi.register-face')" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-full text-sm font-semibold hover:bg-indigo-700 transition">
                    <CameraIcon class="w-4 h-4" /> Daftar Wajah
                </Link>
            </div>
        </div>

        <!-- Active Camera State -->
        <div v-else class="flex flex-col flex-1 h-full w-full relative">
            
            <!-- Camera View (Lingkaran) -->
            <div class="flex-1 flex flex-col items-center justify-center p-4 relative z-10 shrink-0">
                <div class="relative w-64 h-64 sm:w-80 sm:h-80 rounded-full overflow-hidden shadow-[0_0_40px_rgba(79,70,229,0.2)] border-[6px] border-white dark:border-gray-800 bg-black">
                    <video ref="videoRef" autoplay muted playsinline class="w-full h-full object-cover transform scale-x-[-1]"></video>
                    
                    <!-- Processing Overlay inside circle -->
                    <div v-if="isProcessing" class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center text-white backdrop-blur-md z-20">
                        <div class="w-8 h-8 border-4 border-white/20 border-t-indigo-400 rounded-full animate-spin mb-3"></div>
                        <span class="text-xs font-bold tracking-wider px-4 text-center">{{ statusMsg }}</span>
                    </div>
                </div>
                <p v-if="!isProcessing" class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400 text-center px-4 max-w-xs">{{ statusMsg }}</p>
            </div>

            <!-- Bottom Sheet (Controls) -->
            <div class="bg-white dark:bg-gray-800 rounded-t-[2rem] shadow-[0_-10px_40px_rgba(0,0,0,0.08)] p-4 pt-5 pb-[100px] relative z-20 w-full mt-auto mx-auto max-w-2xl border-t border-gray-100 dark:border-gray-700 overflow-y-auto">
                
                <!-- Decorative pull indicator -->
                <div class="w-12 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full mx-auto mb-4"></div>
                
                <!-- Location Section -->
                <div class="mb-5">
                    <div class="flex items-center justify-between mb-2 px-1">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Lokasi Saat Ini</h4>
                        <button @click="fetchLocation" :disabled="isLocating" class="flex items-center text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1.5 rounded-full">
                            <ArrowPathIcon class="w-3.5 h-3.5 mr-1.5" :class="{ 'animate-spin': isLocating }" />
                            {{ isLocating ? 'Mencari...' : 'Refresh' }}
                        </button>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl p-3 border border-gray-100 dark:border-gray-700/50 flex items-start gap-3">
                        <div class="mt-0.5 flex-shrink-0">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
                                <MapPinIcon class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white line-clamp-2 leading-snug">
                                {{ locationName }}
                            </p>
                            <p v-if="location" class="text-[10px] text-gray-500 font-mono mt-1 tracking-wider bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded inline-block">
                                {{ location.lat.toFixed(6) }}, {{ location.lng.toFixed(6) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Dinas Luar Toggle -->
                <div class="mb-2 bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-200/60 dark:border-amber-700/30 p-3 transition-all">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                                <BriefcaseIcon class="w-4 h-4 text-amber-600 dark:text-amber-500" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-amber-900 dark:text-amber-400">Dinas Luar</p>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="checkbox" v-model="isDinasLuar" class="sr-only peer" />
                            <div class="w-11 h-6 bg-amber-200/50 dark:bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500 shadow-inner"></div>
                        </div>
                    </label>
                    
                    <div v-if="isDinasLuar" class="mt-3 overflow-hidden transition-all duration-300 ease-in-out">
                        <textarea
                            v-model="catatanDinasLuar"
                            rows="2"
                            placeholder="Keterangan agenda dinas luar..."
                            class="w-full border-amber-300/50 dark:border-amber-700/50 bg-white dark:bg-gray-800 rounded-xl text-sm p-3 focus:ring-amber-500 focus:border-amber-500 placeholder-amber-300 dark:placeholder-amber-700/50 dark:text-gray-200 resize-none shadow-sm transition-all"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Fixed Action Buttons -->
            <div class="fixed bottom-0 left-0 right-0 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md border-t border-gray-100 dark:border-gray-700 p-4 pb-safe shadow-[0_-10px_20px_rgba(0,0,0,0.05)] z-50">
                <div class="max-w-2xl mx-auto flex gap-3">
                    <button 
                        @click="performClock('in')"
                        :disabled="isProcessing || !location || (absensi && absensi.waktu_masuk)"
                        :class="[
                            'flex-1 py-3.5 rounded-2xl font-black text-sm tracking-wider flex items-center justify-center gap-2 transition-all duration-200 shadow-lg',
                            (absensi && absensi.waktu_masuk) 
                                ? 'bg-gray-100 text-gray-400 border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500 cursor-not-allowed shadow-none' 
                                : (!location) 
                                    ? 'bg-emerald-100 text-emerald-400 cursor-not-allowed shadow-none dark:bg-emerald-900/20'
                                    : 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white hover:from-emerald-500 hover:to-emerald-700 active:scale-[0.98] shadow-emerald-500/30 border border-transparent'
                        ]"
                    >
                        <CheckCircleIcon class="w-6 h-6" />
                        <span>{{ (absensi && absensi.waktu_masuk) ? 'SUDAH MASUK' : 'CLOCK IN' }}</span>
                    </button>
                    
                    <button 
                        @click="performClock('out')"
                        :disabled="isProcessing || !location || (!absensi || !absensi.waktu_masuk || absensi.waktu_keluar)"
                        :class="[
                            'flex-1 py-3.5 rounded-2xl font-black text-sm tracking-wider flex items-center justify-center gap-2 transition-all duration-200 shadow-lg',
                            (!absensi || !absensi.waktu_masuk || absensi.waktu_keluar) 
                                ? 'bg-gray-100 text-gray-400 border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-500 cursor-not-allowed shadow-none' 
                                : (!location)
                                    ? 'bg-rose-100 text-rose-400 cursor-not-allowed shadow-none dark:bg-rose-900/20'
                                    : 'bg-gradient-to-br from-rose-400 to-rose-600 text-white hover:from-rose-500 hover:to-rose-700 active:scale-[0.98] shadow-rose-500/30 border border-transparent'
                        ]"
                    >
                        <XCircleIcon class="w-6 h-6" />
                        <span>{{ (absensi && absensi.waktu_keluar) ? 'SELESAI' : 'CLOCK OUT' }}</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</template>
