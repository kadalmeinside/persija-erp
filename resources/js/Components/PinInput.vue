<script setup>
import { ref, onMounted, nextTick } from 'vue';

const props = defineProps({
    modelValue: String,
    length: {
        type: Number,
        default: 6
    }
});

const emit = defineEmits(['update:modelValue', 'complete']);

const inputs = ref([]);
const values = ref(Array(props.length).fill(''));

const handleInput = (index, event) => {
    const val = event.target.value;
    
    // Allow only numbers
    if (!/^\d*$/.test(val)) {
        values.value[index] = '';
        return;
    }

    values.value[index] = val.slice(-1); // Take only last char

    updateModel();

    // Focus next input
    if (val && index < props.length - 1) {
        inputs.value[index + 1].focus();
    }

    // Check completion
    if (values.value.every(v => v !== '')) {
        emit('complete', values.value.join(''));
    }
};

const handleKeydown = (index, event) => {
    if (event.key === 'Backspace' && !values.value[index] && index > 0) {
        inputs.value[index - 1].focus();
    }
};

const handlePaste = (event) => {
    event.preventDefault();
    const pastedData = event.clipboardData.getData('text').slice(0, props.length);
    if (!/^\d+$/.test(pastedData)) return;

    pastedData.split('').forEach((char, i) => {
        values.value[i] = char;
    });
    
    updateModel();
    
    // Focus last filled or first empty
    const nextIndex = Math.min(pastedData.length, props.length - 1);
    inputs.value[nextIndex].focus();

    if (pastedData.length === props.length) {
        emit('complete', pastedData);
    }
};

const updateModel = () => {
    emit('update:modelValue', values.value.join(''));
};

const focus = () => {
    nextTick(() => {
        if (inputs.value[0]) inputs.value[0].focus();
    });
};

defineExpose({ focus, values });
</script>

<template>
    <div class="flex gap-2 justify-center">
        <input
            v-for="(v, index) in length"
            :key="index"
            ref="inputs"
            type="password"
            inputmode="numeric"
            maxlength="1"
            v-model="values[index]"
            @input="handleInput(index, $event)"
            @keydown="handleKeydown(index, $event)"
            @paste="handlePaste"
            class="w-12 h-12 text-center text-xl font-bold border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white"
        />
    </div>
</template>
