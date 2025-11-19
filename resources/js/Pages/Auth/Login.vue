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
    form.post(route('login'), {
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

        <!-- Menggunakan struktur dari permintaan pengguna -->
        <div class="w-full max-w-md rounded-xl bg-white p-4 dark:bg-slate-800">
            <div class="mb-8 text-center">
                <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">
                    Masuk ke Akun Anda
                </h2>
                <p v-if="status" class="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-700 dark:text-green-50">
                    {{ status }}
                </p>
            </div>

            <!-- Form Login -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Input Email -->
                <div>
                    <InputLabel for="email" value="Alamat Email" class="block text-sm font-medium text-gray-700 dark:text-gray-300" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-slate-400 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@contoh.com"
                    />
                    <InputError class="mt-2 text-xs text-red-600 dark:text-red-400" :message="form.errors.email" />
                </div>

                <!-- Input Password dengan Tombol Show/Hide -->
                <div>
                    <InputLabel for="password" value="Kata Sandi" class="block text-sm font-medium text-gray-700 dark:text-gray-300" />
                    <div class="relative mt-1">
                        <TextInput
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full appearance-none rounded-lg border border-gray-300 px-4 py-3 pr-10 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white dark:placeholder-slate-400 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <button
                            type="button"
                            @click="togglePasswordVisibility"
                            class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-gray-500 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:text-gray-200"
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
                    <InputError class="mt-2 text-xs text-red-600 dark:text-red-400" :message="form.errors.password" />
                </div>

                <!-- Opsi "Ingat Saya" dan "Lupa Kata Sandi" -->
                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center">
                        <Checkbox id="remember" name="remember" v-model:checked="form.remember" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700 dark:focus:ring-indigo-600 dark:focus:ring-offset-slate-800" />
                        <label for="remember" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">Ingat saya</label>
                    </div>

                    <div class="text-sm">
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="font-medium text-indigo-600 hover:text-indigo-500 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            Lupa kata sandi?
                        </Link>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div>
                    <PrimaryButton
                        class="group relative flex w-full justify-center rounded-lg border border-transparent bg-indigo-600 py-3 px-4 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-150 ease-in-out dark:bg-indigo-500 dark:hover:bg-indigo-600 dark:focus:ring-offset-slate-800"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-indigo-400 group-hover:text-indigo-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <svg v-if="form.processing" class="mr-2 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Memproses...' : 'Masuk' }}
                    </PrimaryButton>
                </div>
            </form>

            <!-- Link ke Halaman Registrasi -->
            <p class="mt-10 text-center text-sm text-gray-600 dark:text-gray-400">
                Belum punya akun?
                <Link :href="route('register')" class="font-semibold leading-6 text-indigo-600 hover:text-indigo-500 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300">
                    Daftar di sini
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
