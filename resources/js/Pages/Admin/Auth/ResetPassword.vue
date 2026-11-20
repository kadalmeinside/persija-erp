<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    EnvelopeIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    ArrowPathIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Kata Sandi - Persija ERP" />

        <div class="w-full">
            <div class="mb-6 text-center">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Atur Ulang Kata Sandi
                </h1>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-medium leading-relaxed">
                    Masukkan kata sandi baru untuk akun Anda.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                <div>
                    <label
                        for="email"
                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2 ml-0.5"
                    >
                        Alamat Email <span class="text-red-500">*</span>
                    </label>

                    <div class="relative group">
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
                        />
                    </div>
                    <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400 font-medium" :message="form.errors.email" />
                </div>

                <div>
                    <label
                        for="password"
                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2 ml-0.5"
                    >
                        Kata Sandi Baru <span class="text-red-500">*</span>
                    </label>

                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500 group-focus-within:text-red-600 dark:group-focus-within:text-red-400 transition-colors">
                            <LockClosedIcon class="w-5 h-5" />
                        </div>

                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-950/70 pl-11 pr-11 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm transition-all duration-200 focus:bg-white dark:focus:bg-slate-950 focus:border-red-600 dark:focus:border-red-500 focus:ring-4 focus:ring-red-500/10 dark:focus:ring-red-500/20 focus:outline-none"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />

                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200 transition-colors focus:outline-none"
                        >
                            <EyeSlashIcon v-if="showPassword" class="w-5 h-5" />
                            <EyeIcon v-else class="w-5 h-5" />
                        </button>
                    </div>
                    <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400 font-medium" :message="form.errors.password" />
                </div>

                <div>
                    <label
                        for="password_confirmation"
                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2 ml-0.5"
                    >
                        Konfirmasi Kata Sandi Baru <span class="text-red-500">*</span>
                    </label>

                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500 group-focus-within:text-red-600 dark:group-focus-within:text-red-400 transition-colors">
                            <LockClosedIcon class="w-5 h-5" />
                        </div>

                        <input
                            id="password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            class="block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-950/70 pl-11 pr-11 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm transition-all duration-200 focus:bg-white dark:focus:bg-slate-950 focus:border-red-600 dark:focus:border-red-500 focus:ring-4 focus:ring-red-500/10 dark:focus:ring-red-500/20 focus:outline-none"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />

                        <button
                            type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200 transition-colors focus:outline-none"
                        >
                            <EyeSlashIcon v-if="showConfirmPassword" class="w-5 h-5" />
                            <EyeIcon v-else class="w-5 h-5" />
                        </button>
                    </div>
                    <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400 font-medium" :message="form.errors.password_confirmation" />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="group relative flex w-full justify-center items-center gap-2.5 rounded-xl bg-gradient-to-r from-red-600 via-red-600 to-rose-700 hover:from-red-700 hover:via-red-600 hover:to-rose-800 text-white font-bold text-sm py-3.5 px-5 shadow-lg shadow-red-600/25 hover:shadow-xl hover:shadow-red-600/35 focus:outline-none focus:ring-4 focus:ring-red-500/30 active:scale-[0.99] transition-all duration-200"
                        :class="{ 'opacity-70 cursor-not-allowed transform-none': form.processing }"
                        :disabled="form.processing"
                    >
                        <ArrowPathIcon class="w-5 h-5 text-white/90 group-hover:rotate-180 transition-transform duration-500" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Kata Sandi Baru' }}</span>
                    </button>
                </div>

                <div class="pt-3 text-center">
                    <Link
                        :href="route('admin.login')"
                        class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 transition-colors"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        <span>Kembali ke Halaman Masuk</span>
                    </Link>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
