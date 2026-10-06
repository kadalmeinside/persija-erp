<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    activeTab: {
        type: String,
        required: true,
        validator: (value) => ['cuti', 'pengajuan'].includes(value)
    }
});

const page = usePage();
const isCutiApprover = computed(() => page.props.is_cuti_approver || false);
const isPengajuanApprover = computed(() => page.props.is_pengajuan_approver || false);

const tabs = computed(() => {
    const list = [];
    if (isCutiApprover.value) {
        list.push({
            id: 'cuti',
            name: 'Persetujuan Cuti',
            href: route('admin.cuti.approvals'),
            current: props.activeTab === 'cuti'
        });
    }
    if (isPengajuanApprover.value) {
        list.push({
            id: 'pengajuan',
            name: 'Persetujuan Pengajuan',
            href: route('admin.pengajuan.approvals'),
            current: props.activeTab === 'pengajuan'
        });
    }
    return list;
});
</script>

<template>
    <div class="mb-6">
        <div class="sm:hidden">
            <label for="tabs" class="sr-only">Pilih tab persetujuan</label>
            <select id="tabs" name="tabs" class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-primary-500 focus:outline-none focus:ring-primary-500 sm:text-sm"
                @change="(e) => {
                    const selected = tabs.find(t => t.id === e.target.value);
                    if(selected) window.location.href = selected.href;
                }">
                <option v-for="tab in tabs" :key="tab.id" :value="tab.id" :selected="tab.current">{{ tab.name }}</option>
            </select>
        </div>
        <div class="hidden sm:block">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <Link v-for="tab in tabs" :key="tab.id" :href="tab.href"
                        :class="[
                            tab.current 
                                ? 'border-primary-500 text-primary-600 dark:text-primary-400 dark:border-primary-400' 
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600',
                            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                        ]"
                        :aria-current="tab.current ? 'page' : undefined">
                        {{ tab.name }}
                    </Link>
                </nav>
            </div>
        </div>
    </div>
</template>
