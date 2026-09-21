<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    userIp: String,
});
</script>

<template>
    <Head title="Welcome | Persija ERP" />
    
    <div class="relative min-h-screen flex items-center justify-center overflow-hidden bg-slate-950 selection:bg-red-500 selection:text-white">
        <!-- Background Glowing Accents -->
        <div class="absolute top-0 left-1/4 w-[600px] h-[600px] bg-red-600 rounded-full mix-blend-screen filter blur-[150px] opacity-20 animate-[pulse_6s_ease-in-out_infinite]"></div>
        <div class="absolute bottom-0 right-1/4 w-[500px] h-[500px] bg-rose-800 rounded-full mix-blend-screen filter blur-[150px] opacity-20 animate-[pulse_8s_ease-in-out_infinite_reverse]"></div>

        <div class="relative z-10 px-6 py-12 lg:px-8 w-full max-w-6xl mx-auto flex flex-col items-center text-center">
            
            <!-- Logo Branding (Dynamic via Settings) -->
            <div class="mb-10 inline-flex items-center justify-center w-28 h-28 sm:w-32 sm:h-32 rounded-[2rem] bg-white/5 shadow-2xl shadow-red-600/20 ring-1 ring-white/20 transform transition-all duration-500 hover:scale-110 hover:-rotate-3 backdrop-blur-md p-4">
                <img 
                    v-if="$page.props.app_settings?.app_logo" 
                    :src="`/storage/${$page.props.app_settings.app_logo}`" 
                    alt="App Logo" 
                    class="w-full h-full object-contain filter drop-shadow-[0_4px_8px_rgba(0,0,0,0.5)]" 
                />
                <span v-else class="text-white font-extrabold text-6xl drop-shadow-[0_2px_4px_rgba(0,0,0,0.4)]">P</span>
            </div>

            <!-- Hero Text -->
            <h1 class="text-4xl sm:text-6xl md:text-7xl font-extrabold tracking-tight text-white mb-6 drop-shadow-lg">
                Persija <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-rose-400">ERP System</span>
            </h1>

            <p class="mt-4 text-base sm:text-xl text-slate-300 max-w-2xl mx-auto leading-relaxed mb-12 font-medium drop-shadow-sm">
                Pusat kendali manajemen internal Persija Development. Solusi Enterprise Resource Planning mutakhir, terintegrasi, dan aman untuk mengawal setiap proses krusial perusahaan.
            </p>

            <!-- Call to Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-center w-full sm:w-auto">
                <Link 
                    :href="route('admin.login')" 
                    class="group relative inline-flex w-full sm:w-auto items-center justify-center gap-3 px-8 py-4 text-sm sm:text-base font-bold text-white bg-white/5 hover:bg-white/10 border border-white/20 rounded-full shadow-[0_8px_32px_rgba(220,38,38,0.15)] backdrop-blur-xl transition-all duration-300 hover:scale-[1.03] hover:shadow-red-500/25 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-slate-950 overflow-hidden"
                >
                    <span class="absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r from-red-500 to-pink-500 transition-all group-hover:h-full group-hover:opacity-20 duration-300"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 relative z-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    <span class="relative z-10 tracking-widest uppercase">Portal Staf & Admin</span>
                </Link>
            </div>

            <!-- Footer Details -->
            <div class="mt-20 sm:mt-28 w-full border-t border-slate-800/80 pt-8 flex flex-col lg:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                <p>&copy; {{ new Date().getFullYear() }} Hak Cipta Persija Development. Terbatas Hanya Untuk Kalangan Internal (Restricted Access).</p>
                <div class="flex flex-wrap justify-center gap-3">
                    <span v-if="userIp" class="px-4 py-1.5 rounded-full bg-slate-900/80 shadow-inner border border-slate-800 flex items-center gap-2">
                        IP Tracked: <span class="text-slate-400 font-mono">{{ userIp }}</span>
                    </span>
                    <span class="px-4 py-1.5 rounded-full bg-slate-900/80 shadow-inner border border-slate-800 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span> Sistem Online Berjalan Baik
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
