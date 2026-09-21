<script setup>
import Modal from '@/Components/Modal.vue';
import { XMarkIcon, ArrowDownTrayIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    show: Boolean,
    fileUrl: String,
    fileType: String,
    fileName: String
});

const emit = defineEmits(['close']);

// Helper computed untuk menentukan jenis file
const isPdf = computed(() => {
    if (props.fileType === 'pdf') return true;
    return props.fileUrl?.toLowerCase().endsWith('.pdf');
});

const isImage = computed(() => {
    if (props.fileType === 'image') return true;
    const lower = props.fileUrl?.toLowerCase();
    return lower?.endsWith('.jpg') || lower?.endsWith('.jpeg') || lower?.endsWith('.png') || lower?.endsWith('.webp');
});

// Fungsi untuk membuka file di tab baru
const openInNewTab = () => {
    window.open(props.fileUrl, '_blank');
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')" maxWidth="5xl">
        <div class="relative bg-gray-900 h-[95vh] flex flex-col rounded-lg overflow-hidden">
            
            <!-- Toolbar Header -->
            <div class="flex justify-between items-center px-4 py-3 bg-gray-800 text-white shadow z-10">
                <h3 class="text-sm font-medium truncate max-w-xs md:max-w-md">{{ fileName || 'Pratinjau File' }}</h3>
                <div class="flex space-x-2">
                    <a :href="fileUrl" :download="fileName" class="p-2 hover:bg-gray-700 rounded-full text-gray-300 hover:text-white transition" title="Download">
                        <ArrowDownTrayIcon class="w-5 h-5" />
                    </a>
                    <button @click="openInNewTab" class="p-2 hover:bg-gray-700 rounded-full text-gray-300 hover:text-white transition" title="Buka di Tab Baru">
                        <ArrowTopRightOnSquareIcon class="w-5 h-5" />
                    </button>
                    <button @click="$emit('close')" class="p-2 hover:bg-red-600 rounded-full text-gray-300 hover:text-white transition" title="Tutup">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="flex-1 bg-gray-900 flex items-center justify-center overflow-auto p-4 relative">
                
                <!-- PDF Viewer -->
                <iframe 
                    v-if="isPdf" 
                    :src="fileUrl" 
                    class="w-full h-full rounded bg-white" 
                    frameborder="0"
                ></iframe>

                <!-- Image Viewer -->
                <img 
                    v-else-if="isImage" 
                    :src="fileUrl" 
                    class="max-w-full max-h-full object-contain shadow-lg" 
                    alt="Preview" 
                />

                <!-- Fallback -->
                <div v-else class="text-center text-gray-400">
                    <p class="mb-4">Format file tidak didukung untuk pratinjau.</p>
                    <button @click="openInNewTab" class="primary-button">
                        Buka File
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.primary-button {
    padding: 0.5rem 1rem;
    background-color: #4F46E5;
    color: white;
    font-weight: bold;
    border-radius: 4px;
    border: none;
    cursor: pointer;
}

.primary-button:hover {
    background-color: #4338CA;
}
</style>
