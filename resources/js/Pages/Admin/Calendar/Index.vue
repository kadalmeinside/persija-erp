<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, reactive, watch } from 'vue';
import { 
    PlusIcon, 
    FunnelIcon, 
    Squares2X2Icon, 
    ListBulletIcon, 
    ChevronLeftIcon, 
    ChevronRightIcon, 
    XMarkIcon,
    ShareIcon,
    ClipboardDocumentIcon,
    ClipboardDocumentCheckIcon,
    ArrowPathIcon,
    ShieldCheckIcon,
    GlobeAltIcon,
    EyeIcon,
    EyeSlashIcon,
    QrCodeIcon,
    PrinterIcon,
    ArrowDownTrayIcon,
    LinkIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Swal from 'sweetalert2';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    events: Array,
    currentMonth: Number,
    currentYear: Number,
    canManageEvents: Boolean,
    shareSettings: Object
});

const toast = useToast();

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
const showFilters = ref(false);

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
    Swal.fire({
        title: 'Hapus Agenda?',
        text: `Agenda "${editingEvent.value.title}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.company-events.destroy', editingEvent.value.real_id), {
                onSuccess: () => { showEventModal.value = false; }
            });
        }
    });
};

// --- SHARE CALENDAR STATE & ACTIONS ---
const showShareModal = ref(false);
const shareForm = useForm({
    enabled: props.shareSettings?.enabled ?? true,
    show_details: props.shareSettings?.showDetails ?? true
});

watch(() => props.shareSettings, (newVal) => {
    if (newVal) {
        shareForm.enabled = newVal.enabled;
        shareForm.show_details = newVal.showDetails;
    }
}, { deep: true });

const saveShareSettings = () => {
    shareForm.post(route('admin.calendar.share-settings'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Pengaturan kalender publik berhasil disimpan.');
        }
    });
};

const copied = ref(false);
const copyShareUrl = async () => {
    if (props.shareSettings?.shareUrl) {
        try {
            await navigator.clipboard.writeText(props.shareSettings.shareUrl);
            copied.value = true;
            toast.success('Tautan kalender publik berhasil disalin!');
            setTimeout(() => { copied.value = false; }, 3000);
        } catch (e) {
            toast.error('Gagal menyalin tautan.');
        }
    }
};

const regenerateToken = () => {
    Swal.fire({
        title: 'Reset Tautan Kalender?',
        text: 'Tautan lama akan langsung hangus dan tidak dapat diakses lagi oleh siapa pun. Anda akan mendapatkan tautan baru.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Reset Tautan'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.calendar.regenerate-share-token'), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Tautan kalender baru berhasil dibuat.');
                }
            });
        }
    });
};

const shareTab = ref('link'); // 'link' | 'qr'

const downloadQrPng = () => {
    if (!props.shareSettings?.qrCodeSvg) return;
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = 1000;
        canvas.height = 1000;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, 1000, 1000);
        ctx.drawImage(img, 60, 60, 880, 880);

        const link = document.createElement('a');
        link.download = `qrcode-agenda-${props.shareSettings?.token ? props.shareSettings.token.substring(0, 8) : 'persija'}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        toast.success('QR Code berhasil diunduh (PNG).');
    };
    img.src = props.shareSettings.qrCodeSvg;
};

const downloadQrSvg = () => {
    if (!props.shareSettings?.rawSvg) return;
    const blob = new Blob([props.shareSettings.rawSvg], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.download = `qrcode-agenda-${props.shareSettings?.token ? props.shareSettings.token.substring(0, 8) : 'persija'}.svg`;
    link.href = url;
    link.click();
    URL.revokeObjectURL(url);
    toast.success('QR Code berhasil diunduh (SVG).');
};

const printQrCard = () => {
    const printWindow = window.open('', '_blank', 'width=850,height=950');
    if (!printWindow) {
        toast.error('Gagal membuka jendela cetak. Periksa izin pop-up browser.');
        return;
    }

    const companyName = props.shareSettings?.companyName || 'Persija ERP';
    const companyLogo = props.shareSettings?.companyLogoUrl 
        ? `<img src="${props.shareSettings.companyLogoUrl}" style="height: 52px; max-width: 180px; object-fit: contain; margin-bottom: 14px;" />` 
        : '';
    const qrSvg = props.shareSettings?.qrCodeSvg || '';
    const shareUrl = props.shareSettings?.shareUrl || '';

    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="utf-8">
            <title>Lembar Cetak QR Code Agenda & Ruang Rapat - \${companyName}</title>
            <style>
                @page {
                    size: A4 portrait;
                    margin: 15mm;
                }
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                    font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                }
                body {
                    background-color: #f8fafc;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    padding: 24px;
                }
                .sheet-card {
                    background: #ffffff;
                    width: 100%;
                    max-width: 520px;
                    border: 2px solid #e2e8f0;
                    border-radius: 24px;
                    padding: 44px 36px;
                    text-align: center;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
                }
                .badge {
                    display: inline-block;
                    padding: 6px 16px;
                    background: #eef2ff;
                    color: #4f46e5;
                    font-size: 12px;
                    font-weight: 700;
                    letter-spacing: 0.8px;
                    text-transform: uppercase;
                    border-radius: 9999px;
                    margin-bottom: 16px;
                    border: 1px solid #c7d2fe;
                }
                h1 {
                    font-size: 24px;
                    font-weight: 800;
                    color: #0f172a;
                    margin-bottom: 6px;
                    line-height: 1.3;
                }
                .company {
                    font-size: 15px;
                    font-weight: 600;
                    color: #64748b;
                    margin-bottom: 24px;
                }
                .qr-frame {
                    background: #ffffff;
                    border: 2px solid #e2e8f0;
                    border-radius: 20px;
                    padding: 20px;
                    display: inline-block;
                    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
                    margin-bottom: 22px;
                }
                .qr-frame img {
                    width: 250px;
                    height: 250px;
                    display: block;
                }
                .instruction-title {
                    font-size: 16px;
                    font-weight: 700;
                    color: #1e293b;
                    margin-bottom: 8px;
                }
                .instruction-desc {
                    font-size: 13px;
                    color: #475569;
                    line-height: 1.55;
                    margin-bottom: 20px;
                    max-width: 420px;
                    margin-left: auto;
                    margin-right: auto;
                }
                .url-container {
                    background: #f8fafc;
                    border: 1px dashed #cbd5e1;
                    border-radius: 10px;
                    padding: 10px 14px;
                    font-size: 11px;
                    color: #475569;
                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                    word-break: break-all;
                    margin-bottom: 22px;
                }
                .footer-bar {
                    font-size: 11px;
                    color: #94a3b8;
                    border-top: 1px solid #f1f5f9;
                    padding-top: 16px;
                    display: flex;
                    justify-content: space-between;
                }
                @media print {
                    body {
                        background: #ffffff;
                        padding: 0;
                        min-height: auto;
                    }
                    .sheet-card {
                        border: 2px solid #0f172a;
                        box-shadow: none;
                        max-width: 100%;
                        padding: 36px 30px;
                        page-break-inside: avoid;
                    }
                    .badge {
                        border-color: #000000;
                        color: #000000;
                        background: #f1f5f9;
                    }
                    .qr-frame {
                        border-color: #0f172a;
                        box-shadow: none;
                    }
                    .no-print {
                        display: none !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="sheet-card">
                \${companyLogo}
                <div><span class="badge">Jadwal Agenda & Ruang Meeting</span></div>
                <h1>Ketersediaan Jadwal & Ruangan</h1>
                <div class="company">\${companyName}</div>

                <div class="qr-frame">
                    <img src="\${qrSvg}" alt="QR Code Kalender" />
                </div>

                <div class="instruction-title">Pindai Menggunakan Kamera Smartphone</div>
                <div class="instruction-desc">
                    Arahkan kamera smartphone Anda ke QR code di atas untuk memeriksa jadwal agenda, rapat, dan ketersediaan ruang pertemuan secara langsung tanpa perlu login.
                </div>

                <div class="url-container">
                    Tautan Kalender: \${shareUrl}
                </div>

                <div class="footer-bar">
                    <span>Terproteksi Tautan Rahasia (Unlisted)</span>
                    <span>Pembaruan Real-Time</span>
                </div>
            </div>
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 350);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
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

        <div class="pb-6 pt-2 sm:pb-12 sm:pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-3 sm:p-6">
                    
                    <!-- Header Navigation & Toolbar -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-4 sm:mb-6 gap-4">
                        <!-- Month Nav -->
                        <div class="flex items-center justify-between w-full md:w-auto">
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-800">
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
                        <div class="flex flex-col md:flex-row items-stretch md:items-center w-full md:w-auto gap-3">
                            <!-- Mobile Toggle & View Switcher -->
                            <div class="flex items-center justify-between md:hidden w-full gap-2">
                                <button @click="showFilters = !showFilters" :class="{'bg-indigo-100 text-indigo-700 border-indigo-300': showFilters, 'bg-gray-50 text-gray-700 border-gray-300 hover:bg-gray-100': !showFilters}" class="flex-1 inline-flex items-center justify-center px-4 py-2 border rounded-lg transition-colors text-sm font-medium h-10">
                                    <FunnelIcon class="w-4 h-4 mr-2" /> Filter Agenda
                                </button>
                                
                                <div class="flex bg-gray-100 p-1 rounded-lg shrink-0 border border-gray-200 h-10 items-center">
                                    <button @click="viewMode = 'grid'" 
                                        class="p-1.5 rounded-md transition-all duration-200"
                                        :class="viewMode === 'grid' ? 'bg-white shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700'">
                                        <Squares2X2Icon class="w-4 h-4" />
                                    </button>
                                    <button @click="viewMode = 'list'" 
                                        class="p-1.5 rounded-md transition-all duration-200"
                                        :class="viewMode === 'list' ? 'bg-white shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700'">
                                        <ListBulletIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Add Event & Share Buttons -->
                            <div class="flex items-center gap-2 w-full md:w-auto order-first md:order-none">
                                <PrimaryButton v-if="canManageEvents" @click="openCreateModal" class="flex-1 md:flex-initial justify-center items-center gap-1.5">
                                    <PlusIcon class="w-4 h-4" /> Tambah Agenda
                                </PrimaryButton>
                                <SecondaryButton v-if="canManageEvents" @click="showShareModal = true" class="flex-1 md:flex-initial justify-center items-center gap-1.5">
                                    <ShareIcon class="w-4 h-4 text-indigo-600" />
                                    <span>Bagikan</span>
                                </SecondaryButton>
                            </div>

                            <!-- Filters -->
                            <div :class="{'hidden md:flex': !showFilters, 'grid grid-cols-2 mt-2 gap-2 md:mt-0 md:flex': showFilters}" class="bg-transparent md:bg-gray-50 md:px-3 md:py-2 rounded-lg md:border md:border-gray-200 w-full md:w-auto items-center">
                                <FunnelIcon class="hidden md:block w-4 h-4 text-gray-500 mr-2 shrink-0" />
                                <label class="inline-flex items-center cursor-pointer bg-gray-50 md:bg-transparent p-2 md:p-0 rounded border md:border-0 border-gray-200">
                                    <input type="checkbox" v-model="filters.holidays" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                                    <span class="ml-2 text-xs md:text-sm text-gray-600 truncate">Libur</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer bg-gray-50 md:bg-transparent p-2 md:p-0 rounded border md:border-0 border-gray-200">
                                    <input type="checkbox" v-model="filters.companyEvents" class="rounded border-gray-300 text-purple-600 shadow-sm focus:ring-purple-500">
                                    <span class="ml-2 text-xs md:text-sm text-gray-600 truncate">Agenda</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer bg-gray-50 md:bg-transparent p-2 md:p-0 rounded border md:border-0 border-gray-200">
                                    <input type="checkbox" v-model="filters.myLeave" class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">
                                    <span class="ml-2 text-xs md:text-sm text-gray-600 truncate">Cuti Saya</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer bg-gray-50 md:bg-transparent p-2 md:p-0 rounded border md:border-0 border-gray-200">
                                    <input type="checkbox" v-model="filters.otherLeave" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <span class="ml-2 text-xs md:text-sm text-gray-600 truncate">Cuti Rekan</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer bg-gray-50 md:bg-transparent p-2 md:p-0 rounded border md:border-0 border-gray-200 md:hidden lg:flex">
                                    <input type="checkbox" v-model="filters.tasks" class="rounded border-gray-300 text-gray-600 shadow-sm focus:ring-gray-500">
                                    <span class="ml-2 text-xs md:text-sm text-gray-600 truncate">Tugas</span>
                                </label>
                            </div>

                            <!-- View Switcher (Desktop Only) -->
                            <div class="hidden md:flex bg-gray-100 p-1 rounded-lg shrink-0">
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

                    <!-- GRID VIEW -->
                    <div v-show="viewMode === 'grid'" class="overflow-x-auto w-full">
                        <div class="grid grid-cols-7 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden min-w-[700px] md:min-w-0">
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

                    <!-- LIST VIEW -->
                    <div v-show="viewMode === 'list'">
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
                    {{ editingEvent ? 'Edit Agenda Perusahaan' : 'Tambah Agenda / Booking Ruangan' }}
                </h2>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Judul Agenda *" />
                        <TextInput v-model="form.title" class="w-full mt-1" placeholder="Rapat Evaluasi Tim / Pertemuan Sponsor..." />
                        <InputError :message="form.errors.title" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Mulai *" />
                            <TextInput type="datetime-local" v-model="form.start_date" class="w-full mt-1" />
                            <InputError :message="form.errors.start_date" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Selesai" />
                            <TextInput type="datetime-local" v-model="form.end_date" class="w-full mt-1" />
                            <InputError :message="form.errors.end_date" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Ruangan / Lokasi (Pencegah Bentrok)" />
                        <TextInput 
                            v-model="form.location" 
                            list="common-meeting-rooms"
                            class="w-full mt-1" 
                            placeholder="Pilih atau ketik nama ruangan, misal: Ruang Rapat Utama..." 
                        />
                        <datalist id="common-meeting-rooms">
                            <option value="Ruang Rapat Utama (Lt. 2)" />
                            <option value="Ruang Meeting Direksi" />
                            <option value="Ruang Briefing Tim" />
                            <option value="Press Conference Room" />
                            <option value="Studio Konten & Media" />
                            <option value="Online / Zoom Meeting" />
                        </datalist>
                        <InputError :message="form.errors.location" class="mt-1 font-semibold text-rose-600" />
                        <p class="text-[11px] text-gray-500 mt-1">
                            Sistem otomatis memvalidasi jadwal agar tidak terjadi double-booking pada ruangan dan jam yang sama.
                        </p>
                    </div>

                    <div>
                        <InputLabel value="Deskripsi / Catatan Agenda" />
                        <textarea v-model="form.description" class="w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" rows="3" placeholder="Rincian pembahasan, peserta rapat, atau perlengkapan yang dibutuhkan..."></textarea>
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>
                </div>

                <div class="mt-6 flex justify-between">
                    <DangerButton v-if="editingEvent" @click="deleteEvent" :disabled="form.processing">
                        Hapus
                    </DangerButton>
                    <div v-else></div> <!-- Spacer -->

                    <div class="flex gap-2">
                        <SecondaryButton @click="showEventModal = false">Batal</SecondaryButton>
                        <PrimaryButton @click="submitEvent" :disabled="form.processing">Simpan Agenda</PrimaryButton>
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

        <!-- Share Calendar Modal -->
        <Modal :show="showShareModal" @close="showShareModal = false" maxWidth="2xl">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                            <ShareIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Publikasi & Bagikan Kalender</h2>
                            <p class="text-xs text-gray-500">Tautan rahasia & QR Code siap cetak untuk pintu ruang rapat</p>
                        </div>
                    </div>
                    <button @click="showShareModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <!-- Tab Navigation -->
                <div class="flex border-b border-gray-200 mt-4">
                    <button 
                        type="button"
                        @click="shareTab = 'link'"
                        class="flex items-center gap-2 py-2.5 px-4 text-xs sm:text-sm font-semibold border-b-2 transition"
                        :class="shareTab === 'link' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <LinkIcon class="w-4 h-4" />
                        <span>Tautan & Pengaturan</span>
                    </button>
                    <button 
                        type="button"
                        @click="shareTab = 'qr'"
                        class="flex items-center gap-2 py-2.5 px-4 text-xs sm:text-sm font-semibold border-b-2 transition"
                        :class="shareTab === 'qr' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <QrCodeIcon class="w-4 h-4" />
                        <span>QR Code & Siap Cetak (Print)</span>
                        <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-indigo-50 text-indigo-600 font-bold">Baru</span>
                    </button>
                </div>

                <!-- TAB 1: TAUTAN & PENGATURAN -->
                <div v-show="shareTab === 'link'" class="mt-5 space-y-4">
                    <!-- Toggle Enable/Disable -->
                    <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800">Status Tautan Publik</h4>
                            <p class="text-xs text-gray-500">Izinkan siapapun yang memiliki tautan melihat kalender agenda tanpa login</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="shareForm.enabled" @change="saveShareSettings" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <!-- Share URL Box -->
                    <div v-if="shareForm.enabled" class="space-y-2">
                        <InputLabel value="Tautan Kalender Publik (Secret Token)" />
                        <div class="flex gap-2">
                            <TextInput 
                                :modelValue="shareSettings?.shareUrl" 
                                readonly 
                                class="w-full bg-gray-50 text-xs font-mono text-gray-700 select-all" 
                            />
                            <PrimaryButton @click="copyShareUrl" class="shrink-0 flex items-center gap-1.5">
                                <component :is="copied ? ClipboardDocumentCheckIcon : ClipboardDocumentIcon" class="w-4 h-4" />
                                <span>{{ copied ? 'Tersalin' : 'Salin' }}</span>
                            </PrimaryButton>
                        </div>
                        <p class="text-[11px] text-gray-500">
                            Bagikan tautan ini kepada pihak luar, staf, atau rekanan yang memerlukan informasi ketersediaan ruangan.
                        </p>
                    </div>

                    <!-- Privacy Detail Toggle -->
                    <div v-if="shareForm.enabled" class="flex items-center justify-between p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800">Tampilkan Detail Agenda</h4>
                            <p class="text-xs text-gray-500">
                                {{ shareForm.show_details ? 'Judul, waktu, ruangan, dan deskripsi ditampilkan lengkap.' : 'Judul disamarkan sebagai "Jadwal Agenda/Pertemuan" untuk menjaga kerahasiaan topik internal.' }}
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="shareForm.show_details" @change="saveShareSettings" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <!-- Security Callout -->
                    <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-800 space-y-1.5">
                        <div class="flex items-center gap-1.5 font-bold">
                            <ShieldCheckIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                            <span>Perlindungan Privasi & Anti-Bot AI Aktif</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-[11px] text-emerald-700">
                            <li>Tidak terindeks oleh Google / Bing (HTTP Header <code class="bg-emerald-100 px-1 rounded">X-Robots-Tag: noindex</code>).</li>
                            <li>Bot AI crawler (GPTBot, ClaudeBot, CCBot, dll.) otomatis ditolak oleh server.</li>
                            <li>Data Cuti dan Tugas internal staf tidak pernah ditampilkan di halaman publik.</li>
                        </ul>
                    </div>

                    <!-- Reset Token Action -->
                    <div v-if="shareForm.enabled" class="pt-2 border-t border-gray-200 flex items-center justify-between">
                        <div>
                            <h5 class="text-xs font-semibold text-gray-700">Tautan Terlanjur Bocor?</h5>
                            <p class="text-[11px] text-gray-500">Ganti token baru dan batalkan akses tautan lama seketika.</p>
                        </div>
                        <DangerButton @click="regenerateToken" class="text-xs">
                            <ArrowPathIcon class="w-3.5 h-3.5 mr-1" /> Reset Tautan
                        </DangerButton>
                    </div>
                </div>

                <!-- TAB 2: QR CODE & SIAP CETAK (PRINT) -->
                <div v-show="shareTab === 'qr'" class="mt-5 space-y-4">
                    <!-- Warning if disabled -->
                    <div v-if="!shareForm.enabled" class="p-3.5 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 flex items-start gap-2.5">
                        <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="font-bold">Status Tautan Sedang Nonaktif</p>
                            <p class="text-[11px] text-amber-700 mt-0.5">
                                Tautan kalender saat ini dinonaktifkan. Aktifkan kembali di tab <em>Tautan & Pengaturan</em> agar QR Code dapat dibuka saat dipindai.
                            </p>
                        </div>
                    </div>

                    <!-- Printable Card Live Preview -->
                    <div class="p-6 bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl flex flex-col items-center text-center shadow-inner">
                        <!-- Company Logo / Badge -->
                        <img 
                            v-if="shareSettings?.companyLogoUrl" 
                            :src="shareSettings.companyLogoUrl" 
                            alt="Logo" 
                            class="h-10 max-w-[160px] object-contain mb-2" 
                        />
                        
                        <div>
                            <span class="inline-block px-3 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase bg-indigo-50 text-indigo-700 border border-indigo-200 mb-2">
                                Agenda & Pemakaian Ruang Rapat
                            </span>
                        </div>

                        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 leading-tight">
                            Ketersediaan Jadwal & Ruangan
                        </h3>
                        <p class="text-xs font-semibold text-slate-500 mb-4">{{ shareSettings?.companyName || 'Persija ERP' }}</p>

                        <!-- QR Code Frame -->
                        <div class="bg-white p-3 rounded-2xl border-2 border-slate-200 shadow-sm mb-4 inline-block">
                            <img 
                                v-if="shareSettings?.qrCodeSvg" 
                                :src="shareSettings.qrCodeSvg" 
                                alt="QR Code Kalender" 
                                class="w-44 h-44 sm:w-48 sm:h-48 block mx-auto" 
                            />
                        </div>

                        <h4 class="text-sm font-bold text-slate-800 mb-1">Pindai Menggunakan Kamera Smartphone</h4>
                        <p class="text-xs text-slate-500 max-w-sm mb-3 leading-relaxed">
                            Arahkan kamera smartphone Anda ke QR code di atas untuk melihat jadwal agenda, rapat, dan ketersediaan ruang meeting secara real-time.
                        </p>

                        <!-- URL Box -->
                        <div class="w-full max-w-md bg-white border border-slate-200 rounded-lg py-1.5 px-3 text-[11px] font-mono text-slate-600 truncate mb-2">
                            {{ shareSettings?.shareUrl }}
                        </div>
                        <div class="flex items-center gap-3 text-[10px] text-slate-400">
                            <span>🔒 Terproteksi Tautan Rahasia</span>
                            <span>•</span>
                            <span>⚡ Pembaruan Real-Time</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 pt-2">
                        <PrimaryButton @click="printQrCard" class="justify-center gap-1.5 shadow-sm py-2.5 text-xs sm:text-sm">
                            <PrinterIcon class="w-4 h-4" />
                            <span>Cetak Lembar Siap Tempel</span>
                        </PrimaryButton>

                        <div class="flex items-center gap-2">
                            <SecondaryButton @click="downloadQrPng" class="flex-1 sm:flex-initial justify-center gap-1 text-xs py-2">
                                <ArrowDownTrayIcon class="w-4 h-4 text-indigo-600" />
                                <span>Unduh PNG</span>
                            </SecondaryButton>
                            <SecondaryButton @click="downloadQrSvg" class="flex-1 sm:flex-initial justify-center gap-1 text-xs py-2">
                                <ArrowDownTrayIcon class="w-4 h-4 text-purple-600" />
                                <span>Unduh SVG</span>
                            </SecondaryButton>
                        </div>
                    </div>

                    <!-- Usage Recommendation -->
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-[11px] text-blue-700 leading-relaxed flex items-start gap-2">
                        <span class="font-bold text-sm shrink-0">💡</span>
                        <span>
                            <strong>Tips Pemasangan:</strong> Klik <em>"Cetak Lembar Siap Tempel"</em> untuk mencetak kartu standing A4/A5 resmi. Anda dapat menempelkannya di pintu ruang meeting atau meja resepsionis agar peserta rapat dan staf dapat memindai jadwal ketersediaan ruangan dengan cepat.
                        </span>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-6 flex justify-end pt-3 border-t border-gray-100">
                    <SecondaryButton @click="showShareModal = false">Tutup</SecondaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
