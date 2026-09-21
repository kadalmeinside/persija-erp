<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { PlusIcon, ArrowPathIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/solid';
import Swal from 'sweetalert2';

const props = defineProps({
    holidays: Object,
    filters: Object
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
    id: null,
    tanggal: '',
    keterangan: ''
});

const syncForm = useForm({
    year: new Date().getFullYear()
});

watch(search, (value) => {
    router.get(route('admin.hari-libur.index'), { search: value }, { preserveState: true, replace: true });
});

const formatDateForInput = (dateValue) => {
    if (!dateValue) return '';
    // Handle ISO string or simple YYYY-MM-DD
    if (typeof dateValue === 'string') {
        return dateValue.substring(0, 10);
    }
    // Handle Date object
    if (dateValue instanceof Date) {
         // Create a local date string YYYY-MM-DD
         const offset = dateValue.getTimezoneOffset();
         const localDate = new Date(dateValue.getTime() - (offset * 60 * 1000));
         return localDate.toISOString().substring(0, 10);
    }
    return '';
};

const openModal = (holiday = null) => {
    isEditing.value = !!holiday;
    if (holiday) {
        form.id = holiday.id;
        form.tanggal = formatDateForInput(holiday.tanggal);
        form.keterangan = holiday.keterangan;
    } else {
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.hari-libur.update', form.id), {
            onSuccess: () => {
                closeModal();
                Swal.fire('Berhasil', 'Data berhasil diperbarui', 'success');
            }
        });
    } else {
        form.post(route('admin.hari-libur.store'), {
            onSuccess: () => {
                closeModal();
                Swal.fire('Berhasil', 'Data berhasil ditambahkan', 'success');
            }
        });
    }
};

const deleteHoliday = (id) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.hari-libur.destroy', id), {
                onSuccess: () => Swal.fire('Terhapus!', 'Data berhasil dihapus.', 'success')
            });
        }
    });
};

const syncHolidays = () => {
    Swal.fire({
        title: 'Sinkronisasi Online',
        text: `Ambil data libur tahun ${syncForm.year}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Sinkronisasi'
    }).then((result) => {
        if (result.isConfirmed) {
            syncForm.post(route('admin.hari-libur.fetch'), {
                onSuccess: () => Swal.fire('Berhasil', 'Data berhasil disinkronisasi.', 'success'),
                onError: () => Swal.fire('Gagal', 'Gagal sinkronisasi.', 'error')
            });
        }
    });
};
</script>

<template>
    <Head title="Master Data Hari Libur" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Master Data Hari Libur</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <!-- Toolbar -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div class="flex gap-2">
                            <PrimaryButton @click="openModal()">
                                <PlusIcon class="w-5 h-5 mr-2" /> Tambah Manual
                            </PrimaryButton>
                            <SecondaryButton @click="syncHolidays" :disabled="syncForm.processing">
                                <ArrowPathIcon class="w-5 h-5 mr-2" :class="{'animate-spin': syncForm.processing}" /> 
                                Sync Online ({{ syncForm.year }})
                            </SecondaryButton>
                        </div>
                        <div class="w-full md:w-1/3">
                            <TextInput v-model="search" placeholder="Cari hari libur..." class="w-full" />
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="holiday in holidays.data" :key="holiday.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ new Date(holiday.tanggal).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ holiday.keterangan }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openModal(holiday)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                        <button @click="deleteHoliday(holiday.id)" class="text-red-600 hover:text-red-900">
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="holidays.data.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">Tidak ada data hari libur.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <Pagination :links="holidays.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ isEditing ? 'Edit Hari Libur' : 'Tambah Hari Libur' }}
                </h2>
                
                <div class="mb-4">
                    <InputLabel for="tanggal" value="Tanggal" />
                    <TextInput id="tanggal" type="date" class="mt-1 block w-full" v-model="form.tanggal" />
                    <InputError :message="form.errors.tanggal" class="mt-2" />
                </div>

                <div class="mb-4">
                    <InputLabel for="keterangan" value="Keterangan" />
                    <TextInput id="keterangan" type="text" class="mt-1 block w-full" v-model="form.keterangan" placeholder="Contoh: Tahun Baru 2025" />
                    <InputError :message="form.errors.keterangan" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                    <PrimaryButton class="ml-3" @click="submit" :disabled="form.processing">
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
