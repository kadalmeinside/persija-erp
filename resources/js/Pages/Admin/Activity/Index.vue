<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3'; // Use router instead of Inertia for visit
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ActivityLogList from '@/Components/ActivityLogList.vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'; // Updated Icon Name

const props = defineProps({
    activities: Object,
    filters: Object
});

const search = ref(props.filters.search || '');

// Debounce search
let timeout = null;
watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            route('admin.activity-logs.index'),
            { search: value },
            { preserveState: true, replace: true }
        );
    }, 500);
});
</script>

<template>
    <Head title="System Logs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">System Logs</h2>
        </template>

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        
                        <!-- Search Bar -->
                        <div class="mb-6 flex justify-between items-center">
                            <div class="relative w-full max-w-md">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <TextInput
                                    v-model="search"
                                    type="text"
                                    class="pl-10 block w-full"
                                    placeholder="Cari User, Deskripsi, atau Subjek..."
                                />
                            </div>
                        </div>

                        <!-- Activity List -->
                        <ActivityLogList :activities="activities.data" />

                        <!-- Pagination -->
                        <div class="mt-6">
                            <Pagination :links="activities.links" />
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
