<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { XCircleIcon } from '@heroicons/vue/24/solid';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

defineProps({
    pageTitle: String,
});

// Tentukan tujuan tombol
const backUrl = computed(() => {
    if (user.value) {
        if (user.value.roles.includes('siswa')) {
            return route('siswa.tagihan.index');
        }
        if (user.value.roles.includes('admin')) {
            return route('admin.dashboard');
        }
    }
    return route('tagihan.check_form');
});
</script>

<template>
    <Head :title="pageTitle" />
    <div class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
        <main class="text-center w-full max-w-md bg-white dark:bg-gray-800 shadow-xl rounded-lg p-8">
            <XCircleIcon class="mx-auto h-16 w-16 text-red-500"/>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                Pembayaran Gagal
            </h1>
            <p class="mt-4 text-base text-gray-600 dark:text-gray-300">
                Maaf, pembayaran Anda gagal, dibatalkan, atau telah kedaluwarsa. Tidak ada dana yang ditarik. Silakan coba lagi.
            </p>
            <div class="mt-8">
                <Link :href="backUrl" class="inline-block bg-red-600 text-white font-bold py-3 px-8 rounded-md text-lg hover:bg-red-700 transition-transform hover:scale-105">
                    Kembali & Coba Lagi
                </Link>
            </div>
        </main>
    </div>
</template>
