<script setup>
import { ref } from 'vue'; // Ditambahkan untuk reaktivitas showPassword
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue'; // Pastikan path ini benar
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// State untuk menampilkan/menyembunyikan password
const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.post(route('admin.login'), {
        onFinish: () => {
            form.reset('password');
            showPassword.value = false; // Reset showPassword ke false setelah submit
        }
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk Akun" />

        <!-- Glass Form Teks -->
        <div class="w-full max-w-md p-0 sm:p-2">
            <div class="mb-6 sm:mb-8 text-center">
                <h2 class="mt-2 sm:mt-4 text-2xl sm:text-3xl font-extrabold tracking-tight text-white drop-shadow-md">
                    Admin & Staff Login
                </h2>
                <p class="mt-2 text-sm text-slate-300 drop-shadow-sm font-medium">
                    Selamat datang kembali! Silakan masuk ke akun Anda.
                </p>
                <p v-if="status" class="mt-3 rounded-xl bg-green-500/20 p-3 text-sm text-green-100 border border-green-500/30 backdrop-blur-sm">
                    {{ status }}
                </p>
            </div>

            <!-- Form Login -->
            <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
                <!-- Input Email -->
                <div>
                    <InputLabel for="email" value="Alamat Email" class="block text-sm font-semibold text-slate-300 drop-shadow-sm ml-1" />
                    <input
                        id="email"
                        type="email"
                        class="mt-2 block w-full appearance-none rounded-xl border border-white/20 bg-black/30 px-4 py-2.5 sm:py-3 placeholder-slate-500 text-white shadow-inner backdrop-blur-md focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/50 sm:text-sm transition-all"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@contoh.com"
                    />
                    <InputError class="mt-2 text-xs text-red-400 font-medium" :message="form.errors.email" />
                </div>

                <!-- Input Password dengan Tombol Show/Hide -->
                <div>
                    <InputLabel for="password" value="Kata Sandi" class="block text-sm font-semibold text-slate-300 drop-shadow-sm ml-1" />
                    <div class="relative mt-2">
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full appearance-none rounded-xl border border-white/20 bg-black/30 px-4 py-2.5 sm:py-3 pr-10 placeholder-slate-500 text-white shadow-inner backdrop-blur-md focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/50 sm:text-sm transition-all"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <button
                            type="button"
                            @click="togglePasswordVisibility"
                            class="absolute inset-y-0 right-0 flex items-center rounded-r-xl px-3 text-slate-500 hover:text-white focus:outline-none transition-colors"
                            aria-label="Toggle password visibility"
                        >
                            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    <InputError class="mt-2 text-xs text-red-400 font-medium" :message="form.errors.password" />
                </div>

                <!-- Opsi "Ingat Saya" dan "Lupa Kata Sandi" -->
                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center">
                        <Checkbox id="remember" name="remember" v-model:checked="form.remember" class="h-4 w-4 rounded border-white/20 bg-black/30 text-slate-500 focus:ring-slate-400" />
                        <label for="remember" class="ml-2 block text-sm text-slate-400 hover:text-slate-200 transition-colors cursor-pointer">Ingat saya</label>
                    </div>

                    <div class="text-sm">
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="font-semibold text-slate-400 hover:text-white hover:underline transition-colors"
                        >
                            Lupa kata sandi?
                        </Link>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="pt-2">
                    <button
                        type="submit"
                        class="group relative flex w-full justify-center items-center gap-2 rounded-xl border border-white/10 bg-gradient-to-br from-slate-700 to-slate-900 hover:from-slate-600 hover:to-slate-800 py-3.5 px-4 text-sm font-bold text-white shadow-[0_4px_16px_rgba(0,0,0,0.5)] backdrop-blur-sm transition-all duration-300 ease-out hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 focus:ring-offset-slate-900"
                        :class="{ 'opacity-70 cursor-not-allowed scale-100': form.processing }"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-else>
                            <svg class="h-5 w-5 text-slate-400 group-hover:text-white mb-0.5 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        {{ form.processing ? 'Memproses...' : 'Masuk ke Sistem' }}
                    </button>
                </div>
            </form>

            <!-- Link ke Halaman Registrasi -->

        </div>
    </GuestLayout>
</template>
