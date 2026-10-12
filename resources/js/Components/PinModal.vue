<script setup>
import { ref, watch, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PinInput from '@/Components/PinInput.vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ShieldExclamationIcon, LockClosedIcon, KeyIcon } from '@heroicons/vue/24/outline';

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

const page = usePage();
const currentMode = ref(props.mode);
const noPinNotice = ref(false);

const form = useForm({
    current_pin: '',
    pin: '',
    pin_confirmation: ''
});

const errorMessage = ref('');
const processing = ref(false);
const checkingStatus = ref(false);

const modalTitle = computed(() => {
    if (noPinNotice.value) return 'PIN Keamanan Diperlukan';
    if (currentMode.value === 'create') return 'Buat PIN Keamanan (6 Digit)';
    if (currentMode.value === 'change') return 'Ubah PIN Keamanan';
    return props.title || 'Masukkan PIN Keamanan';
});

const checkPinStatus = async () => {
    // Cek apakah user sudah punya PIN
    if (page.props.auth?.user && page.props.auth.user.has_pin === false) {
        noPinNotice.value = true;
        return;
    }

    try {
        checkingStatus.value = true;
        const res = await axios.get(route('admin.pin.status'));
        if (res.data && res.data.has_pin === false) {
            noPinNotice.value = true;
        } else {
            noPinNotice.value = false;
        }
    } catch (e) {
        // Fallback: biarkan user mencoba atau tampilkan form
    } finally {
        checkingStatus.value = false;
    }
};

watch(() => props.show, (val) => {
    if (val) {
        form.reset();
        errorMessage.value = '';
        currentMode.value = props.mode;
        noPinNotice.value = false;

        if (props.mode === 'verify') {
            checkPinStatus();
        }
    }
});

watch(() => props.mode, (newMode) => {
    currentMode.value = newMode;
});

const switchToCreatePin = () => {
    noPinNotice.value = false;
    currentMode.value = 'create';
    form.reset();
    errorMessage.value = '';
};

const submit = async () => {
    processing.value = true;
    errorMessage.value = '';

    try {
        if (currentMode.value === 'verify') {
            await axios.post(route('admin.pin.verify'), { pin: form.pin });
            emit('success');
            emit('close');
        } 
        else if (currentMode.value === 'create') {
            if (form.pin.length !== 6) {
                throw { response: { data: { message: 'PIN harus terdiri dari 6 digit angka.' } } };
            }
            if (form.pin !== form.pin_confirmation) {
                throw { response: { data: { message: 'Konfirmasi PIN tidak cocok.' } } };
            }
            await axios.post(route('admin.pin.set'), { 
                pin: form.pin, 
                pin_confirmation: form.pin_confirmation 
            });

            if (page.props.auth?.user) {
                page.props.auth.user.has_pin = true;
            }

            if (props.mode === 'verify') {
                // Beritahu dan beralih langsung ke verifikasi
                alert('PIN berhasil dibuat! Silakan masukkan PIN Anda untuk memverifikasi tindakan.');
                currentMode.value = 'verify';
                form.reset();
            } else {
                alert('PIN berhasil dibuat!');
                emit('success');
                emit('close');
            }
        }
        else if (currentMode.value === 'change') {
            if (form.pin.length !== 6) {
                throw { response: { data: { message: 'PIN baru harus terdiri dari 6 digit angka.' } } };
            }
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
        if (error.response && error.response.data) {
            const data = error.response.data;
            if (data.has_pin === false || (data.message && data.message.includes('belum memiliki PIN'))) {
                noPinNotice.value = true;
                return;
            }
            if (data.message) {
                errorMessage.value = data.message;
            } else if (data.errors) {
                errorMessage.value = Object.values(data.errors).flat().join('\n');
            } else {
                errorMessage.value = 'Terjadi kesalahan sistem.';
            }
        } else {
            errorMessage.value = error.message || 'Terjadi kesalahan sistem.';
        }
    } finally {
        processing.value = false;
    }
};

const onComplete = () => {
    if (currentMode.value === 'verify' && !noPinNotice.value) {
        submit();
    }
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')" maxWidth="sm">
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-900 text-center mb-5">{{ modalTitle }}</h2>
            
            <!-- KEADAAN KETIKA BELUM MEMILIKI PIN -->
            <div v-if="noPinNotice" class="text-center py-2">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-amber-600 dark:text-amber-400 mb-4 shadow-sm">
                    <ShieldExclamationIcon class="w-8 h-8" />
                </div>
                
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">Anda Belum Memiliki PIN</h3>
                <p class="text-xs text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                    Untuk menyetujui, menolak, atau memproses pembayaran, Anda diwajibkan memiliki PIN keamanan 6 digit. Silakan buat PIN sekarang.
                </p>

                <div class="flex flex-col gap-2.5">
                    <button 
                        type="button" 
                        @click="switchToCreatePin"
                        class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl shadow-md transition-all active:scale-95"
                    >
                        <KeyIcon class="w-4 h-4 mr-2" />
                        Buat PIN Sekarang
                    </button>
                    
                    <SecondaryButton @click="$emit('close')" class="w-full justify-center">
                        Batal
                    </SecondaryButton>

                    <Link :href="route('profile.edit')" class="text-[11px] text-gray-400 hover:text-blue-600 transition-colors mt-1">
                        Atau atur PIN melalui Halaman Profil &rarr;
                    </Link>
                </div>
            </div>

            <!-- FORM PIN (VERIFY, CREATE, CHANGE) -->
            <form v-else @submit.prevent="submit">
                <!-- Change Mode: Current PIN -->
                <div v-if="currentMode === 'change'" class="mb-6">
                    <InputLabel value="PIN Lama" class="mb-2 text-center" />
                    <PinInput v-model="form.current_pin" />
                </div>

                <!-- Create/Change Mode: New PIN -->
                <div v-if="currentMode === 'create' || currentMode === 'change'" class="mb-6">
                    <InputLabel :value="currentMode === 'change' ? 'PIN Baru' : 'Buat PIN Baru (6 Digit)'" class="mb-2 text-center" />
                    <p v-if="currentMode === 'create'" class="text-xs text-gray-500 mb-3 text-center">
                        Masukkan 6 digit angka untuk keamanan transaksi Anda.
                    </p>
                    <PinInput v-model="form.pin" />
                </div>

                <!-- Create/Change Mode: Confirm PIN -->
                <div v-if="currentMode === 'create' || currentMode === 'change'" class="mb-6">
                    <InputLabel value="Konfirmasi PIN Baru" class="mb-2 text-center" />
                    <PinInput v-model="form.pin_confirmation" />
                </div>

                <!-- Verify Mode: PIN Input -->
                <div v-if="currentMode === 'verify'" class="mb-6">
                    <p class="text-sm text-gray-500 mb-4 text-center">Masukkan 6 digit PIN keamanan Anda.</p>
                    <PinInput v-model="form.pin" @complete="onComplete" />
                </div>

                <div v-if="errorMessage" class="mb-4 text-xs text-red-600 font-bold bg-red-50 dark:bg-red-900/30 p-2.5 rounded-lg border border-red-200 dark:border-red-800 text-center whitespace-pre-line">
                    {{ errorMessage }}
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="$emit('close')">Batal</SecondaryButton>
                    <PrimaryButton :disabled="processing || form.pin.length < 6 || (currentMode === 'create' && form.pin_confirmation.length < 6)">
                        {{ processing ? 'Memproses...' : (currentMode === 'verify' ? 'Konfirmasi' : 'Simpan PIN') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
