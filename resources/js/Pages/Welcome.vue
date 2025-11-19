<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon, ArrowRightIcon } from '@heroicons/vue/24/solid';

const page = usePage();

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    userIp: String, // Prop untuk alamat IP dari backend
    allKelas: Array, // Prop baru untuk data sekolah (cabang)
});

const user = computed(() => page.props.auth.user);

// --- Banner Slider Logic ---
const slides = ref([
    {
        title: 'SOCCER SCHOOL',
        subtitle: 'Cabang Ciledug',
        image: 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?q=80&w=2835&auto=format&fit=crop',
    },
    {
        title: 'SKILL DEVELOPMENT',
        subtitle: 'Latihan Intensif',
        image: 'https://images.unsplash.com/photo-1628029437115-a48bbb6e6ef8?q=80&w=2071&auto=format&fit=crop',
    },
    {
        title: 'TEAMWORK & FUN',
        subtitle: 'Untuk Semua Usia',
        image: 'https://images.unsplash.com/photo-1507626614093-a8b16cfbfd00?q=80&w=2070&auto=format&fit=crop',
    }
]);
const currentSlide = ref(0);
let slideInterval = null;

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % slides.value.length;
};
const prevSlide = () => {
    currentSlide.value = (currentSlide.value - 1 + slides.value.length) % slides.value.length;
};

// --- School Slider Logic ---
const schoolSliderContainer = ref(null);

const scrollSchoolSlider = (direction) => {
    if (schoolSliderContainer.value) {
        const scrollAmount = schoolSliderContainer.value.offsetWidth * 0.8; // Gulir sejauh 80% dari lebar terlihat
        schoolSliderContainer.value.scrollBy({
            left: direction === 'next' ? scrollAmount : -scrollAmount,
            behavior: 'smooth',
        });
    }
};

onMounted(() => {
    slideInterval = setInterval(nextSlide, 5000); // Ganti slide banner setiap 5 detik
});

onUnmounted(() => {
    clearInterval(slideInterval);
});

</script>

<template>
    <Head title="Selamat Datang" />
    <div class="bg-white dark:bg-zinc-900 text-black/80 dark:text-white/80 selection:bg-red-600 selection:text-white">

        <!-- Header -->
        <header class="absolute top-0 left-0 right-0 z-20">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <a href="/" class="flex items-center space-x-2">
                    <!-- <img src="https://images.seeklogo.com/logo-png/34/1/persija-logo-png_seeklogo-341628.png" alt="Persija Logo" class="h-10 w-auto filter grayscale brightness-0 invert"> -->
                    <span class="text-white font-teko text-2xl font-bold tracking-wider">PERSIJA DEVELOPMENT</span>
                </a>
                <!-- <div class="hidden md:flex items-center space-x-6">
                    <a href="#cabang" class="text-white/80 hover:text-white font-semibold transition-colors">Cabang</a>
                    <a href="#" class="text-white/80 hover:text-white font-semibold transition-colors">Program</a>
                    <a href="#" class="text-white/80 hover:text-white font-semibold transition-colors">Tentang Kami</a>
                </div> -->
                <div class="flex items-center">
                    <!-- <a href="#" class="bg-white text-gray-900 font-bold py-2 px-5 rounded-md text-sm hover:bg-gray-200 transition-colors">MENU UTAMA</a> -->
                    <nav v-if="canLogin" class="flex flex-1 justify-end">
                        <!-- If user is logged in, show a link to their dashboard -->
                        <Link
                            v-if="user"
                            :href="route('dashboard')"
                            class="rounded-md px-3 py-2 ring-1 ring-transparent transition hover:text-white focus:outline-none focus-visible:ring-indigo-500 dark:hover:text-white/80"
                        >
                            Dashboard
                        </Link>

                        <!-- If user is a guest, show the student/guardian login link -->
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-white rounded-md px-3 py-2 ring-1 ring-transparent transition hover:text-white/70 focus:outline-none focus-visible:ring-indigo-500 dark:hover:text-white/80"
                            >
                                Login Siswa / Wali
                            </Link>
                        </template>
                    </nav>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main>
            <!-- Banner Slider Section -->
            <section id="banner-slider" class="relative h-[70vh] md:h-[90vh] w-full overflow-hidden bg-zinc-900">
                <!-- Slides -->
                <div id="banner-slides-container">
                    <div v-for="(slide, index) in slides" :key="index" class="banner-slide absolute inset-0 h-full w-full transition-opacity duration-1000 ease-in-out" :class="index === currentSlide ? 'opacity-100' : 'opacity-0'">
                        <img :src="slide.image" :alt="slide.title" class="h-full w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-transparent"></div>
                        <div class="absolute inset-0 flex items-center">
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-white">
                                    <div class="w-full md:w-3/4">
                                            <div class="flex flex-wrap items-baseline gap-x-4">
                                                <h1 class="font-teko text-6xl md:text-8xl font-bold uppercase leading-none tracking-tight">{{ slide.title }}</h1>
                                                <h2 class="font-teko text-4xl md:text-6xl font-semibold uppercase text-red-600">{{ slide.subtitle }}</h2>
                                            </div>
                                            <p class="mt-4 text-xl font-semibold uppercase tracking-widest">Open Registration</p>
                                            <a href="#" class="mt-8 inline-block bg-red-600 text-white font-bold py-3 px-8 rounded-md text-lg hover:bg-red-700 transition-transform hover:scale-105">DAFTAR SEKARANG</a>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Slider Controls -->
                <button @click="prevSlide" class="absolute left-4 top-1/2 -translate-y-1/2 z-10 p-3 bg-white/20 hover:bg-white/40 rounded-full text-white transition-colors">
                    <ChevronLeftIcon class="h-6 w-6"/>
                </button>
                <button @click="nextSlide" class="absolute right-4 top-1/2 -translate-y-1/2 z-10 p-3 bg-white/20 hover:bg-white/40 rounded-full text-white transition-colors">
                    <ChevronRightIcon class="h-6 w-6"/>
                </button>
            </section>

            <!-- Schools Slider Section -->
            <section id="cabang" class="py-16 sm:py-24 bg-gray-100 dark:bg-zinc-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between mb-12">
                        <h2 class="text-4xl md:text-5xl font-bold font-teko tracking-tight text-gray-900 dark:text-white">TEMUKAN CABANG KAMI</h2>
                        <div class="hidden sm:flex items-center space-x-3">
                            <button @click="scrollSchoolSlider('prev')" class="p-3 bg-white dark:bg-zinc-800 rounded-full shadow-md hover:bg-gray-200 dark:hover:bg-zinc-700 transition">
                                <ChevronLeftIcon class="h-5 w-5 text-gray-700 dark:text-gray-200"/>
                            </button>
                            <button @click="scrollSchoolSlider('next')" class="p-3 bg-white dark:bg-zinc-800 rounded-full shadow-md hover:bg-gray-200 dark:hover:bg-zinc-700 transition">
                                <ChevronRightIcon class="h-5 w-5 text-gray-700 dark:text-gray-200"/>
                            </button>
                        </div>
                    </div>
                    <div id="school-slider" class="relative">
                        <div ref="schoolSliderContainer" class="flex space-x-6 overflow-x-auto pb-4 slider-container scroll-smooth snap-x snap-mandatory">
                            <div v-for="kelas in allKelas" :key="kelas.nama_kelas" class="flex-shrink-0 w-80 snap-start">
                                <div class="group relative overflow-hidden rounded-lg shadow-lg">
                                    <img :src="kelas.gambar" :alt="kelas.nama_kelas" class="h-56 w-full object-cover transition-transform duration-300 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                                    <div class="absolute bottom-0 left-0 p-4 text-white">
                                        <h3 class="text-lg font-bold">{{ kelas.nama_kelas }}</h3>
                                        <p class="mt-1 text-sm opacity-90">{{ kelas.deskripsi }}</p>
                                    </div>
                                    <div v-if="kelas.isNew" class="absolute top-2 right-2 bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full">BARU DIBUKA</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-12 text-center">
                        <a href="#" class="bg-red-600 text-white font-bold py-3 px-8 rounded-md text-lg hover:bg-red-700 transition-transform hover:scale-105">Lihat Selengkapnya</a>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-zinc-800 dark:bg-black">
             <div class="max-w-7xl mx-auto py-8 px-6 flex justify-between items-center text-xs text-gray-400">
                <p>&copy; {{ new Date().getFullYear() }} Persija Development. All rights reserved.</p>
                <Link :href="route('admin.login')" class="hover:text-gray-100 transition">
                    (IP: {{ userIp }})
                </Link>
             </div>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Teko:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap');

body {
    font-family: 'Inter', sans-serif;
}
.font-teko {
    font-family: 'Teko', sans-serif;
}
.slider-container {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.slider-container::-webkit-scrollbar {
    display: none;
}
</style>
