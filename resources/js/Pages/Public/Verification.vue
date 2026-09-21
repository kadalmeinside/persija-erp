<script setup>
import { Head } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
    verification: Object
});
</script>

<template>
    <Head title="Verifikasi Dokumen" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div class="flex justify-center mb-6">
                <!-- Logo Placehoder -->
                <div class="text-2xl font-bold text-gray-700">PJH-ERP</div>
            </div>

            <div v-if="verification.isValid" class="text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h2 class="mt-2 text-xl font-bold text-gray-800">Dokumen Valid</h2>
                    <p class="text-sm text-gray-500">Approval Terverifikasi Sistem</p>
                </div>

                <div class="border-t border-gray-200 mt-4 pt-4 text-left">
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <span class="text-xs text-gray-500 uppercase block">Disetujui Oleh</span>
                            <span class="font-semibold text-gray-800">{{ verification.approverName }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase block">Tanggal Persetujuan</span>
                            <span class="font-semibold text-gray-800">{{ verification.approvedAt }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase block">Tipe Dokumen</span>
                            <span class="font-semibold text-gray-800">{{ verification.requestType }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase block">Nomor / Referensi</span>
                            <span class="font-semibold text-gray-800">{{ verification.requestNo }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase block">Status Saat Ini</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ verification.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h2 class="mt-2 text-xl font-bold text-gray-800">Dokumen Tidak Ditemukan</h2>
                    <p class="text-sm text-red-500">Kode verifikasi tidak valid atau data tidak ditemukan.</p>
                </div>
            </div>
            
            <div class="mt-8 text-center text-xs text-gray-400">
                &copy; {{ new Date().getFullYear() }} Persija Jaya Jakarta. All rights reserved.
            </div>
        </div>
    </div>
</template>
