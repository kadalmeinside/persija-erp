<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    EnvelopeIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    ArrowRightEndOnRectangleIcon,
    ShieldCheckIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import persijaLogo from '@/Assets/img/persija-logo.png';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: null,
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
            showPassword.value = false;
        }
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk Akun - Persija ERP" />

        <div class="w-full">
            <!-- Header Brand & Title -->
            <div class="mb-6 sm:mb-8 text-center">
                <!-- Logo Badge -->
                <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-slate-50 dark:bg-slate-800/90 border border-slate-200/90 dark:border-slate-700/80 shadow-md shadow-red-600/10 ring-4 ring-red-500/10 dark:ring-red-500/20 p-3 mb-4 transition-transform duration-300 hover:scale-105">
                    <img
                        v-if="$page.props.app_settings?.app_logo"
                        :src="`/storage/${$page.props.app_settings.app_logo}`"
                        alt="App Logo"
                        class="w-full h-full object-contain filter drop-shadow-sm"
                    />
                    <img
                        v-else
                        :src="persijaLogo"
                        alt="Persija Logo"
                        class="w-full h-full object-contain filter drop-shadow-sm"
                    />
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Masuk ke Sistem
                </h1>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-medium max-w-sm mx-auto leading-relaxed">
                    Selamat datang! Masukkan email dan kata sandi Anda untuk mengakses dashboard operasional.
                </p>

                <!-- Status Banner (e.g. Password Reset) -->
                <div
                    v-if="status"
                    class="mt-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 p-3.5 text-xs sm:text-sm text-emerald-800 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 flex items-center gap-2.5 text-left"
                >
                    <CheckCircleIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                    <span>{{ status }}</span>
                </div>
            </div>

            <!-- Form Login -->
            <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                <!-- Input Email -->
                <div>
                    <label
                        for="email"
                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2 ml-0.5"
                    >
                        Alamat Email <span class="text-red-500">*</span>
                    </label>

                    <div class="relative group">
                        <!-- Icon Left -->
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500 group-focus-within:text-red-600 dark:group-focus-within:text-red-400 transition-colors">
                            <EnvelopeIcon class="w-5 h-5" />
                        </div>

                        <input
                            id="email"
                            type="email"
                            class="block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-950/70 pl-11 pr-4 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm transition-all duration-200 focus:bg-white dark:focus:bg-slate-950 focus:border-red-600 dark:focus:border-red-500 focus:ring-4 focus:ring-red-500/10 dark:focus:ring-red-500/20 focus:outline-none"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nama@persija.id"
                        />
                    </div>
                    <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400 font-medium" :message="form.errors.email" />
                </div>

                <!-- Input Password dengan Toggle Show/Hide -->
                <div>
                    <label
                        for="password"
                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2 ml-0.5"
                    >
                        Kata Sandi <span class="text-red-500">*</span>
                    </label>

                    <div class="relative group">
                        <!-- Icon Left -->
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500 group-focus-within:text-red-600 dark:group-focus-within:text-red-400 transition-colors">
                            <LockClosedIcon class="w-5 h-5" />
                        </div>

                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-950/70 pl-11 pr-11 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm transition-all duration-200 focus:bg-white dark:focus:bg-slate-950 focus:border-red-600 dark:focus:border-red-500 focus:ring-4 focus:ring-red-500/10 dark:focus:ring-red-500/20 focus:outline-none"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />

                        <!-- Toggle Show/Hide Button -->
                        <button
                            type="button"
                            @click="togglePasswordVisibility"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200 transition-colors focus:outline-none focus:text-red-600"
                            :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        >
                            <EyeSlashIcon v-if="showPassword" class="w-5 h-5" />
                            <EyeIcon v-else class="w-5 h-5" />
                        </button>
                    </div>
                    <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400 font-medium" :message="form.errors.password" />
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <Checkbox
                            id="remember"
                            name="remember"
                            v-model:checked="form.remember"
                            class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-red-600 focus:ring-red-500 focus:ring-offset-0"
                        />
                        <span class="text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
                            Ingat saya
                        </span>
                    </label>

                    <div v-if="canResetPassword" class="text-xs sm:text-sm">
                        <Link
                            :href="route('password.request')"
                            class="font-semibold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 hover:underline transition-colors"
                        >
                            Lupa kata sandi?
                        </Link>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button
                        type="submit"
                        class="group relative flex w-full justify-center items-center gap-2.5 rounded-xl bg-gradient-to-r from-red-600 via-red-600 to-rose-700 hover:from-red-700 hover:via-red-600 hover:to-rose-800 text-white font-bold text-sm py-3.5 px-5 shadow-lg shadow-red-600/25 hover:shadow-xl hover:shadow-red-600/35 focus:outline-none focus:ring-4 focus:ring-red-500/30 active:scale-[0.99] transition-all duration-200"
                        :class="{ 'opacity-70 cursor-not-allowed transform-none': form.processing }"
                        :disabled="form.processing"
                    >
                        <svg
                            v-if="form.processing"
                            class="h-5 w-5 animate-spin text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <ArrowRightEndOnRectangleIcon
                            v-else
                            class="w-5 h-5 text-white/90 group-hover:translate-x-0.5 transition-transform"
                        />

                        <span>{{ form.processing ? 'Memverifikasi Akun...' : 'Masuk ke Sistem' }}</span>
                    </button>
                </div>
            </form>

            <!-- Security Footnote Badge -->
            <div class="mt-6 pt-5 border-t border-slate-200/80 dark:border-slate-800/80 flex items-center justify-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                <ShieldCheckIcon class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                <span>Koneksi aman terenkripsi (TLS / SHA-256)</span>
            </div>
        </div>
    </GuestLayout>
</template>
