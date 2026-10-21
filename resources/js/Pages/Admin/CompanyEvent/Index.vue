<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { 
    MagnifyingGlassIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    MapPinIcon, 
    CalendarIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    events: Object,
    filters: Object
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('admin.company-events.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

// --- CONSTANTS ---
const commonRooms = [
    'Ruang Meeting Utama',
    'Ruang Meeting 2',
    'Ruang Basecamp'
];

const getCategoryLabel = (cat) => {
    switch (cat) {
        case 'meeting': return 'Rapat / Meeting';
        case 'general': return 'Kegiatan Umum';
        case 'training': return 'Pelatihan / Workshop';
        case 'online': return 'Meeting Online';
        case 'other': return 'Lainnya';
        default: return 'Rapat / Meeting';
    }
};

const getCategoryBadgeClass = (cat) => {
    switch (cat) {
        case 'meeting': return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'general': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'training': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'online': return 'bg-sky-50 text-sky-700 border-sky-200';
        case 'other': return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        default: return 'bg-purple-50 text-purple-700 border-purple-200';
    }
};

// --- MODAL STATE ---
const showModal = ref(false);
const editingEvent = ref(null);
const clientError = ref('');

const form = useForm({
    title: '',
    category: '',
    start_date: '',
    end_date: '',
    description: '',
    location: ''
});

const setRoom = (room) => {
    form.location = room;
    if (form.errors.location) {
        form.clearErrors('location');
    }
};

const openCreateModal = () => {
    editingEvent.value = null;
    form.reset();
    form.clearErrors();
    form.category = '';
    clientError.value = '';

    const now = new Date();
    // Default next whole hour
    now.setHours(now.getHours() + 1, 0, 0, 0);
    const end = new Date(now.getTime() + 60 * 60 * 1000);

    const toLocalIso = (d) => new Date(d.getTime() - (d.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    form.start_date = toLocalIso(now);
    form.end_date = toLocalIso(end);
    showModal.value = true;
};

const openEditModal = (event) => {
    editingEvent.value = event;
    form.clearErrors();
    clientError.value = '';

    form.title = event.title;
    form.category = event.category || '';
    form.start_date = event.start_date && event.start_date.length >= 16 ? event.start_date.slice(0, 16) : '';
    form.end_date = event.end_date && event.end_date.length >= 16 ? event.end_date.slice(0, 16) : '';
    form.description = event.description || '';
    form.location = event.location || '';
    showModal.value = true;
};

const submitForm = () => {
    clientError.value = '';

    if (!form.category) {
        clientError.value = 'Silakan pilih Jenis Agenda terlebih dahulu.';
        return;
    }

    if (form.end_date && form.start_date && new Date(form.end_date) <= new Date(form.start_date)) {
        clientError.value = 'Waktu selesai harus lebih lambat dari waktu mulai.';
        return;
    }

    if (editingEvent.value) {
        form.put(route('admin.company-events.update', editingEvent.value.id), {
            onSuccess: () => { showModal.value = false; }
        });
    } else {
        form.post(route('admin.company-events.store'), {
            onSuccess: () => { showModal.value = false; }
        });
    }
};

const deleteEvent = (event) => {
    if (confirm('Yakin ingin menghapus agenda ' + event.title + '?')) {
        router.delete(route('admin.company-events.destroy', event.id));
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit'
    });
};
</script>

<template>
    <Head title="Manajemen Agenda Perusahaan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Manajemen Agenda Perusahaan
            </h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <!-- Toolbar -->
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <PrimaryButton @click="openCreateModal" class="flex items-center gap-2">
                            <PlusIcon class="w-4 h-4" /> Tambah Agenda
                        </PrimaryButton>

                        <div class="relative w-full md:w-1/3">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                            </div>
                            <TextInput v-model="search" placeholder="Cari Agenda..." class="pl-10 w-full" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agenda</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ruangan / Lokasi</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="events.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada agenda perusahaan.</td>
                                </tr>
                                <tr v-for="event in events.data" :key="event.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-bold text-gray-900">{{ event.title }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold border"
                                                :class="getCategoryBadgeClass(event.category)">
                                                {{ getCategoryLabel(event.category) }}
                                            </span>
                                        </div>
                                        <div v-if="event.description" class="text-xs text-gray-500 mt-1 line-clamp-2">{{ event.description }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center text-sm text-gray-900">
                                            <CalendarIcon class="w-4 h-4 mr-1 text-gray-400 shrink-0" />
                                            {{ formatDate(event.start_date) }}
                                        </div>
                                        <div v-if="event.end_date" class="text-xs text-gray-500 ml-5 mt-0.5">
                                            s/d {{ formatDate(event.end_date) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div v-if="event.location" class="flex items-center">
                                            <MapPinIcon class="w-4 h-4 mr-1 text-purple-600 shrink-0" />
                                            <span class="font-medium text-gray-800">{{ event.location }}</span>
                                        </div>
                                        <span v-else class="text-gray-400 italic">Tidak ditentukan</span>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2 whitespace-nowrap">
                                        <button @click="openEditModal(event)" class="text-indigo-600 hover:text-indigo-900 p-1" title="Edit Agenda">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                        <button @click="deleteEvent(event)" class="text-red-600 hover:text-red-900 p-1" title="Hapus Agenda">
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4">
                    <Pagination :links="events.links" />
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal with Overlap & Conflict Protection -->
        <Modal :show="showModal" @close="showModal = false" maxWidth="lg">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">
                            {{ editingEvent ? 'Edit Agenda Perusahaan' : 'Tambah Agenda / Booking Ruangan' }}
                        </h2>
                    </div>
                </div>
                
                <!-- Conflict / Error Alert Banner (Seperti di Mobile) -->
                <div v-if="form.errors.location || clientError" class="mb-4 p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-start gap-2.5 shadow-sm">
                    <ExclamationTriangleIcon class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                    <div class="space-y-0.5">
                        <p class="font-bold text-rose-900">Peringatan: Bentrok Jadwal / Kesalahan Input</p>
                        <p class="text-rose-700 leading-relaxed">{{ form.errors.location || clientError }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Jenis / Kategori Agenda -->
                    <div>
                        <InputLabel value="Jenis Agenda *" />
                        <select 
                            v-model="form.category" 
                            class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-medium"
                        >
                            <option value="" disabled>Pilih Jenis Agenda</option>
                            <option value="meeting">Rapat / Meeting Ruangan</option>
                            <option value="general">Kegiatan Umum Perusahaan</option>
                            <option value="training">Pelatihan / Workshop</option>
                            <option value="online">Meeting Online / Virtual</option>
                            <option value="other">Agenda Lainnya</option>
                        </select>
                        <InputError :message="form.errors.category" class="mt-1" />
                    </div>

                    <!-- Judul Agenda -->
                    <div>
                        <InputLabel value="Judul Agenda *" />
                        <TextInput v-model="form.title" class="w-full mt-1" placeholder="Misal: Rapat Koordinasi HR & Operasional" />
                        <InputError :message="form.errors.title" class="mt-1" />
                    </div>

                    <!-- Waktu Mulai & Selesai -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Waktu Mulai *" />
                            <TextInput type="datetime-local" v-model="form.start_date" class="w-full mt-1" />
                            <InputError :message="form.errors.start_date" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Waktu Selesai *" />
                            <TextInput type="datetime-local" v-model="form.end_date" class="w-full mt-1" />
                            <InputError :message="form.errors.end_date" class="mt-1" />
                        </div>
                    </div>

                    <!-- Ruangan / Lokasi dengan Quick Chips -->
                    <div>
                        <InputLabel value="Ruangan / Tempat" />
                        <TextInput 
                            v-model="form.location" 
                            list="common-meeting-rooms-list"
                            class="w-full mt-1" 
                            placeholder="Pilih atau ketik ruangan/tempat..." 
                        />
                        <datalist id="common-meeting-rooms-list">
                            <option v-for="room in commonRooms" :key="room" :value="room" />
                        </datalist>
                        <InputError :message="form.errors.location" class="mt-1 font-semibold text-rose-600" />

                        <!-- Quick Room Chips -->
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-xs text-gray-500 mr-1">Rekomendasi Ruangan:</span>
                            <button
                                v-for="room in commonRooms"
                                :key="room"
                                type="button"
                                @click="setRoom(room)"
                                class="text-xs px-2.5 py-1 rounded-full border transition cursor-pointer"
                                :class="form.location === room 
                                    ? 'bg-purple-100 text-purple-700 border-purple-300 font-bold shadow-xs' 
                                    : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'"
                            >
                                {{ room }}
                            </button>
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <InputLabel value="Deskripsi / Catatan Agenda (Opsional)" />
                        <textarea 
                            v-model="form.description" 
                            class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                            rows="3"
                            placeholder="Rincian pembahasan, peserta rapat, atau perlengkapan yang dibutuhkan..."
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submitForm" :disabled="form.processing">
                        {{ editingEvent ? 'Simpan Perubahan' : 'Simpan Agenda' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
