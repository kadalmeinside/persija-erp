<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: [String, Number, Object],
    options: {
        type: Array,
        default: () => [],
    },
    labelField: {
        type: String,
        default: 'label',
    },
    valueField: {
        type: String,
        default: 'value',
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);

const selectedLabel = computed(() => {
    const selected = props.options.find(opt => opt[props.valueField] === props.modelValue);
    return selected ? selected[props.labelField] : '';
});

// Initialize search query if value exists
watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        const selected = props.options.find(opt => opt[props.valueField] === newVal);
        if (selected) {
            searchQuery.value = selected[props.labelField];
        }
    } else {
        searchQuery.value = '';
    }
}, { immediate: true });

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt => 
        String(opt[props.labelField]).toLowerCase().includes(query)
    );
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        // When opening, if there is a selected value, keep the search query as the label
        // If we want to clear on open to search fresh, we could do searchQuery.value = ''
        // But usually users want to see what's selected.
        // Let's select the text so they can easily type over it.
    }
};

const selectOption = (option) => {
    emit('update:modelValue', option[props.valueField]);
    searchQuery.value = option[props.labelField];
    isOpen.value = false;
};

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
        // Reset search query to selected value label if closed without selection
        const selected = props.options.find(opt => opt[props.valueField] === props.modelValue);
        searchQuery.value = selected ? selected[props.labelField] : '';
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

// Handle input typing
const onInput = () => {
    if (!isOpen.value) isOpen.value = true;
    // If user clears input, emit null/empty
    if (searchQuery.value === '') {
        emit('update:modelValue', null);
    }
};

</script>

<template>
    <div ref="containerRef" class="relative">
        <div class="relative">
            <input
                type="text"
                v-model="searchQuery"
                @click="toggleDropdown"
                @input="onInput"
                :placeholder="placeholder"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <div v-if="isOpen" class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white dark:bg-gray-800 py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
            <ul v-if="filteredOptions.length > 0">
                <li
                    v-for="option in filteredOptions"
                    :key="option[valueField]"
                    @click="selectOption(option)"
                    class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900 dark:text-white hover:bg-indigo-600 hover:text-white"
                >
                    <span class="block truncate" :class="{ 'font-semibold': modelValue === option[valueField] }">
                        {{ option[labelField] }}
                    </span>
                    
                    <span v-if="modelValue === option[valueField]" class="absolute inset-y-0 right-0 flex items-center pr-4 text-indigo-600 hover:text-white">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </li>
            </ul>
            <div v-else class="py-2 px-3 text-gray-500 dark:text-gray-400">
                No results found.
            </div>
        </div>
    </div>
</template>
