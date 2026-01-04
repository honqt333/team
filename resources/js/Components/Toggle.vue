<script setup>
import { computed } from 'vue';

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        default: false,
    },
    value: {
        type: [String, Number, Boolean],
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:checked']);

const proxyChecked = computed({
    get() {
        return props.checked;
    },
    set(val) {
        emit('update:checked', val);
    },
});

const isChecked = computed(() => {
    if (Array.isArray(proxyChecked.value)) {
        return proxyChecked.value.includes(props.value);
    }
    return proxyChecked.value;
});

const handleChange = () => {
    if (props.disabled) return;
    
    if (Array.isArray(proxyChecked.value)) {
        let newValue = [...proxyChecked.value];
        if (isChecked.value) {
            newValue = newValue.filter(v => v !== props.value);
        } else {
            newValue.push(props.value);
        }
        proxyChecked.value = newValue;
    } else {
        proxyChecked.value = !proxyChecked.value;
    }
};
</script>

<template>
    <button 
        type="button" 
        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-violet-600 focus:ring-offset-2"
        :class="[
            isChecked ? 'bg-violet-600' : 'bg-gray-200 dark:bg-gray-700', 
            disabled ? 'opacity-50 cursor-not-allowed' : ''
        ]"
        role="switch"
        :aria-checked="isChecked"
        @click="handleChange"
    >
        <span 
            aria-hidden="true" 
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
            :class="[isChecked ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0']"
        ></span>
    </button>
</template>
