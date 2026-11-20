<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeOpenIcon, CheckCircleIcon, ArrowLeftOnRectangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email - Persija ERP" />

        <div class="w-full">
            <div class="mb-6 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 text-red-600 dark:text-red-400 p-3 mb-3 shadow-sm">
                    <EnvelopeOpenIcon class="w-7 h-7" />
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Verifikasi Email
                </h1>
                <p class="mt-2 text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-medium leading-relaxed">
                    Terima kasih telah mendaftar! Sebelum melanjutkan, mohon verifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirimkan.
                </p>

                <div
                    v-if="verificationLinkSent"
                    class="mt-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 p-3.5 text-xs sm:text-sm text-emerald-800 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800/60 flex items-center gap-2.5 text-left"
                >
                    <CheckCircleIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                    <span>Tautan verifikasi baru telah dikirimkan ke alamat email yang Anda daftarkan.</span>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <button
                        type="submit"
                        class="group relative flex w-full justify-center items-center gap-2.5 rounded-xl bg-gradient-to-r from-red-600 via-red-600 to-rose-700 hover:from-red-700 hover:via-red-600 hover:to-rose-800 text-white font-bold text-sm py-3.5 px-5 shadow-lg shadow-red-600/25 hover:shadow-xl hover:shadow-red-600/35 focus:outline-none focus:ring-4 focus:ring-red-500/30 active:scale-[0.99] transition-all duration-200"
                        :class="{ 'opacity-70 cursor-not-allowed transform-none': form.processing }"
                        :disabled="form.processing"
                    >
                        <span>{{ form.processing ? 'Mengirim...' : 'Kirim Ulang Email Verifikasi' }}</span>
                    </button>
                </div>

                <div class="pt-2 text-center">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 transition-colors"
                    >
                        <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                        <span>Keluar Akun</span>
                    </Link>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
