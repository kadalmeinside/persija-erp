<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';

defineProps({
    pageTitle: String,
});

// --- DATA KONTEN ---
const videoPlaylist = ref([
    { type: 'video', sources: [{ src: 'https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-576p.mp4', type: 'video/mp4' }] },
    { type: 'video', sources: [{ src: 'https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-720p.mp4', type: 'video/mp4' }] },
    { type: 'video', sources: [{ src: 'https://cdn.plyr.io/static/demo/View_From_A_Blue_Moon_Trailer-1080p.mp4', type: 'video/mp4' }] },
]);

const bannerImages = ref([
    { url: 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?q=80&w=1974&auto=format&fit=crop', text: 'Jadwal Uji Coba' },
    { url: 'https://images.unsplash.com/photo-1624880357913-a8539238245b?q=80&w=1470&auto=format&fit=crop', text: 'Diskon Merchandise' },
    { url: 'https://images.unsplash.com/photo-1430232324554-8f4aebd06683?q=80&w=1032&auto=format&fit=crop', text: 'Program Latihan Baru' },
    { url: 'https://images.unsplash.com/photo-1634111848657-b14d29152bcc?q=80&w=870&auto=format&fit=crop', text: 'Pendaftaran Dibuka!' }
]);
const runningTexts = ref([
    "Selamat datang di Persija Development Soccer School.",
    "Pendaftaran untuk periode berikutnya telah dibuka.",
    "Saksikan pertandingan persahabatan akhir pekan ini pukul 15:00.",
]);

// --- REFS & LOGIC ---
const playerEl = ref(null);
let player = null;
let currentVideoIndex = ref(0);

const bannerElements = ref([]);
let currentBannerIndex = 0;
let bannerInterval = null;

onMounted(() => {
    // 1. Inisialisasi Player
    if (playerEl.value) {
        player = new Plyr(playerEl.value, {
            controls: [], autoplay: true, muted: false, loop: { active: false },
            urls: { youtube: { enabled: false }, vimeo: { enabled: false } }
        });

        if (videoPlaylist.value.length > 0) {
            player.source = videoPlaylist.value[currentVideoIndex.value];
        }

        player.on('ended', () => {
            currentVideoIndex.value = (currentVideoIndex.value + 1) % videoPlaylist.value.length;
            player.source = videoPlaylist.value[currentVideoIndex.value];
            player.play();
        });
    }

    // 2. Inisialisasi Banner
    const updateBanners = () => {
        bannerElements.value.forEach((banner, i) => {
            if (banner) {
                const dataIndex = (currentBannerIndex + i) % bannerImages.value.length;
                banner.style.backgroundImage = `url('${bannerImages.value[dataIndex].url}')`;
                const span = banner.querySelector('span');
                if (span) span.textContent = bannerImages.value[dataIndex].text;
            }
        });
        currentBannerIndex = (currentBannerIndex + 1) % bannerImages.value.length;
    };
    updateBanners();
    bannerInterval = setInterval(updateBanners, 8000);

});

onUnmounted(() => {
    if (player) {
        player.destroy();
    }
    if (bannerInterval) {
        clearInterval(bannerInterval);
    }
});

</script>

<template>
    <Head :title="pageTitle" />
    <div class="bg-gray-900 text-white main-grid p-4">

        <!-- Baris 1: Banner Iklan Atas -->
        <div class="grid grid-cols-2 gap-4">
            <div v-for="i in 2" :key="'banner-top-'+i" :ref="el => bannerElements[i-1] = el" class="ads-banner rounded-lg shadow-lg flex items-end p-4 bg-gray-800">
                <span class="font-teko text-3xl text-white drop-shadow-lg"></span>
            </div>
        </div>

        <!-- Baris 2: Pemutar Video (Tanpa Pembungkus) -->
        <video ref="playerEl" playsinline class="w-full h-auto rounded-lg shadow-lg block mx-auto"></video>
        
        <!-- Baris 3: Banner Iklan Bawah -->
        <div class="grid grid-cols-2 gap-4">
             <div v-for="i in 2" :key="'banner-bottom-'+i" :ref="el => bannerElements[i+1] = el" class="ads-banner rounded-lg shadow-lg flex items-end p-4 bg-gray-800">
                <span class="font-teko text-3xl text-white drop-shadow-lg"></span>
            </div>
        </div>

        <!-- Baris 4: Running Text -->
        <div class="bg-red-600 rounded-md shadow-lg p-2 overflow-hidden whitespace-nowrap">
            <p class="marquee-content font-semibold text-lg">
                {{ runningTexts.join(' +++ ') }}
            </p>
        </div>
    </div>
</template>

<style>
/* Style global untuk halaman ini saja */
body {
    font-family: 'Inter', sans-serif;
    overflow: hidden;
}
.font-teko {
    font-family: 'Teko', sans-serif;
}
.main-grid {
    height: 100vh;
    display: grid;
    /* Tinggi baris video sekarang 'auto' agar fleksibel sesuai konten */
    grid-template-rows: 1fr auto 1fr auto;
    gap: 1rem;
}
.ads-banner {
    background-size: cover;
    background-position: center;
    transition: background-image 1s ease-in-out;
}
.marquee-content {
    animation: marquee 30s linear infinite;
    display: inline-block;
    padding-left: 100%;
}
@keyframes marquee {
    from { transform: translateX(0); }
    to { transform: translateX(-100%); }
}
.plyr--video .plyr__controls {
    display: none !important;
}

.plyr {
    border-radius: 12px;
}
</style>
