<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    CalendarIcon, 
    ChevronLeftIcon, 
    ChevronRightIcon, 
    Squares2X2Icon, 
    ListBulletIcon, 
    MapPinIcon, 
    ClockIcon, 
    XMarkIcon,
    ShieldCheckIcon,
    ArrowDownTrayIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    events: Array,
    currentMonth: Number,
    currentYear: Number,
    companyName: String,
    token: String,
});

const monthNames = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
];

const daysOfWeek = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

const viewMode = ref('grid');
const showDetailModal = ref(false);
const selectedEvent = ref(null);

const changeMonth = (delta) => {
    let newMonth = props.currentMonth + delta;
    let newYear = props.currentYear;
    if (newMonth > 12) {
        newMonth = 1;
        newYear++;
    } else if (newMonth < 1) {
        newMonth = 12;
        newYear--;
    }
    router.get(route('public.calendar.show', props.token), { month: newMonth, year: newYear }, { preserveState: true, preserveScroll: true });
};

const goToToday = () => {
    const today = new Date();
    router.get(route('public.calendar.show', props.token), { month: today.getMonth() + 1, year: today.getFullYear() }, { preserveState: true, preserveScroll: true });
};

const calendarDays = computed(() => {
    const days = [];
    const firstDay = new Date(props.currentYear, props.currentMonth - 1, 1);
    const lastDay = new Date(props.currentYear, props.currentMonth, 0);
    const startingDay = firstDay.getDay();

    // Padding days for previous month
    for (let i = 0; i < startingDay; i++) {
        days.push({ date: null, isPadding: true });
    }

    // Days of current month
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const fullDate = `${props.currentYear}-${String(props.currentMonth).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const dayEvents = props.events.filter(e => {
            const start = new Date(e.start);
            const end = e.end ? new Date(e.end) : start;
            const target = new Date(fullDate);
            start.setHours(0, 0, 0, 0);
            end.setHours(0, 0, 0, 0);
            target.setHours(0, 0, 0, 0);
            return target >= start && target <= end;
        });

        days.push({
            date: i,
            fullDate,
            isPadding: false,
            events: dayEvents,
            isToday: fullDate === new Date().toISOString().slice(0, 10),
        });
    }

    return days;
});

const sortedEvents = computed(() => {
    return [...props.events].sort((a, b) => new Date(a.start) - new Date(b.start));
});

const openDetail = (event) => {
    selectedEvent.value = event;
    showDetailModal.value = true;
};

const formatDateFull = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatTimeRange = (startStr, endStr) => {
    if (!startStr) return '';
    const start = new Date(startStr);
    const startTime = start.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    if (!endStr || endStr === startStr) return `${startTime} WIB`;
    const end = new Date(endStr);
    const endTime = end.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    return `${startTime} - ${endTime} WIB`;
};

const getEventBadgeClass = (event) => {
    if (event.type === 'holiday') {
        return 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100';
    }
    return 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100';
};
</script>

<template>
    <Head>
        <title>{{ companyName }} - Kalender Agenda & Hari Libur</title>
        <meta name="robots" content="noindex, nofollow, noarchive, nosnippet" />
        <meta name="googlebot" content="noindex, nofollow" />
    </Head>

    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col justify-between selection:bg-indigo-500 selection:text-white">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-xs backdrop-blur-md bg-white/90">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-md shadow-indigo-200 font-bold text-lg">
                        <CalendarIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-slate-900 leading-tight">{{ companyName }}</h1>
                        <p class="text-xs text-slate-500 font-medium">Kalender Agenda & Ketersediaan Ruangan</p>
                    </div>
                </div>

                <!-- Security & Anti-Bot Tag -->
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                        Akses Tautan Terproteksi (Unlisted)
                    </span>
                    <a 
                        :href="route('public.calendar.ics', token)" 
                        download="agenda.ics"
                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition-colors"
                        title="Download / Subscribe kalender ini ke Google Calendar / Apple Calendar"
                    >
                        <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                        Ekspor .iCal
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full flex-1">
            <!-- Calendar Navigation Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 sm:p-6 mb-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Month Title & Controls -->
                    <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            {{ monthNames[currentMonth - 1] }} {{ currentYear }}
                        </h2>
                        <div class="flex items-center gap-1">
                            <button 
                                @click="changeMonth(-1)"
                                class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 transition-colors"
                                title="Bulan Sebelumnya"
                            >
                                <ChevronLeftIcon class="w-5 h-5" />
                            </button>
                            <button 
                                @click="goToToday"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 transition-colors"
                            >
                                Hari Ini
                            </button>
                            <button 
                                @click="changeMonth(1)"
                                class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 transition-colors"
                                title="Bulan Berikutnya"
                            >
                                <ChevronRightIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Legend & View Switcher -->
                    <div class="flex flex-wrap items-center justify-between sm:justify-end gap-3 w-full sm:w-auto">
                        <div class="flex items-center gap-3 text-xs text-slate-600">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                Hari Libur
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span>
                                Agenda / Ruangan
                            </span>
                        </div>

                        <!-- Toggle View Mode -->
                        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                            <button 
                                @click="viewMode = 'grid'"
                                :class="[viewMode === 'grid' ? 'bg-white text-indigo-600 shadow-xs font-semibold' : 'text-slate-500 hover:text-slate-800 font-medium']"
                                class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs transition-all"
                            >
                                <Squares2X2Icon class="w-4 h-4" />
                                Kalender
                            </button>
                            <button 
                                @click="viewMode = 'list'"
                                :class="[viewMode === 'list' ? 'bg-white text-indigo-600 shadow-xs font-semibold' : 'text-slate-500 hover:text-slate-800 font-medium']"
                                class="flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs transition-all"
                            >
                                <ListBulletIcon class="w-4 h-4" />
                                Daftar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRID CALENDAR VIEW -->
            <div v-if="viewMode === 'grid'" class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <!-- Day Names Header -->
                <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50/75">
                    <div 
                        v-for="(day, idx) in daysOfWeek" 
                        :key="day" 
                        :class="[idx === 0 ? 'text-red-600' : 'text-slate-600']"
                        class="py-3 text-center text-xs font-bold uppercase tracking-wider"
                    >
                        {{ day }}
                    </div>
                </div>

                <!-- Days Matrix -->
                <div class="grid grid-cols-7 divide-x divide-y divide-slate-100 bg-slate-100">
                    <div 
                        v-for="(cell, index) in calendarDays" 
                        :key="index"
                        :class="[
                            cell.isPadding ? 'bg-slate-50/50 min-h-[90px] sm:min-h-[120px]' : 'bg-white min-h-[90px] sm:min-h-[120px] p-2 hover:bg-slate-50/70 transition-colors',
                            cell.isToday ? 'ring-2 ring-indigo-500 ring-inset bg-indigo-50/30' : ''
                        ]"
                    >
                        <div v-if="!cell.isPadding" class="flex flex-col h-full">
                            <div class="flex items-center justify-between mb-1.5">
                                <span 
                                    :class="[
                                        cell.isToday ? 'bg-indigo-600 text-white font-bold w-6 h-6 rounded-full flex items-center justify-center text-xs' : 'text-xs font-semibold text-slate-700'
                                    ]"
                                >
                                    {{ cell.date }}
                                </span>
                                <span v-if="cell.events.length > 2" class="text-[10px] text-slate-400 font-medium hidden sm:inline">
                                    {{ cell.events.length }} jadwal
                                </span>
                            </div>

                            <!-- Events List inside cell -->
                            <div class="space-y-1 overflow-y-auto max-h-[85px] custom-scrollbar">
                                <div 
                                    v-for="event in cell.events" 
                                    :key="event.id"
                                    @click="openDetail(event)"
                                    :class="[getEventBadgeClass(event)]"
                                    class="text-[11px] leading-tight px-1.5 py-1 rounded-md border font-medium cursor-pointer truncate transition-all shadow-2xs hover:scale-[1.01]"
                                    :title="event.title"
                                >
                                    <span v-if="event.type === 'event' && event.location" class="font-bold text-[10px] block opacity-85">
                                        📍 {{ event.location }}
                                    </span>
                                    <span>{{ event.title }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIST VIEW -->
            <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-200/80 divide-y divide-slate-100 overflow-hidden">
                <div v-if="sortedEvents.length === 0" class="p-12 text-center text-slate-400 text-sm">
                    Tidak ada agenda atau hari libur pada bulan ini.
                </div>

                <div 
                    v-for="event in sortedEvents" 
                    :key="event.id"
                    @click="openDetail(event)"
                    class="p-4 sm:p-5 hover:bg-slate-50/80 transition-colors flex items-start gap-4 cursor-pointer"
                >
                    <!-- Date Badge -->
                    <div class="w-14 sm:w-16 text-center py-2 px-1 bg-slate-50 rounded-xl border border-slate-200/80 shrink-0">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">
                            {{ new Date(event.start).toLocaleDateString('id-ID', { month: 'short' }) }}
                        </span>
                        <span class="text-xl sm:text-2xl font-black text-slate-800 leading-none block mt-0.5">
                            {{ new Date(event.start).getDate() }}
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span 
                                :class="[event.type === 'holiday' ? 'bg-red-100 text-red-700 border-red-200' : 'bg-purple-100 text-purple-700 border-purple-200']"
                                class="px-2 py-0.5 text-xs font-semibold rounded-md border"
                            >
                                {{ event.type === 'holiday' ? 'Hari Libur Nasional' : 'Agenda / Ruang Meeting' }}
                            </span>
                            <span v-if="event.type === 'event'" class="text-xs text-slate-500 font-medium flex items-center gap-1">
                                <ClockIcon class="w-3.5 h-3.5 text-slate-400" />
                                {{ formatTimeRange(event.start, event.end) }}
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-slate-900 truncate">{{ event.title }}</h3>

                        <div v-if="event.location" class="flex items-center text-xs text-slate-600 mt-1 font-medium">
                            <MapPinIcon class="w-3.5 h-3.5 text-indigo-500 mr-1 shrink-0" />
                            <span>{{ event.location }}</span>
                        </div>

                        <p v-if="event.description" class="text-xs text-slate-500 mt-1 line-clamp-2">
                            {{ event.description }}
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200/80 py-4 mt-8">
            <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-400">
                &copy; {{ currentYear }} {{ companyName }}. Dokumen kalender internal dibagikan melalui tautan resmi terverifikasi.
            </div>
        </footer>

        <!-- Event Detail Modal -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-lg w-full p-6 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <span 
                            :class="[selectedEvent?.type === 'holiday' ? 'bg-red-100 text-red-700 border-red-200' : 'bg-purple-100 text-purple-700 border-purple-200']"
                            class="px-2.5 py-0.5 text-xs font-bold rounded-md border inline-block mb-1.5"
                        >
                            {{ selectedEvent?.type === 'holiday' ? 'Hari Libur Nasional' : 'Agenda / Ruang Meeting' }}
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 leading-snug">{{ selectedEvent?.title }}</h3>
                    </div>
                    <button 
                        @click="showDetailModal = false"
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="py-4 space-y-3.5 text-sm">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Tanggal</span>
                        <span class="font-medium text-slate-800">{{ formatDateFull(selectedEvent?.start) }}</span>
                    </div>

                    <div v-if="selectedEvent?.type === 'event'">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Waktu Pelaksanaan</span>
                        <span class="font-medium text-slate-800">{{ formatTimeRange(selectedEvent?.start, selectedEvent?.end) }}</span>
                    </div>

                    <div v-if="selectedEvent?.location">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Ruangan / Tempat</span>
                        <span class="font-semibold text-indigo-700 flex items-center gap-1.5 mt-0.5">
                            <MapPinIcon class="w-4 h-4 text-indigo-600" />
                            {{ selectedEvent?.location }}
                        </span>
                    </div>

                    <div v-if="selectedEvent?.description">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Keterangan / Agenda</span>
                        <p class="text-slate-600 mt-1 whitespace-pre-line bg-slate-50 p-3 rounded-xl border border-slate-100 text-xs leading-relaxed">
                            {{ selectedEvent?.description }}
                        </p>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button 
                        @click="showDetailModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 rounded-xl transition-colors border border-slate-200"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
