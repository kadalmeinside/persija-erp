<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { computed } from 'vue';

const props = defineProps({
    jenisCuti: Array,
    balances: Array
});

const form = useForm({
    id_jenis_cuti: '',
    tgl_mulai: '',
    tgl_selesai: '',
    alasan: ''
});

const selectedBalance = computed(() => {
    if (!form.id_jenis_cuti) return null;
    return props.balances.find(b => b.id_jenis_cuti == form.id_jenis_cuti);
});

const submit = () => {
    form.post(route('admin.cuti.store'));
};
</script>

<template>
    <Head title="Ajukan Cuti" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Ajukan Cuti Baru</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <InputLabel value="Jenis Cuti" />
                            <select v-model="form.id_jenis_cuti" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="" disabled>Pilih Jenis Cuti</option>
                                <option v-for="jenis in jenisCuti" :key="jenis.id" :value="jenis.id">{{ jenis.nama_cuti }}</option>
                            </select>
                            <p v-if="selectedBalance" class="mt-1 text-sm text-gray-500">
                                Sisa Saldo: <span class="font-bold text-indigo-600">{{ selectedBalance.saldo_akhir }}</span> hari
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel value="Tanggal Mulai" />
                                <TextInput type="date" v-model="form.tgl_mulai" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel value="Tanggal Selesai" />
                                <TextInput type="date" v-model="form.tgl_selesai" class="mt-1 block w-full" required />
                            </div>
                        </div>

                        <div class="mb-6">
                            <InputLabel value="Alasan Cuti" />
                            <textarea v-model="form.alasan" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3" required></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link :href="route('admin.cuti.my-requests')" class="text-gray-600 hover:text-gray-900">Batal</Link>
                            <PrimaryButton :disabled="form.processing">
                                Kirim Pengajuan
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
