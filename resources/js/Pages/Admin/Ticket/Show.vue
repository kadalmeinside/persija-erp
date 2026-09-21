<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
    ClockIcon, UserCircleIcon, PaperClipIcon, 
    CheckCircleIcon, XCircleIcon, ArrowLeftIcon 
} from '@heroicons/vue/24/solid';

const props = defineProps({
    ticket: Object,
    isIT: Boolean,
    itStaff: Array
});

// --- UTILS ---
const statusColor = (status) => {
    switch(status) {
        case 'Open': return 'bg-blue-100 text-blue-800';
        case 'In Progress': return 'bg-yellow-100 text-yellow-800';
        case 'Resolved': return 'bg-green-100 text-green-800';
        case 'Closed': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-600';
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('id-ID', {
        weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

// --- IT ACTIONS ---
const updateForm = useForm({
    status: props.ticket.status,
    priority: props.ticket.priority,
    assigned_to: props.ticket.assigned_to
});

const updateTicket = () => {
    updateForm.put(route('admin.tickets.update', props.ticket.id), {
        onSuccess: () => { /* maybe toast */ }
    });
};

// --- COMMENT FORM ---
const commentForm = useForm({
    message: '',
    attachment: null
});

const fileInput = ref(null);

const handleFile = (e) => {
    commentForm.attachment = e.target.files[0];
};

const sendComment = () => {
    commentForm.post(route('admin.tickets.comments.store', props.ticket.id), {
        onSuccess: () => {
            commentForm.reset();
            // Reset file input manually
            if (fileInput.value) fileInput.value.value = '';
        }
    });
};
</script>

<template>
    <Head :title="`Tiket #${ticket.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.tickets.index')" class="p-2 rounded-full hover:bg-gray-200 text-gray-500">
                    <ArrowLeftIcon class="w-5 h-5"/>
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        #{{ ticket.id }}: {{ ticket.subject }}
                    </h2>
                    <div class="text-sm text-gray-500 flex gap-2 items-center mt-1">
                        <span>{{ ticket.reporter.name }}</span>
                        <span>&bull;</span>
                        <span>{{ formatDate(ticket.created_at) }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row gap-6">
                    
                    <!-- LEFT COLUMN: CONTENT & CHAT -->
                    <div class="flex-1 space-y-6">
                        
                        <!-- Ticket Description -->
                        <div class="bg-white shadow sm:rounded-lg p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="px-3 py-1 rounded-full text-sm font-bold tracking-wide" :class="statusColor(ticket.status)">
                                        {{ ticket.status }}
                                    </span>
                                    <span class="ml-2 px-2 py-1 rounded border text-xs text-gray-600 bg-gray-50 font-mono">
                                        {{ ticket.category }}
                                    </span>
                                </div>
                                <div v-if="ticket.priority === 'Critical'" class="text-red-600 font-bold uppercase text-xs border border-red-200 bg-red-50 px-2 py-1 rounded">Critical Priority</div>
                            </div>
                            
                            <div class="prose max-w-none text-gray-800 whitespace-pre-line mb-6">
                                {{ ticket.description }}
                            </div>

                            <div v-if="ticket.attachment_path" class="border-t pt-4">
                                <h4 class="text-sm font-bold text-gray-700 mb-2 flex items-center"><PaperClipIcon class="w-4 h-4 mr-1"/> Lampiran:</h4>
                                <a :href="`/storage/${ticket.attachment_path}`" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Lihat Lampiran
                                </a>
                            </div>
                        </div>

                        <!-- Comments Section -->
                        <div class="bg-gray-50 shadow sm:rounded-lg p-6 border border-gray-200">
                            <h3 class="font-bold text-lg mb-6 flex items-center text-gray-700">
                                Diskusi / Update
                            </h3>

                            <div class="space-y-6 mb-8">
                                <div v-if="ticket.comments.length === 0" class="text-center text-gray-500 italic py-4">
                                    Belum ada komentar.
                                </div>

                                <div v-for="comment in ticket.comments" :key="comment.id" class="flex gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold border border-indigo-200">
                                            {{ comment.user.name.charAt(0) }}
                                        </div>
                                    </div>
                                    <div class="flex-1 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="font-bold text-gray-900">{{ comment.user.name }}</span>
                                            <span class="text-xs text-gray-500">{{ formatDate(comment.created_at) }}</span>
                                        </div>
                                        <div class="text-gray-800 whitespace-pre-line text-sm">{{ comment.message }}</div>
                                        
                                        <div v-if="comment.attachment_path" class="mt-3">
                                            <a :href="`/storage/${comment.attachment_path}`" target="_blank" class="text-xs text-indigo-600 hover:underline flex items-center">
                                                <PaperClipIcon class="w-3 h-3 mr-1"/> Lihat Lampiran
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Comment Form -->
                            <div class="bg-white p-4 rounded-lg border border-gray-300">
                                <h4 class="font-bold text-sm text-gray-700 mb-2">Balas Tiket</h4>
                                <textarea v-model="commentForm.message" rows="3" class="w-full border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500" placeholder="Tulis balasan..."></textarea>
                                
                                <div class="mt-2 flex justify-between items-center">
                                    <input type="file" ref="fileInput" @change="handleFile" class="text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                    
                                    <PrimaryButton @click="sendComment" :disabled="commentForm.processing || !commentForm.message">
                                        {{ commentForm.processing ? 'Mengirim...' : 'Kirim Balasan' }}
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: ADMIN ACTIONS -->
                    <div v-if="isIT" class="w-full lg:w-80 space-y-6">
                        <div class="bg-white shadow sm:rounded-lg p-6 border-t-4 border-indigo-500">
                            <h3 class="font-bold text-gray-900 mb-4 border-b pb-2">Admin Panel</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <InputLabel value="Update Status" />
                                    <select v-model="updateForm.status" class="mt-1 block w-full text-sm border-gray-300 rounded-md">
                                        <option value="Open">Open</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Resolved">Resolved</option>
                                        <option value="Closed">Closed</option>
                                    </select>
                                </div>

                                <div>
                                    <InputLabel value="Prioritas" />
                                    <select v-model="updateForm.priority" class="mt-1 block w-full text-sm border-gray-300 rounded-md">
                                        <option value="Low">Low</option>
                                        <option value="Medium">Medium</option>
                                        <option value="High">High</option>
                                        <option value="Critical">Critical</option>
                                    </select>
                                </div>

                                <div>
                                    <InputLabel value="Assignee (PIC)" />
                                    <select v-model="updateForm.assigned_to" class="mt-1 block w-full text-sm border-gray-300 rounded-md">
                                        <option :value="null">-- Unassigned --</option>
                                        <option v-for="staff in itStaff" :key="staff.id" :value="staff.id">
                                            {{ staff.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="pt-2">
                                    <PrimaryButton @click="updateTicket" class="w-full justify-center" :disabled="updateForm.processing">
                                        Simpan Perubahan
                                    </PrimaryButton>
                                    <p class="text-xs text-gray-500 mt-2 text-center" v-if="updateForm.recentlySuccessful">Simpan Berhasil!</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
