<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';
import { PlusIcon, FunnelIcon, Squares2X2Icon, ListBulletIcon, ChevronLeftIcon, ChevronRightIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    events: Array,
    currentMonth: Number,
    currentYear: Number,
    canManageEvents: Boolean
});

const monthNames = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
];

const daysOfWeek = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

// --- STATE ---
const viewMode = ref('grid'); 
const filters = reactive({
    holidays: true,
    companyEvents: true,
    myLeave: true,
    otherLeave: true,
    tasks: true,
});

// Event Management State
const showEventModal = ref(false);
const editingEvent = ref(null);
const showDetailModal = ref(false);
const selectedDetailEvent = ref(null);

const handleEventClick = (event) => {
    if (event.is_managed && props.canManageEvents) {
        openEditModal(event);
    } else {
        selectedDetailEvent.value = event;
        showDetailModal.value = true;
    }
};

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
    // Default date to today
    const today = new Date().toISOString().slice(0, 10);
    form.start_date = today;
    form.end_date = today;
    showEventModal.value = true;
};

const openEditModal = (event) => {
    if (event.type !== 'event' || !props.canManageEvents) return;
    
    editingEvent.value = event;
    form.title = event.title;
    // Strip time for simpler date input, or keep if using datetime-local
    // For simplicity, let's use date input for now, or text with datetime logic.
    // The Controller returns Y-m-d H:i:s.
    form.start_date = event.start.slice(0, 16); // YYYY-MM-DDTHH:mm for datetime-local
    form.end_date = event.end ? event.end.slice(0, 16) : '';
    form.description = event.description;
    form.location = event.location;
    
    showEventModal.value = true;
};

const submitEvent = () => {
    if (editingEvent.value) {
        form.put(route('admin.company-events.update', editingEvent.value.real_id), {
            onSuccess: () => { showEventModal.value = false; }
        });
    } else {
        form.post(route('admin.company-events.store'), {
            onSuccess: () => { showEventModal.value = false; }
        });
    }
};

const deleteEvent = () => {
    if (!editingEvent.value) return;
    if (confirm('Yakin ingin menghapus agenda ini?')) {
        router.delete(route('admin.company-events.destroy', editingEvent.value.real_id), {
            onSuccess: () => { showEventModal.value = false; }
        });
    }
};

// --- COMPUTED ---
const filteredEvents = computed(() => {
    return props.events.filter(event => {
        if (event.type === 'holiday' && !filters.holidays) return false;
        if (event.type === 'event' && !filters.companyEvents) return false;
        if (event.type === 'leave') {
            if (event.is_own && !filters.myLeave) return false;
            if (!event.is_own && !filters.otherLeave) return false;
        }
        if (event.type === 'task' && !filters.tasks) return false;
        return true;
    });
});

const sortedEvents = computed(() => {
    return [...filteredEvents.value].sort((a, b) => new Date(a.start) - new Date(b.start));
});

const getEventColor = (event) => {
    if (event.type === 'holiday') return 'bg-red-100 text-red-800 border-red-200';
    if (event.type === 'event') return 'bg-purple-100 text-purple-800 border-purple-200'; // Company Event
    if (event.type === 'task') {
        if (event.color === 'green') return 'bg-green-100 text-green-800 border-green-200';
        if (event.color === 'red') return 'bg-red-100 text-red-800 border-red-200';
        if (event.color === 'orange') return 'bg-orange-100 text-orange-800 border-orange-200';
        if (event.color === 'blue') return 'bg-blue-100 text-blue-800 border-blue-200';
        return 'bg-gray-100 text-gray-800 border-gray-200';
    }
    if (event.is_own) return 'bg-green-100 text-green-800 border-green-200';
    return 'bg-blue-100 text-blue-800 border-blue-200';
};



// Generate Calendar Grid
const calendarDays = computed(() => {
    const days = [];
    const firstDay = new Date(props.currentYear, props.currentMonth - 1, 1);
    const lastDay = new Date(props.currentYear, props.currentMonth, 0);
    
    // Padding days from previous month
    const startDayOfWeek = firstDay.getDay(); // 0 (Sun) - 6 (Sat)
    for (let i = 0; i < startDayOfWeek; i++) {
        days.push({ date: null, isPadding: true });
    }

    // Days of current month
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const dateStr = `${props.currentYear}-${String(props.currentMonth).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        
        // Find events for this day
        const dayEvents = filteredEvents.value.filter(event => {
            const start = new Date(event.start);
            const end = event.end ? new Date(event.end) : start;
            const current = new Date(dateStr);
            
            // Reset time for comparison
            start.setHours(0,0,0,0);
            end.setHours(0,0,0,0);
            current.setHours(0,0,0,0);

            return current >= start && current <= end;
        });

        days.push({
            date: i,
            fullDate: dateStr,
            isPadding: false,
            events: dayEvents
        });
    }

    return days;
});

const changeMonth = (offset) => {
    let newMonth = props.currentMonth + offset;
    let newYear = props.currentYear;

    if (newMonth > 12) {
        newMonth = 1;
        newYear++;
    } else if (newMonth < 1) {
        newMonth = 12;
        newYear--;
    }

    router.get(route('admin.calendar.index'), { month: newMonth, year: newYear }, { preserveState: true, preserveScroll: true });
};



const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' });
};

</script>

<template>
    <Head title="Kalender Perusahaan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kalender Perusahaan</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <!-- Header Navigation & Toolbar -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <!-- Month Nav -->
                        <div class="flex items-center gap-4">
                            <h3 class="text-2xl font-bold text-gray-800 w-48">
                                {{ monthNames[currentMonth - 1] }} {{ currentYear }}
                            </h3>
                            <div class="flex space-x-1">
                                <button @click="changeMonth(-1)" class="p-1.5 rounded-full hover:bg-gray-100 border border-gray-300">
                                    <ChevronLeftIcon class="w-5 h-5 text-gray-600" />
                                </button>
                                <button @click="changeMonth(1)" class="p-1.5 rounded-full hover:bg-gray-100 border border-gray-300">
                                    <ChevronRightIcon class="w-5 h-5 text-gray-600" />
                                </button>
                            </div>
                        </div>

                        <!-- Controls -->
                        <div class="flex flex-wrap items-center gap-4">
                            <!-- Add Event Button -->
                            <PrimaryButton v-if="canManageEvents" @click="openCreateModal" class="flex items-center gap-2">
                                <PlusIcon class="w-4 h-4" /> Tambah Agenda
                            </PrimaryButton>

                            <!-- Filters -->
                            <div class="flex items-center gap-3 bg-gray-50 px-3 py-2 rounded-lg border border-gray-200">
                                <FunnelIcon class="w-4 h-4 text-gray-500" />
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="filters.holidays" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                                    <span class="ml-2 text-sm text-gray-600">Libur</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="filters.companyEvents" class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-600">Agenda</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="filters.myLeave" class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">
                                    <span class="ml-2 text-sm text-gray-600">Cuti Saya</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="filters.otherLeave" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600">Cuti Rekan</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="filters.tasks" class="rounded border-gray-300 text-gray-600 shadow-sm focus:ring-gray-500">
                                    <span class="ml-2 text-sm text-gray-600">Tugas</span>
                                </label>
                            </div>

                            <!-- View Switcher (Desktop Only) -->
                            <div class="hidden md:flex bg-gray-100 p-1 rounded-lg">
                                <button @click="viewMode = 'grid'" 
                                    class="p-2 rounded-md transition-all duration-200"
                                    :class="viewMode === 'grid' ? 'bg-white shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700'">
                                    <Squares2X2Icon class="w-5 h-5" />
                                </button>
                                <button @click="viewMode = 'list'" 
                                    class="p-2 rounded-md transition-all duration-200"
                                    :class="viewMode === 'list' ? 'bg-white shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700'">
                                    <ListBulletIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- GRID VIEW (Hidden on Mobile) -->
                    <div v-show="viewMode === 'grid'" class="hidden md:block">
                        <div class="grid grid-cols-7 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden">
                            <!-- Days Header -->
                            <div v-for="day in daysOfWeek" :key="day" class="bg-gray-50 p-2 text-center text-sm font-semibold text-gray-600">
                                {{ day }}
                            </div>

                            <!-- Calendar Days -->
                            <div v-for="(day, index) in calendarDays" :key="index" 
                                 class="bg-white min-h-[120px] p-2 transition hover:bg-gray-50"
                                 :class="{ 'bg-gray-50': day.isPadding }">
                                
                                <div v-if="!day.isPadding">
                                    <span class="text-sm font-medium text-gray-700 block mb-1">{{ day.date }}</span>
                                    
                                    <!-- Events List -->
                                    <div class="space-y-1">
                                        <div v-for="event in day.events" :key="event.id" 
                                             @click="handleEventClick(event)"
                                             class="text-xs px-1.5 py-0.5 rounded border truncate cursor-pointer hover:opacity-80 transition"
                                             :class="[getEventColor(event)]"
                                             :title="event.title">
                                            {{ event.title }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LIST VIEW (Always Visible on Mobile, Toggle on Desktop) -->
                    <div class="block" :class="viewMode === 'grid' ? 'md:hidden' : ''">
                        <div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-200">
                            <div v-if="sortedEvents.length === 0" class="p-8 text-center text-gray-500">
                                Tidak ada agenda pada bulan ini.
                            </div>
                            <div v-for="event in sortedEvents" :key="event.id" class="p-4 hover:bg-gray-50 flex items-start gap-4 cursor-pointer transition" @click="handleEventClick(event)">
                                <!-- Date Badge -->
                                <div class="flex-shrink-0 w-16 text-center">
                                    <div class="text-xs font-bold text-gray-500 uppercase">{{ new Date(event.start).toLocaleDateString('id-ID', { month: 'short' }) }}</div>
                                    <div class="text-xl font-bold text-gray-800">{{ new Date(event.start).getDate() }}</div>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-grow">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full border" 
                                              :class="getEventColor(event)">
                                            {{ event.type === 'holiday' ? 'Libur Nasional' : (event.type === 'event' ? 'Agenda Perusahaan' : (event.type === 'task' ? 'Tugas' : (event.is_own ? 'Cuti Saya' : 'Cuti Rekan'))) }}
                                        </span>
                                        <span class="text-sm text-gray-500">{{ formatDate(event.start) }} <span v-if="event.end && event.end !== event.start">- {{ formatDate(event.end) }}</span></span>
                                    </div>
                                    <h4 class="text-base font-medium text-gray-900">{{ event.title }}</h4>
                                    <p v-if="event.details || event.description" class="text-sm text-gray-600 mt-1">{{ event.details || event.description }}</p>
                                    <div v-if="event.location" class="flex items-center text-xs text-gray-500 mt-1">
                                        <MapPinIcon class="w-3 h-3 mr-1" /> {{ event.location }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Legend (Only for Grid) -->
                    <div v-if="viewMode === 'grid'" class="mt-6 flex gap-4 text-sm flex-wrap">
                        <div class="flex items-center">
                            <span class="w-3 h-3 bg-red-100 border border-red-200 rounded mr-2"></span>
                            <span>Hari Libur</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 bg-purple-100 border border-purple-200 rounded mr-2"></span>
                            <span>Agenda Perusahaan</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 bg-green-100 border border-green-200 rounded mr-2"></span>
                            <span>Cuti Saya</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 bg-blue-100 border border-blue-200 rounded mr-2"></span>
                            <span>Cuti Lainnya</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Event Modal -->
        <Modal :show="showEventModal" @close="showEventModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingEvent ? 'Edit Agenda' : 'Tambah Agenda Baru' }}
                </h2>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Judul Agenda" />
                        <TextInput v-model="form.title" class="w-full mt-1" placeholder="Rapat Bulanan..." />
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

                <div class="mt-6 flex justify-between">
                    <DangerButton v-if="editingEvent" @click="deleteEvent" :disabled="form.processing">
                        Hapus
                    </DangerButton>
                    <div v-else></div> <!-- Spacer -->

                    <div class="flex gap-2">
                        <SecondaryButton @click="showEventModal = false">Batal</SecondaryButton>
                        <PrimaryButton @click="submitEvent" :disabled="form.processing">Simpan</PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Detail Modal (For View Only Items) -->
        <Modal :show="showDetailModal" @close="showDetailModal = false" maxWidth="md">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ selectedDetailEvent?.title }}
                    </h2>
                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-500">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="space-y-4 mb-6 text-sm text-gray-600 dark:text-gray-400">
                    <div>
                        <strong class="block text-gray-700 dark:text-gray-300">Kategori:</strong>
                        <span class="capitalize px-2 py-1 bg-gray-100 rounded text-gray-700 inline-block mt-1">{{ selectedDetailEvent?.type === 'event' ? 'Agenda Perusahaan' : selectedDetailEvent?.type === 'holiday' ? 'Hari Libur' : selectedDetailEvent?.type === 'leave' ? 'Cuti' : 'Tugas' }}</span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <strong class="block text-gray-700 dark:text-gray-300">Mulai:</strong>
                            {{ selectedDetailEvent?.start ? formatDate(selectedDetailEvent.start) : '-' }}
                        </div>
                        <div v-if="selectedDetailEvent?.end">
                            <strong class="block text-gray-700 dark:text-gray-300">Selesai:</strong>
                            {{ formatDate(selectedDetailEvent.end) }}
                        </div>
                    </div>
                    
                    <div v-if="selectedDetailEvent?.description || selectedDetailEvent?.details">
                        <strong class="block text-gray-700 dark:text-gray-300">Deskripsi / Info:</strong>
                        <p class="whitespace-pre-wrap mt-1 p-3 bg-gray-50 rounded-lg border border-gray-100">{{ selectedDetailEvent?.description || selectedDetailEvent?.details }}</p>
                    </div>

                    <div v-if="selectedDetailEvent?.location">
                        <strong class="block text-gray-700 dark:text-gray-300">Lokasi:</strong>
                        <p class="mt-1 flex items-center text-gray-600"><MapPinIcon class="w-4 h-4 mr-1 text-gray-400" /> {{ selectedDetailEvent?.location }}</p>
                    </div>
                </div>
                
                <div class="flex justify-end border-t border-gray-200 dark:border-gray-700 pt-4" v-if="selectedDetailEvent?.type === 'task' || selectedDetailEvent?.type === 'leave'">
                    <SecondaryButton @click="showDetailModal = false" class="mr-3">Tutup</SecondaryButton>
                    <Link v-if="selectedDetailEvent?.type === 'task'" :href="route('admin.tasks.index')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Buka Manajemen Tugas
                    </Link>
                    <Link v-if="selectedDetailEvent?.type === 'leave' && selectedDetailEvent?.is_own" :href="route('admin.cuti.my-requests')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Buka Cuti Saya
                    </Link>
                    <Link v-if="selectedDetailEvent?.type === 'leave' && !selectedDetailEvent?.is_own" :href="route('admin.cuti.approvals')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Kelola Persetujuan Cuti
                    </Link>
                </div>
                <div class="flex justify-end border-t border-gray-200 dark:border-gray-700 pt-4" v-else>
                    <PrimaryButton @click="showDetailModal = false">Tutup</PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
