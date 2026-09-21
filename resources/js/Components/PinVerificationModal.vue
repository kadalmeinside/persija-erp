<script setup>
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PinInput from '@/Components/PinInput.vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'verified']);

const pin = ref('');
const error = ref('');
const processing = ref(false);
const pinInputRef = ref(null);

const verifyPin = async () => {
    if (pin.value.length !== 6) {
        error.value = 'PIN harus 6 digit.';
        return;
    }

    processing.value = true;
    error.value = '';

    try {
        await axios.post(route('admin.auth.verify-pin'), { pin: pin.value });
        emit('verified');
        close();
    } catch (err) {
        error.value = err.response?.data?.message || 'Verifikasi gagal.';
        // Reset PIN on error
        if (pinInputRef.value) {
            pinInputRef.value.values.fill('');
            pin.value = '';
            pinInputRef.value.focus();
        }
    } finally {
        processing.value = false;
    }
};

const close = () => {
    pin.value = '';
    error.value = '';
    emit('close');
};

const onPinComplete = (val) => {
    pin.value = val;
    verifyPin(); // Auto submit on complete
};
</script>

<template>
    <Modal :show="show" @close="close" maxWidth="sm">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 text-center mb-4">
                Verifikasi PIN
            </h2>

            <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-6">
                Masukkan 6 digit PIN keamanan Anda untuk melanjutkan.
            </p>

            <div class="mb-6">
                <PinInput
                    ref="pinInputRef"
                    v-model="pin"
                    @complete="onPinComplete"
                />
            </div>

            <p v-if="error" class="text-sm text-red-600 text-center mb-4 font-medium">
                {{ error }}
            </p>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="close" :disabled="processing">
                    Batal
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
