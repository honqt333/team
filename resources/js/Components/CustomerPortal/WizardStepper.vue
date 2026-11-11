<template>
    <!--
        WizardStepper — a progress indicator for the booking
        and reschedule flows.

        The bar is informational only — it is not clickable,
        because each step needs the previous step's data to
        render the next one. The Next/Previous buttons are
        the only way to advance.

        The stepper honors the active direction: in RTL the
        order runs right-to-left, so the "1" pill is on the
        right and the "current" pill is positioned by flex
        order, not absolute coordinates.
    -->
    <nav
        class="mb-6 rounded-2xl border border-slate-200 bg-white p-2 text-xs shadow-sm sm:text-sm"
        :aria-label="ariaLabel"
    >
        <ol class="grid grid-cols-3 gap-1.5">
            <li
                v-for="(s, i) in steps"
                :key="s.key"
                class="inline-flex min-w-0 items-center justify-center gap-1.5 rounded-xl px-2 py-2.5 font-bold"
                :class="
                    i < currentStep
                        ? 'bg-emerald-50 text-emerald-700'
                        : i === currentStep
                          ? 'text-white'
                          : 'bg-slate-50 text-slate-400'
                "
                :style="
                    i === currentStep ? { backgroundColor: 'var(--tenant-primary, #2563eb)' } : {}
                "
                :aria-current="i === currentStep ? 'step' : undefined"
            >
                <span class="text-[10px]">{{ i + 1 }}</span>
                <span class="truncate">{{ s.label }}</span>
            </li>
        </ol>
        <span v-if="showStepCounter" class="sr-only">{{ stepCounterText }}</span>
    </nav>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    steps: { type: Array, required: true },
    currentStep: { type: Number, required: true },
    ariaLabel: { type: String, default: 'progress' },
    // "Step 2 of 3" style counter, in the user's locale.
    stepCounter: { type: String, default: '' },
    showStepCounter: { type: Boolean, default: true },
});

const stepCounterText = computed(() => props.stepCounter);
</script>
