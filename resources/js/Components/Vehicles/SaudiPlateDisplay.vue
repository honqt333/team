<template>
    <div v-if="isValidPlate" class="bg-white rounded border-2 border-gray-700 shadow flex overflow-hidden inline-flex" :class="[sizeClass]">
        <!-- KSA Badge -->
        <div class="bg-gradient-to-b from-gray-100 to-gray-200 border-e border-gray-700 flex flex-col items-center justify-center shrink-0" :class="[badgeWidthClass]">
            <svg class="text-green-700" :class="[iconSizeClass]" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L9 5h6l-3-3zm0 4c-1.1 0-2 .9-2 2v8h4V8c0-1.1-.9-2-2-2zm-4 12l-2 4h12l-2-4H8z"/>
            </svg>
            <span class="font-bold text-gray-700 leading-none mt-0.5" :class="[ksaTextClass]">KSA</span>
        </div>
        <!-- Letters -->
        <div class="flex-1 flex flex-col justify-center items-center border-e border-gray-400 px-1">
            <span class="font-bold text-gray-800 leading-none" :class="[lettersArClass]" dir="rtl">{{ lettersAr }}</span>
            <span class="text-gray-500 mt-0.5 leading-none" :class="[lettersEnClass]" dir="ltr">{{ lettersEn }}</span>
        </div>
        <!-- Numbers -->
        <div class="flex-1 flex flex-col justify-center items-center px-1">
            <div class="flex flex-row gap-0.5" dir="ltr">
                <span v-for="(char, i) in numbersArr" :key="i" class="font-bold text-gray-800 leading-none" :class="[lettersArClass]">{{ getArabicNumeral(char) }}</span>
            </div>
            <div class="flex flex-row gap-0.5 mt-0.5" dir="ltr">
                <span v-for="(char, i) in numbersArr" :key="i" class="text-gray-500 leading-none" :class="[lettersEnClass]">{{ char }}</span>
            </div>
        </div>
    </div>
    <!-- Fallback if not structured -->
    <span v-else class="px-2.5 py-1 text-sm font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg inline-block" dir="ltr">
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
        default: 'md', // sm, md, lg
    }
});

// Saudi plate valid letters mapping
const plateLettersMap = {
    'A': 'أ', 'B': 'ب', 'J': 'ح', 'D': 'د', 'R': 'ر', 'S': 'س',
    'X': 'ص', 'T': 'ط', 'E': 'ع', 'G': 'ق', 'K': 'ك', 'L': 'ل',
    'Z': 'م', 'N': 'ن', 'H': 'هـ', 'U': 'و', 'V': 'ي',
};

const englishToArabicNums = {
    '0': '٠', '1': '١', '2': '٢', '3': '٣', '4': '٤',
    '5': '٥', '6': '٦', '7': '٧', '8': '٨', '9': '٩'
};

const getArabicNumeral = (num) => englishToArabicNums[num] || num;

const parsed = computed(() => {
    if (!props.plateNumber) return null;
    const match = props.plateNumber.match(/^([a-zA-Z]{1,3})\s*(\d{1,4})$/);
    if (match) {
        return {
            letters: match[1].toUpperCase(),
            numbers: match[2]
        };
    }
    return null;
});

const isValidPlate = computed(() => !!parsed.value);

const lettersEn = computed(() => {
    if (!parsed.value) return '';
    return parsed.value.letters.split('').join(' ');
});

const lettersAr = computed(() => {
    if (!parsed.value) return '';
    return parsed.value.letters.split('').map(l => plateLettersMap[l] || l).join(' ');
});

const numbersArr = computed(() => {
    if (!parsed.value) return [];
    return parsed.value.numbers.split('');
});

// Sizing classes
const sizeClass = computed(() => {
    if (props.size === 'sm') return 'w-[140px] h-[34px]';
    if (props.size === 'lg') return 'w-[200px] h-[50px]';
    return 'w-[160px] h-[40px]'; // md
});

const badgeWidthClass = computed(() => {
    if (props.size === 'sm') return 'w-6';
    if (props.size === 'lg') return 'w-10';
    return 'w-8';
});

const iconSizeClass = computed(() => {
    if (props.size === 'sm') return 'w-2.5 h-2.5';
    if (props.size === 'lg') return 'w-4 h-4';
    return 'w-3 h-3';
});

const ksaTextClass = computed(() => {
    if (props.size === 'sm') return 'text-[5px]';
    if (props.size === 'lg') return 'text-[7px]';
    return 'text-[6px]';
});

const lettersArClass = computed(() => {
    if (props.size === 'sm') return 'text-xs';
    if (props.size === 'lg') return 'text-lg';
    return 'text-sm';
});

const lettersEnClass = computed(() => {
    if (props.size === 'sm') return 'text-[8px]';
    if (props.size === 'lg') return 'text-[11px]';
    return 'text-[9px]';
});
</script>
