<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon, ArrowLeftIcon, PaperAirplaneIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Kata Sandi - Persija ERP" />

        <div class="w-full">
            <div class="mb-6 text-center">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Lupa Kata Sandi?
                </h1>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-medium leading-relaxed">
                    Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
                </p>

                <div
                    v-if="status"
                    class="mt-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 p-3.5 text-xs sm:text-sm text-emerald-800 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 flex items-center gap-2.5 text-left"
                >
                    <CheckCircleIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                    <span>{{ status }}</span>
                </div>
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
                            placeholder="nama@persija.id"
                        />
                    </div>
                    <InputError class="mt-1.5 text-xs text-red-600 dark:text-red-400 font-medium" :message="form.errors.email" />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="group relative flex w-full justify-center items-center gap-2.5 rounded-xl bg-gradient-to-r from-red-600 via-red-600 to-rose-700 hover:from-red-700 hover:via-red-600 hover:to-rose-800 text-white font-bold text-sm py-3.5 px-5 shadow-lg shadow-red-600/25 hover:shadow-xl hover:shadow-red-600/35 focus:outline-none focus:ring-4 focus:ring-red-500/30 active:scale-[0.99] transition-all duration-200"
                        :class="{ 'opacity-70 cursor-not-allowed transform-none': form.processing }"
                        :disabled="form.processing"
                    >
                        <PaperAirplaneIcon class="w-5 h-5 text-white/90 group-hover:translate-x-0.5 transition-transform" />
                        <span>{{ form.processing ? 'Mengirim Tautan...' : 'Kirim Tautan Reset Kata Sandi' }}</span>
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
