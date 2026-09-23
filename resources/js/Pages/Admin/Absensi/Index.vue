<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { UserGroupIcon, MapPinIcon, PhotoIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import { ref, watch, onMounted, nextTick, computed } from 'vue';

dayjs.locale('id');

const props = defineProps({
    absensis: Object,
    filters: Object
});

const filterForm = ref({
    date: props.filters?.date || dayjs().format('YYYY-MM-DD'),
    status: props.filters?.status || '',
    time_in: props.filters?.time_in || '',
    time_out: props.filters?.time_out || '',
});

const queryString = computed(() => {
    const params = new URLSearchParams();
    if (filterForm.value.date) params.append('date', filterForm.value.date);
    if (filterForm.value.status) params.append('status', filterForm.value.status);
    if (filterForm.value.time_in) params.append('time_in', filterForm.value.time_in);
    if (filterForm.value.time_out) params.append('time_out', filterForm.value.time_out);
    return params.toString();
});

const printUrl = computed(() => route('admin.absensi.print') + '?' + queryString.value);
const pdfUrl = computed(() => route('admin.absensi.export-pdf') + '?' + queryString.value);

// Generate dates around the selected date
const dateList = ref([]);
const generateDateList = (centerDate) => {
    const list = [];
    for (let i = -15; i <= 15; i++) {
        list.push({
            obj: dayjs(centerDate).add(i, 'day'),
            dateStr: dayjs(centerDate).add(i, 'day').format('YYYY-MM-DD')
        });
    }
    dateList.value = list;
};
generateDateList(filterForm.value.date);

const selectDate = (dateStr) => {
    filterForm.value.date = dateStr;
    generateDateList(dateStr);
};

const dateScrollContainer = ref(null);
onMounted(() => {
    scrollToActiveDate();
});

const scrollToActiveDate = () => {
    nextTick(() => {
        if (dateScrollContainer.value) {
            const activeEl = dateScrollContainer.value.querySelector('.date-active');
            if (activeEl) {
                const scrollLeft = activeEl.offsetLeft - (dateScrollContainer.value.offsetWidth / 2) + (activeEl.offsetWidth / 2);
                dateScrollContainer.value.scrollTo({ left: scrollLeft, behavior: 'smooth' });
            }
        }
    });
};

watch(filterForm, (newVal) => {
    router.get(route('admin.absensi.rekap'), newVal, { preserveState: true, replace: true, preserveScroll: true });
}, { deep: true });

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
    return dayjs(dateStr).format('DD MMM YYYY');
};

const formatPaginationLabel = (label) => {
    if (!label) return '';
    let formatted = label;
    if (formatted.includes('pagination.previous')) {
        formatted = '&laquo; Sebelumnya';
    }
    if (formatted.includes('pagination.next')) {
        formatted = 'Selanjutnya &raquo;';
    }
    return formatted;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Rekap Absensi Karyawan" />

        <template #header>
            <h2 class="font-semibold text-lg md:text-xl text-gray-800 leading-tight truncate">Rekap Absensi (HR)</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                    <!-- Daily Date Scroller -->
                    <div class="border-b border-gray-100 bg-gray-50/50 pt-4 pb-2 relative">
                        <div class="px-4 mb-2 flex justify-between items-center text-sm font-semibold text-gray-700">
                            <span>Pilih Tanggal:</span>
                            <button @click="selectDate(dayjs().format('YYYY-MM-DD'))" type="button" class="text-indigo-600 hover:text-indigo-800 text-xs font-bold uppercase">
                                Hari Ini
                            </button>
                        </div>
                        <div class="flex overflow-x-auto snap-x snap-mandatory hide-scrollbar px-4 pb-2 gap-2" ref="dateScrollContainer">
                            <button 
                                v-for="d in dateList" 
                                :key="d.dateStr"
                                @click="selectDate(d.dateStr)"
                                type="button"
                                :class="[
                                    'snap-center flex-shrink-0 flex flex-col items-center justify-center w-16 h-20 rounded-xl border transition-all duration-200',
                                    filterForm.date === d.dateStr 
                                        ? 'bg-indigo-600 border-indigo-600 text-white shadow-md scale-105 date-active' 
                                        : 'bg-white border-gray-200 text-gray-700 hover:border-indigo-300 hover:bg-indigo-50'
                                ]"
                            >
                                <span class="text-xs font-medium uppercase tracking-wider mb-1" :class="filterForm.date === d.dateStr ? 'text-indigo-100' : 'text-gray-400'">{{ d.obj.format('ddd') }}</span>
                                <span class="text-xl font-black">{{ d.obj.format('DD') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Filters Section -->
                    <div class="p-4 bg-white border-b border-gray-100 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Status Kehadiran</label>
                            <select v-model="filterForm.status" class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Semua Status</option>
                                <option value="Hadir">Hadir</option>
                                <option value="Terlambat">Terlambat</option>
                                <option value="Lupa Checkout">Lupa Checkout</option>
                                <option value="Luar Kantor">Luar Kantor</option>
                                <option value="Tidak Hadir">Tidak Hadir</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Waktu Masuk (Setelah jam)</label>
                            <input type="time" v-model="filterForm.time_in" class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Waktu Keluar (Sebelum jam)</label>
                            <input type="time" v-model="filterForm.time_out" class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div class="flex flex-row gap-2 justify-end items-end h-full mt-2 md:mt-0">
                            <a :href="printUrl" target="_blank" class="inline-flex justify-center items-center px-3 py-1.5 bg-white border border-gray-300 rounded text-[10px] font-bold text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Print
                            </a>
                            <a :href="pdfUrl" class="inline-flex justify-center items-center px-3 py-1.5 bg-red-600 border border-transparent rounded text-[10px] font-bold text-white uppercase tracking-widest shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                PDF
                            </a>
                        </div>
                    </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="p-4">Tanggal</th>
                                <th class="p-4">Karyawan</th>
                                <th class="p-4">Cabang</th>
                                <th class="p-4">Waktu Masuk</th>
                                <th class="p-4">Waktu Keluar</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Keterangan</th>
                                <th class="p-4">Foto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="item in absensis.data" :key="item.id" class="hover:bg-gray-50">
                                <td class="p-4 whitespace-nowrap">{{ formatDate(item.tanggal) }}</td>
                                <td class="p-4 font-medium text-gray-900">{{ item.karyawan?.nama_lengkap || 'Unknown' }}</td>
                                <td class="p-4 text-gray-600">{{ item.lokasi_kantor?.nama_kantor || '-' }}</td>
                                <td class="p-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold">{{ formatTime(item.waktu_masuk) }}</span>
                                        <button v-if="item.lat_masuk" @click="openMapModal(item.lat_masuk, item.lng_masuk, 'In')" type="button" class="text-[10px] text-indigo-600 hover:underline flex items-center gap-1 mt-0.5 w-fit">
                                            <MapPinIcon class="w-3 h-3" /> Maps
                                        </button>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-col">
                                        <template v-if="item.waktu_keluar">
                                            <span class="font-bold">{{ formatTime(item.waktu_keluar) }}</span>
                                            <button v-if="item.lat_keluar" @click="openMapModal(item.lat_keluar, item.lng_keluar, 'Out')" type="button" class="text-[10px] text-indigo-600 hover:underline flex items-center gap-1 mt-0.5 w-fit">
                                                <MapPinIcon class="w-3 h-3" /> Maps
                                            </button>
                                        </template>
                                        <span v-else class="inline-flex items-center gap-1 text-xs font-semibold text-red-600 bg-red-50 border border-red-200 rounded px-2 py-0.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                            Belum Checkout
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-col gap-1">
                                        <span :class="[
                                            'px-2 py-1 rounded text-xs font-semibold w-fit',
                                            item.status_kehadiran === 'Hadir' ? 'bg-green-100 text-green-700' :
                                            item.status_kehadiran === 'Terlambat' ? 'bg-yellow-100 text-yellow-700' :
                                            'bg-red-100 text-red-700'
                                        ]">
                                            {{ item.status_kehadiran }}
                                        </span>
                                        <span v-if="item.is_dinas_luar" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700 w-fit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                            Dinas Luar
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4 max-w-[180px]">
                                    <p v-if="item.catatan" class="text-xs text-gray-600 italic line-clamp-2" :title="item.catatan">{{ item.catatan }}</p>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                <td class="p-4">
                                    <div class="flex gap-2">
                                        <button v-if="item.foto_masuk" @click="openPhotoModal('Foto Clock In', `/storage/${item.foto_masuk}`)" type="button" class="text-blue-600 hover:text-blue-800" title="Foto Masuk">
                                            <PhotoIcon class="w-5 h-5" />
                                        </button>
                                        <button v-if="item.foto_keluar" @click="openPhotoModal('Foto Clock Out', `/storage/${item.foto_keluar}`)" type="button" class="text-orange-600 hover:text-orange-800" title="Foto Keluar">
                                            <PhotoIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!absensis.data || absensis.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-gray-500">
                                    Belum ada data absensi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination (Simple) -->
                <div v-if="absensis.links" class="p-4 border-t border-gray-100 flex items-center justify-between text-sm">
                    <div class="text-gray-500">
                        <span v-if="absensis.total > 0">Menampilkan {{ absensis.from }} - {{ absensis.to }} dari {{ absensis.total }}</span>
                        <span v-else>Tidak ada data</span>
                    </div>
                    <div class="flex flex-wrap gap-1 mt-2 sm:mt-0">
                        <template v-for="(link, idx) in absensis.links" :key="idx">
                            <div v-if="link.url === null" 
                                 class="px-3 py-1.5 border rounded-lg bg-gray-50 text-gray-400 cursor-not-allowed text-sm"
                                 v-html="formatPaginationLabel(link.label)">
                            </div>
                            <Link v-else :href="link.url" 
                                  class="px-3 py-1.5 border rounded-lg transition-colors text-sm font-medium"
                                  :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'"
                                  v-html="formatPaginationLabel(link.label)"
                                  preserve-scroll />
                        </template>
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

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
