<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
    customer: Object // If provided, we are in Edit mode
});

const emit = defineEmits(['close']);

const isEditing = computed(() => !!props.customer);

const form = useForm({
    id: null,
    nama_pelanggan: '',
    email: '',
    telepon: '',
    alamat: '',
    npwp: ''
});

// Watch for changes in props.customer to populate form
watch(() => props.customer, (newVal) => {
    if (newVal) {
        form.id = newVal.id;
        form.nama_pelanggan = newVal.nama_pelanggan;
        form.email = newVal.email;
        form.telepon = newVal.telepon;
        form.alamat = newVal.alamat;
        form.npwp = newVal.npwp;
    } else {
        form.reset();
        form.clearErrors();
    }
}, { immediate: true });

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.customers.update', form.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.customers.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const closeModal = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                {{ isEditing ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru' }}
            </h2>
            
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <InputLabel value="Nama Pelanggan" />
                    <TextInput v-model="form.nama_pelanggan" class="mt-1 block w-full" required placeholder="PT. Contoh Sejahtera" />
                    <InputError :message="form.errors.nama_pelanggan" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <InputLabel value="Email" />
                        <TextInput type="email" v-model="form.email" class="mt-1 block w-full" placeholder="email@perusahaan.com" />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel value="Telepon" />
                        <TextInput v-model="form.telepon" class="mt-1 block w-full" placeholder="021-xxxxxxx" />
                        <InputError :message="form.errors.telepon" class="mt-2" />
                    </div>
                </div>

                <div class="mb-4">
                    <InputLabel value="NPWP" />
                    <TextInput v-model="form.npwp" class="mt-1 block w-full" placeholder="00.000.000.0-000.000" />
                    <InputError :message="form.errors.npwp" class="mt-2" />
                </div>

                <div class="mb-6">
                    <InputLabel value="Alamat" />
                    <textarea v-model="form.alamat" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3" placeholder="Alamat lengkap..."></textarea>
                    <InputError :message="form.errors.alamat" class="mt-2" />
                </div>

                <div class="flex justify-end gap-4">
                    <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan Pelanggan' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
