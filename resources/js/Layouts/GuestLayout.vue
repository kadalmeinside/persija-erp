<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-950 selection:bg-slate-700 selection:text-white pt-6 sm:pt-0">
        <!-- Liquid Blobs Background (Dark/Grey Mode) - Optimized for Performance -->
        <!-- Mengganti blur-[100px] dan mix-blend-screen dengan blur-3xl dan radial-gradients murni untuk mengurangi beban GPU -->
        <div class="absolute top-0 -left-4 w-96 h-96 bg-slate-800 rounded-full filter blur-3xl opacity-50 animate-blob will-change-transform"></div>
        <div class="absolute top-0 -right-4 w-96 h-96 bg-gray-800 rounded-full filter blur-3xl opacity-50 animate-blob animation-delay-2000 will-change-transform"></div>
        <div class="absolute -bottom-8 left-20 w-96 h-96 bg-slate-700 rounded-full filter blur-3xl opacity-50 animate-blob animation-delay-4000 will-change-transform"></div>

        <!-- Floating Note Icons with Liquid Glass Gradient -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
            <!-- Global Gradient Definition for the icons -->
            <svg style="position: absolute; width: 0; height: 0;" aria-hidden="true">
                <defs>
                    <linearGradient id="liquidGlass" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.9" />
                        <stop offset="50%" stop-color="#818cf8" stop-opacity="0.7" />
                        <stop offset="100%" stop-color="#c084fc" stop-opacity="0.9" />
                    </linearGradient>
                </defs>
            </svg>

            <!-- Icon 1: Top Left -->
            <div class="absolute top-10 left-10 md:top-16 md:left-32 transform -rotate-12 opacity-40">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-32 h-32 md:w-48 md:h-48 animate-float will-change-transform">
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm5.845 1.7a2.25 2.25 0 0 0-1.42-.2H5.625a.375.375 0 0 0-.375.375V20.625c0 .207.168.375.375.375H18.375a.375.375 0 0 0 .375-.375V11.25a2.25 2.25 0 0 0-.659-1.59l-4.12-4.12a2.25 2.25 0 0 0-1.59-.66L11.47 3.2Z" clip-rule="evenodd" />
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M7.5 15.75a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
            </div>

            <!-- Icon 2: Bottom Right -->
            <div class="absolute bottom-10 right-10 md:bottom-16 md:right-32 transform rotate-12 opacity-30">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-40 h-40 md:w-56 md:h-56 animate-float-delayed will-change-transform">
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm5.845 1.7a2.25 2.25 0 0 0-1.42-.2H5.625a.375.375 0 0 0-.375.375V20.625c0 .207.168.375.375.375H18.375a.375.375 0 0 0 .375-.375V11.25a2.25 2.25 0 0 0-.659-1.59l-4.12-4.12a2.25 2.25 0 0 0-1.59-.66L11.47 3.2Z" clip-rule="evenodd" />
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M7.5 15.75a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
            </div>

            <!-- Icon 3: Middle Right / Top Right (Smaller) -->
            <div class="absolute top-1/4 right-5 md:right-20 transform rotate-45 opacity-25 hidden sm:block">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-20 h-20 md:w-28 md:h-28 animate-float-fast will-change-transform">
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm5.845 1.7a2.25 2.25 0 0 0-1.42-.2H5.625a.375.375 0 0 0-.375.375V20.625c0 .207.168.375.375.375H18.375a.375.375 0 0 0 .375-.375V11.25a2.25 2.25 0 0 0-.659-1.59l-4.12-4.12a2.25 2.25 0 0 0-1.59-.66L11.47 3.2Z" clip-rule="evenodd" />
                    <path fill="url(#liquidGlass)" fill-rule="evenodd" d="M7.5 15.75a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <!-- Glassmorphism Container -->
        <!-- Mengganti backdrop-blur-2xl (mahal) dengan backdrop-blur-md (lebih ringan tapi tetap estetik) -->
        <div class="relative z-10 w-full px-8 py-10 shadow-2xl sm:max-w-md sm:rounded-3xl bg-white/5 backdrop-blur-md border border-white/10">
            <slot />
        </div>
    </div>
</template>

<style>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
@keyframes float {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
  100% { transform: translateY(0px); }
}
.animate-blob {
  animation: blob 10s infinite alternate;
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

