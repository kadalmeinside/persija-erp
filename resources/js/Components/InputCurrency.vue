<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: [Number, String],
    placeholder: String,
    disabled: Boolean,
    class: String,
});

const emit = defineEmits(['update:modelValue']);

const inputRef = ref(null);
const formatNumber = (n) => {
    if (n === '' || n === null || n === undefined) return '';
    // Handle decimal strings from DB (e.g. "10000.00")
    // We want to treat it as 10000, not 1000000
    let num = n;
    if (typeof n === 'string' && n.includes('.')) {
        num = Math.floor(parseFloat(n));
    }
    const stringNum = num.toString().replace(/\D/g, '');
    return new Intl.NumberFormat('id-ID').format(stringNum);
};

// Fungsi handle input user
const onInput = (event) => {
    let value = event.target.value;

    const numericValue = value.replace(/\D/g, '');

    const cursorPosition = event.target.selectionStart;
    const oldLength = value.length;
    
    const formatted = numericValue ? new Intl.NumberFormat('id-ID').format(numericValue) : '';
    event.target.value = formatted;

    emit('update:modelValue', numericValue === '' ? 0 : parseInt(numericValue));
};

</script>

<template>
    <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">Rp</span>
        <input
            ref="inputRef"
            type="text"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full pl-10 text-right font-mono font-bold"
            :class="props.class"
            :value="formatNumber(modelValue)"
            :disabled="disabled"
            :placeholder="placeholder"
            @input="onInput"
        />
    </div>
</template>