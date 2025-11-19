<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        default: () => [], // { value: 'foo', label: 'Foo' }
    },
    valueField: {
        type: String,
        default: 'value'
    },
    labelField: {
        type: String,
        default: 'label'
    },
    placeholder: String,
    required: Boolean,
});

defineEmits(['update:modelValue']);
const select = ref(null);
onMounted(() => {
    if (select.value.hasAttribute('autofocus')) {
        select.value.focus();
    }
});
defineExpose({ focus: () => select.value.focus() });
</script>

<template>
    <select
        ref="select"
        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        :required="required"
    >
        <option v-if="placeholder" value="" disabled selected>{{ placeholder }}</option>
        <option v-for="option in options" :key="option[valueField]" :value="option[valueField]">
            {{ option[labelField] }}
        </option>
    </select>
</template>