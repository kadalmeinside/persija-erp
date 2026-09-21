<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';

const form = useForm({
    category: '',
    subject: '',
    description: '',
    priority: 'Medium',
    attachment: null,
});

const categories = ['Error / Bug', 'Permintaan Fitur', 'Hardware / Perangkat', 'Jaringan / Internet', 'Akses / Password', 'Lainnya'];
const priorities = ['Low', 'Medium', 'High', 'Critical'];

const filePreview = ref(null);

const handleFileChange = (e) => {
    form.attachment = e.target.files[0];
    if (form.attachment) {
        filePreview.value = URL.createObjectURL(form.attachment);
    }
};

const submit = () => {
    form.post(route('admin.tickets.store'));
};
</script>

<template>
    <Head title="Buat Tiket Baru" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('admin.tickets.index')" class="text-gray-500 hover:text-gray-700">Tickets</Link>
                <span class="text-gray-400">/</span>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat Tiket Baru</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- Category & Priority -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel value="Kategori Masalah" />
                                <select v-model="form.category" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="" disabled>Pilih Kategori...</option>
                                    <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                                </select>
                                <InputError :message="form.errors.category" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel value="Prioritas" />
                                <select v-model="form.priority" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="p in priorities" :key="p" :value="p">{{ p }}</option>
                                </select>
                                <InputError :message="form.errors.priority" class="mt-2" />
                            </div>
                        </div>

                        <!-- Subject -->
                        <div>
                            <InputLabel value="Judul / Subjek" />
                            <TextInput v-model="form.subject" type="text" class="mt-1 block w-full" placeholder="Contoh: Error saat upload invoice..." />
                            <InputError :message="form.errors.subject" class="mt-2" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel value="Deskripsi Masalah" />
                            <textarea v-model="form.description" rows="5" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Jelaskan detail masalah, langkah-langkah kejadian, dll..."></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <!-- Attachment -->
                        <div>
                            <InputLabel value="Lampiran Screenshot (Opsional)" />
                            <div class="mt-2 flex items-center gap-4">
                                <label class="cursor-pointer bg-white border border-gray-300 rounded-md py-2 px-4 shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Pilih File
                                    <input type="file" @change="handleFileChange" accept="image/*,application/pdf" class="hidden" />
                                </label>
                                <span v-if="form.attachment" class="text-sm text-gray-500">{{ form.attachment.name }}</span>
                            </div>
                            <div v-if="filePreview && !form.attachment.type.includes('pdf')" class="mt-4 border rounded p-2 inline-block">
                                <img :src="filePreview" class="h-32 object-cover rounded" />
                            </div>
                            <InputError :message="form.errors.attachment" class="mt-2" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t">
                            <Link :href="route('admin.tickets.index')">
                                <SecondaryButton>Batal</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Mengirim...' : 'Kirim Tiket' }}
                            </PrimaryButton>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
