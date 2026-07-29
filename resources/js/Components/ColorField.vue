<template>
    <div>
        <label class="block text-sm font-medium mb-1">{{ label }}</label>
        <div class="flex items-center gap-2">
            <input
                type="color"
                :value="modelValue"
                @input="emit('update:modelValue', $event.target.value)"
                class="w-12 h-10 rounded-lg border border-slate-300 dark:border-slate-600 cursor-pointer"
            />
            <input
                type="text"
                :value="modelValue"
                @input="onTextInput"
                maxlength="9"
                pattern="^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$"
                class="flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 font-mono text-sm"
                dir="ltr"
            />
        </div>
        <p v-if="error" class="text-rose-500 text-sm mt-1">{{ error }}</p>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: { type: String, required: true },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

// The native <input type="color"> always emits a 6-digit hex
// value, so the text input only needs to validate that the
// user typed something matching the hex pattern before we
// propagate the change. We don't try to expand "#abc" to
// "#aabbcc" because that would surprise the user — if they
// type #abc, they meant #abc.
function onTextInput(e) {
    const value = e.target.value;
    if (/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(value) || value === '') {
        emit('update:modelValue', value);
    }
}
</script>
