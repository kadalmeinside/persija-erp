<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    MapPinIcon, 
    BuildingOfficeIcon, 
    ArrowPathIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    lokasiKantor: Object
});

const form = useForm({
    nama_kantor: props.lokasiKantor.nama_kantor || '',
    latitude: props.lokasiKantor.latitude || '',
    longitude: props.lokasiKantor.longitude || '',
    radius_meter: props.lokasiKantor.radius_meter || 50,
    is_active: props.lokasiKantor.is_active !== undefined ? props.lokasiKantor.is_active : true,
});

const isLocating = ref(false);

const getCurrentLocation = () => {
    if (!("geolocation" in navigator)) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Geolocation tidak didukung oleh browser Anda.',
        });
        return;
    }

    isLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude;
            form.longitude = position.coords.longitude;
            isLocating.value = false;
            
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Titik koordinat berhasil diperbarui sesuai lokasi Anda saat ini.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        },
        (error) => {
            isLocating.value = false;
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Tidak dapat mengambil lokasi. Pastikan izin lokasi diberikan.',
            });
        },
        { enableHighAccuracy: true }
    );
};

const submit = () => {
    form.post(route('admin.absensi.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Tersimpan',
                text: 'Pengaturan lokasi absensi berhasil diperbarui.',
                showConfirmButton: false,
                timer: 2000
            });
        }
    });
};
</script>

<template>
    <Head title="Pengaturan Lokasi Absensi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <MapPinIcon class="w-6 h-6 text-indigo-600" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengaturan Lokasi Absensi</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white overflow-hidden shadow-sm rounded-[2rem] border border-gray-100 p-6 md:p-8">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Koordinat & Radius Toleransi Kantor</h3>
                        <p class="text-sm text-gray-500">Tentukan titik kordinat (GPS) kantor dan batas jarak maksimal (radius) agar karyawan dapat melakukan Clock In dan Clock Out.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- Nama Kantor -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lokasi / Kantor</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <BuildingOfficeIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <input type="text" v-model="form.nama_kantor" required
                                    class="pl-10 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-3 bg-gray-50 hover:bg-white transition-colors"
                                    placeholder="Contoh: Head Office Jakarta">
                            </div>
                            <div v-if="form.errors.nama_kantor" class="text-red-500 text-xs mt-1">{{ form.errors.nama_kantor }}</div>
                        </div>

                        <!-- Kordinat -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                                <input type="number" step="any" v-model="form.latitude" required
                                    class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-3 bg-gray-50 hover:bg-white transition-colors"
                                    placeholder="-6.1234567">
                                <div v-if="form.errors.latitude" class="text-red-500 text-xs mt-1">{{ form.errors.latitude }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                                <input type="number" step="any" v-model="form.longitude" required
                                    class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-3 bg-gray-50 hover:bg-white transition-colors"
                                    placeholder="106.1234567">
                                <div v-if="form.errors.longitude" class="text-red-500 text-xs mt-1">{{ form.errors.longitude }}</div>
                            </div>
                        </div>

                        <!-- Get Current Location Button -->
                        <div class="flex justify-end">
                            <button type="button" @click="getCurrentLocation" :disabled="isLocating"
                                class="inline-flex items-center px-4 py-2 border border-indigo-200 text-sm font-medium rounded-xl text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors disabled:opacity-50">
                                <ArrowPathIcon v-if="isLocating" class="animate-spin -ml-1 mr-2 h-4 w-4" />
                                <MapPinIcon v-else class="-ml-1 mr-2 h-4 w-4" />
                                {{ isLocating ? 'Mencari...' : 'Gunakan Lokasi Saat Ini' }}
                            </button>
                        </div>

                        <hr class="border-gray-100">

                        <!-- Radius -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Radius Toleransi (Meter)</label>
                            <div class="flex items-center gap-4">
                                <input type="number" min="1" v-model="form.radius_meter" required
                                    class="block w-32 rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-3 bg-gray-50 hover:bg-white transition-colors">
                                <span class="text-gray-500 text-sm">Meter dari titik koordinat pusat</span>
                            </div>
                            <div v-if="form.errors.radius_meter" class="text-red-500 text-xs mt-1">{{ form.errors.radius_meter }}</div>
                        </div>

                        <!-- Status Aktif -->
                        <div class="flex items-center gap-3 bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <input type="checkbox" id="is_active" v-model="form.is_active" 
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-5 h-5">
                            <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">
                                Aktifkan Lokasi Ini
                            </label>
                        </div>

                        <div class="pt-4 flex justify-end gap-3">
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-all">
                                <CheckCircleIcon class="-ml-1 mr-2 h-5 w-5" />
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
