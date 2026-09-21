<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { XMarkIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: Boolean,
    task: Object, // null if creating new
    assignees: Array,
    departemens: Array,
    programKerjas: Array,
    currentKaryawanId: Number,
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    description: '',
    id_karyawan_assignee: '',
    id_departemen: '',
    id_program_kerja: '',
    priority: 'Medium',
    status: 'To Do',
    due_date: '',
});

watch(() => props.show, (showing) => {
    if (showing) {
        if (props.task) {
            form.title = props.task.title;
            form.description = props.task.description || '';
            form.id_karyawan_assignee = props.task.id_karyawan_assignee;
            form.id_departemen = props.task.id_departemen || '';
            form.id_program_kerja = props.task.id_program_kerja || '';
            form.priority = props.task.priority;
            form.status = props.task.status;
            form.due_date = props.task.due_date ? props.task.due_date.split('T')[0] : '';
        } else {
            form.reset();
            form.id_karyawan_assignee = props.currentKaryawanId; // Default to self assign
        }
    }
});

const submit = () => {
    if (props.task) {
        form.put(route('admin.tasks.update', props.task.id), {
            preserveScroll: true,
            onSuccess: () => emit('close')
        });
    } else {
        form.post(route('admin.tasks.store'), {
            preserveScroll: true,
            onSuccess: () => emit('close')
        });
    }
};

const destroy = () => {
    if (confirm('Apakah Anda yakin ingin menghapus tugas ini?')) {
        form.delete(route('admin.tasks.destroy', props.task.id), {
            preserveScroll: true,
            onSuccess: () => emit('close')
        });
    }
};
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="emit('close')">
        <div class="bg-white overflow-y-auto max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">
                    {{ task ? 'Detail Tugas' : 'Buat Tugas Baru' }}
                </h3>
                <button @click="emit('close')" type="button" class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100 transition-colors">
                    <XMarkIcon class="w-6 h-6" />
                </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="submit" class="p-6 flex-1 flex flex-col gap-5">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Tugas <span class="text-red-500">*</span></label>
                    <input v-model="form.title" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-gray-900" placeholder="Contoh: Siapkan Laporan Keuangan" required>
                    <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Assignee -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Diberikan Kepada (Assignee) <span class="text-red-500">*</span></label>
                        <select v-model="form.id_karyawan_assignee" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-gray-900" required>
                            <option value="" disabled>Pilih Karyawan</option>
                            <option v-for="opt in assignees" :key="opt.id" :value="opt.id">
                                {{ opt.nama_lengkap }} {{ opt.jabatan ? `(${opt.jabatan})` : '' }}
                            </option>
                        </select>
                        <div v-if="form.errors.id_karyawan_assignee" class="text-red-500 text-xs mt-1">{{ form.errors.id_karyawan_assignee }}</div>
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Batas Waktu (Due Date)</label>
                        <input v-model="form.due_date" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-gray-900">
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritas</label>
                        <select v-model="form.priority" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-gray-900">
                            <option value="Low">Rendah (Low)</option>
                            <option value="Medium">Sedang (Medium)</option>
                            <option value="High">Tinggi (High)</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div v-if="task">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select v-model="form.status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-gray-900">
                            <option value="To Do">To Do</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Review">Needs Review</option>
                            <option value="Done">Done</option>
                        </select>
                    </div>
                </div>

                <!-- Grouping / Context -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Departemen (Opsional)</label>
                        <select v-model="form.id_departemen" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-gray-900 bg-white">
                            <option value="">-- Tidak Terikat --</option>
                            <option v-for="opt in departemens" :key="opt.id" :value="opt.id">{{ opt.nama_departemen }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Program Kerja (Opsional)</label>
                        <select v-model="form.id_program_kerja" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-gray-900 bg-white">
                            <option value="">-- Tidak Terikat --</option>
                            <option v-for="opt in programKerjas" :key="opt.id" :value="opt.id">{{ opt.nama_program }}</option>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Lengkap</label>
                    <textarea v-model="form.description" rows="5" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-gray-900" placeholder="Tulis rincian tugas, checklist, atau instruksi di sini..."></textarea>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-between pt-4 mt-2 border-t border-gray-100">
                    <div>
                        <button type="button" v-if="task" @click="destroy" class="flex items-center text-red-600 hover:text-red-800 text-sm font-medium transition-colors">
                            <TrashIcon class="w-4 h-4 mr-1" /> Hapus Tugas
                        </button>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="emit('close')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-75 flex items-center">
                            <span v-if="form.processing" class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            {{ task ? 'Simpan Perubahan' : 'Buat Tugas' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>
