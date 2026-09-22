<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { CalendarIcon, MapPinIcon, PhotoIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import { ref } from 'vue';

dayjs.locale('id');

const props = defineProps({
    absensis: Array
});

const isModalOpen = ref(false);
const modalType = ref(''); // 'photo' or 'map'
const modalTitle = ref('');
const modalContent = ref(''); // URL for photo or map
const modalExternalLink = ref(''); // For 'Open in Google Maps' button

const openPhotoModal = (title, url) => {
    modalType.value = 'photo';
    modalTitle.value = title;
    modalContent.value = url;
    isModalOpen.value = true;
};

const openMapModal = (lat, lng, type) => {
    modalType.value = 'map';
    modalTitle.value = `Lokasi Clock ${type}`;
    // Use simple embed URL for Google Maps
    modalContent.value = `https://maps.google.com/maps?q=${lat},${lng}&t=&z=15&ie=UTF8&iwloc=&output=embed`;
    modalExternalLink.value = `https://maps.google.com/?q=${lat},${lng}`;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => {
        modalContent.value = '';
        modalExternalLink.value = '';
    }, 300);
};


const formatTime = (timeStr) => {
    if (!timeStr) return '-';
    return dayjs(timeStr).format('HH:mm');
};
const formatDate = (dateStr) => {
    return dayjs(dateStr).format('dddd, DD MMM YYYY');
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Riwayat Absensi" />

        <template #header>
            <h2 class="font-semibold text-lg md:text-xl text-gray-800 leading-tight truncate">Riwayat Absensi</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">

                <div class="p-0">
                    <div v-if="!absensis || absensis.length === 0" class="p-12 text-center">
                        <CalendarIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900">Belum ada data</h3>
                        <p class="text-gray-500 mt-1">Anda belum memiliki riwayat absensi.</p>
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="item in absensis" :key="item.id" class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                
                                <div>
                                    <h4 class="font-bold text-gray-900 text-lg">{{ formatDate(item.tanggal) }}</h4>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span :class="[
                                            'px-3 py-1 rounded-full text-xs font-semibold',
                                            item.status_kehadiran === 'Hadir' ? 'bg-green-100 text-green-700' :
                                            item.status_kehadiran === 'Terlambat' ? 'bg-yellow-100 text-yellow-700' :
                                            'bg-red-100 text-red-700'
                                        ]">
                                            {{ item.status_kehadiran }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 md:gap-8 bg-gray-100 rounded-xl p-4 md:w-auto w-full justify-around">
                                    <div class="text-center flex flex-col items-center">
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">IN</p>
                                        <p class="text-xl font-black text-gray-800">{{ formatTime(item.waktu_masuk) }}</p>
                                        <div class="flex items-center gap-3 mt-2">
                                            <button v-if="item.lat_masuk" @click="openMapModal(item.lat_masuk, item.lng_masuk, 'In')" type="button" class="text-xs text-indigo-600 hover:underline flex items-center justify-center gap-1">
                                                <MapPinIcon class="w-3 h-3" /> Peta
                                            </button>
                                            <button v-if="item.foto_masuk" @click="openPhotoModal('Foto Clock In', `/storage/${item.foto_masuk}`)" type="button" class="text-xs text-blue-600 hover:underline flex items-center justify-center gap-1">
                                                <PhotoIcon class="w-3 h-3" /> Foto
                                            </button>
                                        </div>
                                    </div>
                                    <div class="w-px h-12 bg-gray-300"></div>
                                    <div class="text-center flex flex-col items-center">
                                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">OUT</p>
                                        <p class="text-xl font-black text-gray-800">{{ formatTime(item.waktu_keluar) }}</p>
                                        <div class="flex items-center gap-3 mt-2">
                                            <button v-if="item.lat_keluar" @click="openMapModal(item.lat_keluar, item.lng_keluar, 'Out')" type="button" class="text-xs text-indigo-600 hover:underline flex items-center justify-center gap-1">
                                                <MapPinIcon class="w-3 h-3" /> Peta
                                            </button>
                                            <button v-if="item.foto_keluar" @click="openPhotoModal('Foto Clock Out', `/storage/${item.foto_keluar}`)" type="button" class="text-xs text-orange-600 hover:underline flex items-center justify-center gap-1">
                                                <PhotoIcon class="w-3 h-3" /> Foto
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <!-- Reusable Modal for Photo & Map -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <div class="p-6">
                <div class="mb-4 pr-8">
                    <h3 class="text-lg font-medium text-gray-900">{{ modalTitle }}</h3>
                </div>

                <div v-if="modalType === 'photo'" class="mt-2 flex justify-center">
                    <img :src="modalContent" alt="Foto Absensi" class="rounded-lg max-h-96 object-contain shadow-sm border border-gray-200" />
                </div>

                <div v-else-if="modalType === 'map'" class="mt-2">
                    <div class="relative w-full h-64 rounded-lg overflow-hidden border border-gray-200 shadow-sm mb-4">
                        <iframe
                            :src="modalContent"
                            width="100%"
                            height="100%"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        ></iframe>
                    </div>
                    <div class="flex justify-end">
                        <a :href="modalExternalLink" target="_blank" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 gap-2">
                            <MapPinIcon class="w-4 h-4" /> Buka di Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
