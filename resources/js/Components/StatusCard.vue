<script setup>
import { DocumentTextIcon } from '@heroicons/vue/24/solid';
import { formatCurrency } from '@/utils/helpers';
import { getStatusBadgeClass } from '@/utils/statusHelpers'; 

defineProps({
    status: String,
    totalNominal: Number,
});
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5 flex flex-col md:flex-row justify-between items-center gap-6 bg-gradient-to-r from-gray-50 via-white to-white">
            <!-- Ikon Status -->
            <div class="flex items-center gap-4">
                <div class="p-3 rounded-full bg-white shadow-sm border border-gray-100">
                    <DocumentTextIcon class="w-8 h-8 text-indigo-600" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Status Saat Ini</p>
                    <div class="mt-1">
                        <span :class="getStatusBadgeClass(status)" class="px-3 py-1 rounded-full text-sm font-bold border shadow-sm">
                            {{ status }}
                        </span>
                    </div>
                </div>
            </div>
            <!-- Total Nominal Pengajuan -->
            <div class="text-right">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Nilai Pengajuan</p>
                <p class="text-xl sm:text-3xl font-bold text-gray-900 mt-1">{{ formatCurrency(totalNominal) }}</p>
            </div>
        </div>
    </div>
</template>
