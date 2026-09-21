<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PinInput from '@/Components/PinInput.vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    mode: {
        type: String,
        default: 'verify' // 'verify', 'create', 'change'
    },
    title: {
        type: String,
        default: 'Masukkan PIN Keamanan'
    }
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
    current_pin: '',
    pin: '',
    pin_confirmation: ''
});

const errorMessage = ref('');
const processing = ref(false);

watch(() => props.show, (val) => {
    if (val) {
        form.reset();
        errorMessage.value = '';
    }
});

const submit = async () => {
    processing.value = true;
    errorMessage.value = '';

    try {
        if (props.mode === 'verify') {
            await axios.post(route('admin.pin.verify'), { pin: form.pin });
            emit('success');
            emit('close');
        } 
        else if (props.mode === 'create') {
            if (form.pin !== form.pin_confirmation) {
                throw { response: { data: { message: 'Konfirmasi PIN tidak cocok.' } } };
            }
            await axios.post(route('admin.pin.set'), { 
                pin: form.pin, 
                pin_confirmation: form.pin_confirmation 
            });
            alert('PIN berhasil dibuat!');
            emit('success');
            emit('close');
        }
        else if (props.mode === 'change') {
            if (form.pin !== form.pin_confirmation) {
                throw { response: { data: { message: 'Konfirmasi PIN tidak cocok.' } } };
            }
            await axios.post(route('admin.pin.change'), { 
                current_pin: form.current_pin,
                pin: form.pin, 
                pin_confirmation: form.pin_confirmation 
            });
            alert('PIN berhasil diubah!');
            emit('success');
            emit('close');
        }
    } catch (error) {
        if (error.response && error.response.data.message) {
            errorMessage.value = error.response.data.message;
        } else if (error.response && error.response.data.errors) {
            errorMessage.value = Object.values(error.response.data.errors).flat().join('\n');
        } else {
            errorMessage.value = 'Terjadi kesalahan sistem.';
        }
    } finally {
        processing.value = false;
    }
};

const onComplete = () => {
    if (props.mode === 'verify') {
        submit();
    }
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')" maxWidth="sm">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 text-center mb-6">{{ title }}</h2>
            
            <form @submit.prevent="submit">
                <!-- Change Mode: Current PIN -->
                <div v-if="mode === 'change'" class="mb-6">
                    <InputLabel value="PIN Lama" class="mb-2 text-center" />
                    <PinInput v-model="form.current_pin" />
                </div>

                <!-- Create/Change Mode: New PIN -->
                <div v-if="mode === 'create' || mode === 'change'" class="mb-6">
                    <InputLabel :value="mode === 'change' ? 'PIN Baru' : 'Buat PIN Baru (6 Digit)'" class="mb-2 text-center" />
                    <PinInput v-model="form.pin" />
                </div>

                <!-- Create/Change Mode: Confirm PIN -->
                <div v-if="mode === 'create' || mode === 'change'" class="mb-6">
                    <InputLabel value="Konfirmasi PIN Baru" class="mb-2 text-center" />
                    <PinInput v-model="form.pin_confirmation" />
                </div>

                <!-- Verify Mode: PIN Input -->
                <div v-if="mode === 'verify'" class="mb-6">
                    <p class="text-sm text-gray-500 mb-4 text-center">Masukkan 6 digit PIN keamanan Anda.</p>
                    <PinInput v-model="form.pin" @complete="onComplete" />
                </div>

                <div v-if="errorMessage" class="mb-4 text-sm text-red-600 font-bold bg-red-50 p-2 rounded border border-red-200 text-center">
                    {{ errorMessage }}
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="$emit('close')">Batal</SecondaryButton>
                    <PrimaryButton :disabled="processing || form.pin.length < 6">
                        {{ processing ? 'Memproses...' : (mode === 'verify' ? 'Konfirmasi' : 'Simpan PIN') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
