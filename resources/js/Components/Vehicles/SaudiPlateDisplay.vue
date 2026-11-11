<template>
    <div
        v-if="isValidPlate"
        class="inline-flex shrink-0 items-stretch bg-white rounded border-2 border-gray-700 shadow overflow-hidden select-none align-middle"
        :class="[sizeClass]"
    >
        <!-- KSA Badge -->
        <div
            class="bg-gradient-to-b from-gray-100 to-gray-200 border-e border-gray-700 flex flex-col items-center justify-center shrink-0"
            :class="[badgeWidthClass]"
        >
            <svg
                class="text-green-700"
                :class="[iconSizeClass]"
                viewBox="0 0 24 24"
                fill="currentColor"
            >
                <path
                    d="M12 2L9 5h6l-3-3zm0 4c-1.1 0-2 .9-2 2v8h4V8c0-1.1-.9-2-2-2zm-4 12l-2 4h12l-2-4H8z"
                />
            </svg>
            <span class="font-bold text-gray-700 leading-none mt-0.5" :class="[ksaTextClass]">
                KSA
            </span>
        </div>
        <!-- Letters -->
        <div
            class="flex-1 min-w-0 flex flex-col justify-center items-center border-e border-gray-400 px-1.5 gap-0.5"
        >
            <div
                class="flex flex-row items-center justify-center whitespace-nowrap"
                :class="[letterGapClass]"
                dir="rtl"
            >
                <span
                    v-for="(char, i) in lettersArArr"
                    :key="'ar-l-' + i"
                    class="font-bold text-gray-800 leading-[1.2] shrink-0 whitespace-nowrap"
                    :class="[lettersArClass]"
                >
                    {{ char }}
                </span>
            </div>
            <div
                class="flex flex-row items-center justify-center whitespace-nowrap"
                :class="[letterGapClass]"
                dir="ltr"
            >
                <span
                    v-for="(char, i) in lettersEnArr"
                    :key="'en-l-' + i"
                    class="text-gray-500 leading-none shrink-0 whitespace-nowrap"
                    :class="[lettersEnClass]"
                >
                    {{ char }}
                </span>
            </div>
        </div>
        <!-- Numbers -->
        <div class="flex-1 min-w-0 flex flex-col justify-center items-center px-1.5 gap-0.5">
            <div
                class="flex flex-row items-center justify-center gap-0.5 whitespace-nowrap"
                dir="ltr"
            >
                <span
                    v-for="(char, i) in numbersArr"
                    :key="'ar-n-' + i"
                    class="font-bold text-gray-800 leading-[1.2] shrink-0"
                    :class="[lettersArClass]"
                >
                    {{ getArabicNumeral(char) }}
                </span>
            </div>
            <div
                class="flex flex-row items-center justify-center gap-0.5 whitespace-nowrap"
                dir="ltr"
            >
                <span
                    v-for="(char, i) in numbersArr"
                    :key="'en-n-' + i"
                    class="text-gray-500 leading-none shrink-0"
                    :class="[lettersEnClass]"
                >
                    {{ char }}
                </span>
            </div>
        </div>
    </div>
    <!-- Fallback if not structured -->
    <span
        v-else
        class="px-2.5 py-1 text-sm font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg inline-block whitespace-nowrap"
        dir="ltr"
    >
        {{ plateNumber || '-' }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    plateNumber: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md', // xs, sm, md, lg
    },
});

// Saudi plate valid letters mapping
const plateLettersMap = {
    A: 'أ',
    B: 'ب',
    J: 'ح',
    D: 'د',
    R: 'ر',
    S: 'س',
    X: 'ص',
    T: 'ط',
    E: 'ع',
    G: 'ق',
    K: 'ك',
    L: 'ل',
    Z: 'م',
    N: 'ن',
    H: 'هـ',
    U: 'و',
    V: 'ي',
};

const englishToArabicNums = {
    0: '٠',
    1: '١',
    2: '٢',
    3: '٣',
    4: '٤',
    5: '٥',
    6: '٦',
    7: '٧',
    8: '٨',
    9: '٩',
};

const getArabicNumeral = (num) => englishToArabicNums[num] || num;

const parsed = computed(() => {
    if (!props.plateNumber) return null;
    const match = props.plateNumber.trim().match(/^([a-zA-Z](?:\s*[a-zA-Z]){0,2})\s*(\d{1,4})$/);
    if (match) {
        return {
            letters: match[1].replace(/\s+/g, '').toUpperCase(),
            numbers: match[2],
        };
    }
    return null;
});

const isValidPlate = computed(() => !!parsed.value);

const lettersEnArr = computed(() => {
    if (!parsed.value) return [];
    return parsed.value.letters.split('');
});

const lettersArArr = computed(() => {
    if (!parsed.value) return [];
    return parsed.value.letters.split('').map((l) => plateLettersMap[l] || l);
});

const numbersArr = computed(() => {
    if (!parsed.value) return [];
    return parsed.value.numbers.split('');
});

// Sizing classes (min-w + fit-content prevents clipping or wrapping even with wide Arabic glyphs like هـ)
const sizeClass = computed(() => {
    if (props.size === 'xs') return 'min-w-[136px] h-[36px]';
    if (props.size === 'sm') return 'min-w-[158px] h-[40px]';
    if (props.size === 'lg') return 'min-w-[224px] h-[54px]';
    return 'min-w-[182px] h-[44px]'; // md
});

const badgeWidthClass = computed(() => {
    if (props.size === 'xs') return 'w-6';
    if (props.size === 'sm') return 'w-7';
    if (props.size === 'lg') return 'w-10';
    return 'w-8';
});

const iconSizeClass = computed(() => {
    if (props.size === 'xs') return 'w-2 h-2';
    if (props.size === 'sm') return 'w-2.5 h-2.5';
    if (props.size === 'lg') return 'w-4 h-4';
    return 'w-3 h-3';
});

const ksaTextClass = computed(() => {
    if (props.size === 'xs') return 'text-[5px]';
    if (props.size === 'sm') return 'text-[5px]';
    if (props.size === 'lg') return 'text-[7px]';
    return 'text-[6px]';
});

const letterGapClass = computed(() => {
    if (props.size === 'xs' || props.size === 'sm') return 'gap-1';
    if (props.size === 'lg') return 'gap-1.5';
    return 'gap-1';
});

const lettersArClass = computed(() => {
    if (props.size === 'xs') return 'text-[10px]';
    if (props.size === 'sm') return 'text-[11px]';
    if (props.size === 'lg') return 'text-base';
    return 'text-[13px]';
});

const lettersEnClass = computed(() => {
    if (props.size === 'xs') return 'text-[7px]';
    if (props.size === 'sm') return 'text-[8px]';
    if (props.size === 'lg') return 'text-[11px]';
    return 'text-[9px]';
});
</script>
