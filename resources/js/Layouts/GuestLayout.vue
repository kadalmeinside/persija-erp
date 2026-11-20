<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';
import { useToast } from '@/Composables/useToast';
import { useThemeMode } from '@/Composables/useThemeMode';
import { watch } from 'vue';
import {
    SunIcon,
    MoonIcon,
    ArrowLeftIcon,
    ShieldCheckIcon
} from '@heroicons/vue/24/outline';
import persijaLogo from '@/Assets/img/persija-logo.png';

const page = usePage();
const toast = useToast();
const { isDark, toggleTheme } = useThemeMode();

let lastFlashSig = '';

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        const flashMsg = flash.message || null;
        const flashSuccess = flash.success || null;
        const flashError = flash.error || null;
        const flashType = flash.type || null;

        if (!flashMsg && !flashSuccess && !flashError) {
            lastFlashSig = '';
            return;
        }

        const currentSig = `${flashSuccess}|${flashError}|${flashMsg}|${flashType}`;
        if (currentSig === lastFlashSig) return;
        lastFlashSig = currentSig;

        if (flashSuccess) {
            toast.success(flashSuccess);
        } else if (flashError) {
            toast.error(flashError);
        } else if (flashMsg) {
            toast.show({
                message: flashMsg,
                type: flashType === 'danger' ? 'error' : (flashType || 'info'),
            });
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <div class="relative min-h-screen flex flex-col justify-between overflow-x-hidden bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors duration-300 selection:bg-red-600 selection:text-white">
        <Toast />

        <!-- Ambient Atmospheric Background Lights -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
            <!-- Top Left Crimson Glow -->
            <div class="absolute -top-32 -left-32 w-[420px] sm:w-[540px] h-[420px] sm:h-[540px] rounded-full bg-gradient-to-br from-red-500/20 via-rose-500/10 to-transparent dark:from-red-600/20 dark:via-rose-900/15 dark:to-transparent filter blur-3xl opacity-75 dark:opacity-60 animate-blob"></div>

            <!-- Top Right Warm Amber/Coral Glow -->
            <div class="absolute -top-28 -right-28 w-[380px] sm:w-[480px] h-[380px] sm:h-[480px] rounded-full bg-gradient-to-bl from-amber-400/15 via-orange-500/10 to-transparent dark:from-amber-600/15 dark:via-red-950/20 dark:to-transparent filter blur-3xl opacity-70 dark:opacity-50 animate-blob animation-delay-2000"></div>

            <!-- Bottom Left Slate/Rose Glow -->
            <div class="absolute -bottom-36 left-1/3 w-[450px] sm:w-[560px] h-[450px] sm:h-[560px] rounded-full bg-gradient-to-tr from-rose-200/40 via-red-100/20 to-transparent dark:from-slate-900/80 dark:via-red-950/20 dark:to-transparent filter blur-3xl opacity-60 dark:opacity-50 animate-blob animation-delay-4000"></div>

            <!-- Modern Dot Pattern Overlay -->
            <div class="absolute inset-0 bg-[radial-gradient(#94a3b8_1px,transparent_1px)] dark:bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-35 dark:opacity-20"></div>
        </div>

        <!-- Floating Note Icons with Liquid Glass Gradient -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
            <!-- Global Gradient Definition for the icons -->
            <svg style="position: absolute; width: 0; height: 0;" aria-hidden="true">
                <defs>
                    <linearGradient id="liquidGlass" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.9" />
                        <stop offset="50%" stop-color="#818cf8" stop-opacity="0.75" />
                        <stop offset="100%" stop-color="#c084fc" stop-opacity="0.9" />
                    </linearGradient>
                </defs>
            </svg>

            <!-- Icon 1: Top Left -->
            <div class="absolute top-10 left-6 sm:left-10 md:top-16 md:left-28 transform -rotate-12 opacity-35 dark:opacity-40">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-28 h-28 sm:w-36 sm:h-36 md:w-48 md:h-48 animate-float will-change-transform filter drop-shadow-sm">
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm5.845 1.7a2.25 2.25 0 0 0-1.42-.2H5.625a.375.375 0 0 0-.375.375V20.625c0 .207.168.375.375.375H18.375a.375.375 0 0 0 .375-.375V11.25a2.25 2.25 0 0 0-.659-1.59l-4.12-4.12a2.25 2.25 0 0 0-1.59-.66L11.47 3.2Z" clip-rule="evenodd" />
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M7.5 15.75a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
            </div>

            <!-- Icon 2: Bottom Right -->
            <div class="absolute bottom-10 right-6 sm:right-10 md:bottom-16 md:right-28 transform rotate-12 opacity-30 dark:opacity-35">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-36 h-36 sm:w-44 sm:h-44 md:w-56 md:h-56 animate-float-delayed will-change-transform filter drop-shadow-sm">
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm5.845 1.7a2.25 2.25 0 0 0-1.42-.2H5.625a.375.375 0 0 0-.375.375V20.625c0 .207.168.375.375.375H18.375a.375.375 0 0 0 .375-.375V11.25a2.25 2.25 0 0 0-.659-1.59l-4.12-4.12a2.25 2.25 0 0 0-1.59-.66L11.47 3.2Z" clip-rule="evenodd" />
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M7.5 15.75a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
            </div>

            <!-- Icon 3: Middle Right / Top Right (Smaller) -->
            <div class="absolute top-1/4 right-5 md:right-20 transform rotate-45 opacity-25 dark:opacity-30 hidden sm:block">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-20 h-20 md:w-28 md:h-28 animate-float-fast will-change-transform filter drop-shadow-sm">
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm5.845 1.7a2.25 2.25 0 0 0-1.42-.2H5.625a.375.375 0 0 0-.375.375V20.625c0 .207.168.375.375.375H18.375a.375.375 0 0 0 .375-.375V11.25a2.25 2.25 0 0 0-.659-1.59l-4.12-4.12a2.25 2.25 0 0 0-1.59-.66L11.47 3.2Z" clip-rule="evenodd" />
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M7.5 15.75a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <!-- Top Header Bar -->
        <header class="relative z-20 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 flex items-center justify-between">
            <!-- Brand Badge -->
            <Link
                href="/"
                class="group inline-flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full bg-white/80 hover:bg-white dark:bg-slate-900/80 dark:hover:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm backdrop-blur-md transition-all duration-200 hover:scale-[1.02]"
            >
                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-red-600/10 dark:bg-red-500/15 flex items-center justify-center p-1 border border-red-500/20 shadow-inner">
                    <img
                        v-if="$page.props.app_settings?.app_logo"
                        :src="`/storage/${$page.props.app_settings.app_logo}`"
                        alt="Persija ERP Logo"
                        class="w-full h-full object-contain"
                    />
                    <img
                        v-else
                        :src="persijaLogo"
                        alt="Persija Logo"
                        class="w-full h-full object-contain"
                    />
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <span class="text-xs sm:text-sm font-extrabold tracking-tight text-slate-900 dark:text-white">
                        PERSIJA <span class="text-red-600 dark:text-red-500">ERP</span>
                    </span>
                    <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-red-50 dark:bg-red-950/80 text-red-600 dark:text-red-400 border border-red-200/60 dark:border-red-800/60 hidden xs:inline-block">
                        Enterprise
                    </span>
                </div>
            </Link>

            <!-- Actions: Portal Link & Dark/Light Switcher -->
            <div class="flex items-center gap-2 sm:gap-3">
                <Link
                    href="/"
                    class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white bg-white/70 hover:bg-white dark:bg-slate-900/70 dark:hover:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 shadow-sm backdrop-blur-sm transition-all"
                >
                    <ArrowLeftIcon class="w-3.5 h-3.5" />
                    <span>Portal Utama</span>
                </Link>

                <!-- Theme Switcher Button -->
                <button
                    type="button"
                    @click="toggleTheme"
                    class="inline-flex items-center gap-2 px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-full text-xs font-semibold transition-all duration-200 border shadow-sm backdrop-blur-md bg-white/80 hover:bg-white text-slate-700 hover:text-slate-900 border-slate-200/90 hover:border-slate-300 dark:bg-slate-900/80 dark:hover:bg-slate-800 dark:text-slate-200 dark:hover:text-white dark:border-slate-800 dark:hover:border-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500/30 active:scale-95"
                    :title="isDark ? 'Beralih ke Tema Terang (Light Mode)' : 'Beralih ke Tema Gelap (Dark Mode)'"
                    :aria-label="isDark ? 'Beralih ke Tema Terang' : 'Beralih ke Tema Gelap'"
                >
                    <span class="relative flex h-4 w-4 items-center justify-center">
                        <SunIcon
                            v-if="isDark"
                            class="w-4 h-4 text-amber-400 transform transition-transform duration-300 rotate-0 hover:rotate-90"
                        />
                        <MoonIcon
                            v-else
                            class="w-4 h-4 text-slate-700 transform transition-transform duration-300 -rotate-12 hover:rotate-0"
                        />
                    </span>
                    <span class="font-medium text-xs">{{ isDark ? 'Terang' : 'Gelap' }}</span>
                </button>
            </div>
        </header>

        <!-- Main Form Section -->
        <main class="relative z-10 flex-1 flex items-center justify-center px-4 sm:px-6 py-6 sm:py-12">
            <div class="w-full max-w-md sm:max-w-lg transition-all duration-300">
                <!-- Card Container with Glassmorphism -->
                <div class="relative overflow-hidden rounded-3xl bg-white/90 dark:bg-slate-900/80 backdrop-blur-2xl border border-slate-200/90 dark:border-slate-800/90 shadow-[0_20px_50px_rgba(15,23,42,0.08)] dark:shadow-[0_25px_60px_rgba(0,0,0,0.7)] ring-1 ring-slate-900/5 dark:ring-white/10 px-6 py-8 sm:px-10 sm:py-10 transition-colors duration-300">
                    <!-- Top Persija Accent Line -->
                    <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-red-600 via-rose-500 to-amber-500"></div>

                    <slot />
                </div>
            </div>
        </main>

        <!-- Footer Bar -->
        <footer class="relative z-20 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-2">
                <ShieldCheckIcon class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                <span>Sistem Enterprise Terproteksi • Akses Khusus Karyawan & Manajemen</span>
            </div>
            <p>&copy; {{ new Date().getFullYear() }} PT Persija Jaya Jakarta. Seluruh hak cipta dilindungi.</p>
        </footer>
    </div>
</template>

<style>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(25px, -35px) scale(1.08); }
  66% { transform: translate(-20px, 20px) scale(0.95); }
  100% { transform: translate(0px, 0px) scale(1); }
}
@keyframes float {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
  100% { transform: translateY(0px); }
}
.animate-blob {
  animation: blob 12s infinite alternate ease-in-out;
}
.animate-float {
  animation: float 6s ease-in-out infinite;
}
.animate-float-delayed {
  animation: float 7s ease-in-out infinite;
  animation-delay: 2s;
}
.animate-float-fast {
  animation: float 4s ease-in-out infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
.animation-delay-4000 {
  animation-delay: 4s;
}
</style>
