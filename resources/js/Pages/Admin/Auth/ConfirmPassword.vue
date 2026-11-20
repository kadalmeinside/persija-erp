<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LockClosedIcon, ShieldExclamationIcon } from '@heroicons/vue/24/outline';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Konfirmasi Kata Sandi - Persija ERP" />

        <div class="w-full">
            <div class="mb-6 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-600 dark:text-amber-400 p-3 mb-3 shadow-sm">
                    <ShieldExclamationIcon class="w-7 h-7" />
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Area Aman
                </h1>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-medium leading-relaxed">
                    Ini adalah area sistem yang dilindungi. Silakan konfirmasi kata sandi Anda sebelum melanjutkan.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                <div>
                    <label
                        for="password"
                        class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2 ml-0.5"
                    >
                        Kata Sandi <span class="text-red-500">*</span>
                    </label>

                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500 group-focus-within:text-red-600 dark:group-focus-within:text-red-400 transition-colors">
                            <LockClosedIcon class="w-5 h-5" />
                        </div>

                        <input
                            id="password"
                            type="password"
                            class="block w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-950/70 pl-11 pr-4 py-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm transition-all duration-200 focus:bg-white dark:focus:bg-slate-950 focus:border-red-600 dark:focus:border-red-500 focus:ring-4 focus:ring-red-500/10 dark:focus:ring-red-500/20 focus:outline-none"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            autofocus
                            placeholder="••••••••"
                        />
                    </div>
                    <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400 font-medium" :message="form.errors.password" />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="group relative flex w-full justify-center items-center gap-2.5 rounded-xl bg-gradient-to-r from-red-600 via-red-600 to-rose-700 hover:from-red-700 hover:via-red-600 hover:to-rose-800 text-white font-bold text-sm py-3.5 px-5 shadow-lg shadow-red-600/25 hover:shadow-xl hover:shadow-red-600/35 focus:outline-none focus:ring-4 focus:ring-red-500/30 active:scale-[0.99] transition-all duration-200"
                        :class="{ 'opacity-70 cursor-not-allowed transform-none': form.processing }"
                        :disabled="form.processing"
                    >
                        <span>{{ form.processing ? 'Memproses...' : 'Konfirmasi Akses' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
