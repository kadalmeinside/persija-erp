<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { MagnifyingGlassIcon, PlusIcon, PencilSquareIcon, TrashIcon, MapPinIcon, CalendarIcon } from '@heroicons/vue/24/solid';
import { debounce } from 'lodash';

const props = defineProps({
    events: Object,
    filters: Object
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('admin.company-events.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

// --- MODAL STATE ---
const showModal = ref(false);
const editingEvent = ref(null);
const form = useForm({
    title: '',
    start_date: '',
    end_date: '',
    description: '',
    location: ''
});

const openCreateModal = () => {
    editingEvent.value = null;
    form.reset();
    // Default today with time
    const now = new Date();
    // Adjust to local timezone for datetime-local input
    const localIso = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    form.start_date = localIso;
    form.end_date = localIso;
    showModal.value = true;
};

const openEditModal = (event) => {
    editingEvent.value = event;
    form.title = event.title;
    
    // Fix: Ensure we extract YYYY-MM-DDTHH:mm for datetime-local
    // Check if date string exists and is long enough
    if (event.start_date && event.start_date.length >= 16) {
        form.start_date = event.start_date.slice(0, 16);
    } else {
        form.start_date = '';
    }
    
    if (event.end_date && event.end_date.length >= 16) {
        form.end_date = event.end_date.slice(0, 16);
    } else {
        form.end_date = '';
    }
    
    form.description = event.description;
    form.location = event.location;
    showModal.value = true;
};

const submitForm = () => {
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

        <div class="py-12 pt-4">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="events.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada agenda perusahaan.</td>
                                </tr>
                                <tr v-for="event in events.data" :key="event.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">{{ event.title }}</div>
                                        <div class="text-sm text-gray-500 mt-1">{{ event.description }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center text-sm text-gray-900">
                                            <CalendarIcon class="w-4 h-4 mr-1 text-gray-400" />
                                            {{ formatDate(event.start_date) }}
                                        </div>
                                        <div v-if="event.end_date" class="text-xs text-gray-500 ml-5 mt-1">
                                            s/d {{ formatDate(event.end_date) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div v-if="event.location" class="flex items-center">
                                            <MapPinIcon class="w-4 h-4 mr-1 text-gray-400" />
                                            {{ event.location }}
                                        </div>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-6 py-4 text-center space-x-2">
                                        <button @click="openEditModal(event)" class="text-indigo-600 hover:text-indigo-900">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                        <button @click="deleteEvent(event)" class="text-red-600 hover:text-red-900">
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

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingEvent ? 'Edit Agenda' : 'Tambah Agenda Baru' }}
                </h2>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Judul Agenda" />
                        <TextInput v-model="form.title" class="w-full mt-1" placeholder="Rapat Bulanan..." />
                        <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Mulai" />
                            <TextInput type="datetime-local" v-model="form.start_date" class="w-full mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Selesai" />
                            <TextInput type="datetime-local" v-model="form.end_date" class="w-full mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Lokasi (Opsional)" />
                        <TextInput v-model="form.location" class="w-full mt-1" placeholder="Ruang Meeting Lt. 2..." />
                    </div>

                    <div>
                        <InputLabel value="Deskripsi" />
                        <textarea v-model="form.description" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3"></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton @click="showModal = false">Batal</SecondaryButton>
                    <PrimaryButton @click="submitForm" :disabled="form.processing">Simpan</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
