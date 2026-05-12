<template>
    <div class="space-y-3">
        <!-- Country & Skip Format Row -->
        <div class="flex items-center gap-4 flex-wrap">
            <!-- Country Selector -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $t('vehicles.plate.country') }}</span>
                <SearchableSelect
                    v-model="selectedCountry"
                    :options="countryOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="{{ $t('vehicles.plate.country') }}"
                    class="min-w-[150px]"
                />
            </div>

            <!-- Skip Format Checkbox -->
            <label v-if="selectedCountry === 'SA'" class="flex items-center gap-2 cursor-pointer select-none">
                <input 
                    type="checkbox" 
                    v-model="skipFormat"
                    class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500 cursor-pointer"
                />
                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $t('vehicles.plate.skip_format') }}</span>
            </label>
        </div>

        <!-- Saudi Structured Input (when SA selected and not skipped) -->
        <div v-if="selectedCountry === 'SA' && !skipFormat" class="space-y-3">
            <!-- Compact Plate Preview -->
            <div class="flex justify-center">
                <div class="bg-white rounded border-2 border-gray-700 shadow flex overflow-hidden" style="width: 240px; height: 50px;">
                    <!-- KSA Badge -->
                    <div class="w-10 bg-gradient-to-b from-gray-100 to-gray-200 border-e border-gray-700 flex flex-col items-center justify-center">
                        <svg class="w-4 h-4 text-green-700" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L9 5h6l-3-3zm0 4c-1.1 0-2 .9-2 2v8h4V8c0-1.1-.9-2-2-2zm-4 12l-2 4h12l-2-4H8z"/>
                        </svg>
                        <span class="text-[6px] font-bold text-gray-700">KSA</span>
                    </div>
                    <!-- Letters -->
                    <div class="flex-1 flex flex-col justify-center items-center border-e border-gray-400 px-1">
                        <span class="text-sm font-bold text-gray-800" dir="rtl">{{ displayLettersAr }}</span>
                        <span class="text-[9px] text-gray-500" dir="ltr">{{ displayLettersEn }}</span>
                    </div>
                    <!-- Numbers -->
                    <div class="flex-1 flex flex-col justify-center items-center px-1">
                        <div class="flex flex-row gap-0.5" dir="ltr">
                            <span v-for="(char, i) in displayNumbersEn.split(' ')" :key="i" class="text-sm font-bold text-gray-800">{{ char }}</span>
                        </div>
                        <div class="flex flex-row gap-0.5" dir="ltr">
                            <span v-for="(char, i) in displayNumbersEn.split(' ')" :key="i" class="text-[9px] text-gray-500">{{ char }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compact Selectors Row -->
            <div class="grid grid-cols-2 gap-2">
                <!-- Letters Input -->
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                        {{ $t('vehicles.plate.letters') }} <span class="text-red-500">*</span> (3)
                    </label>
                    <div class="flex gap-1">
                        <div class="flex-1">
                            <SearchableSelect
                                v-model="letter1"
                                :options="plateLetters"
                                :option-label="l => `${l.ar} - ${l.en}`"
                                option-value="en"
                                :placeholder="'-'"
                                :label="''"
                                :compact="true"
                                :error="error && !letter1"
                            />
                        </div>
                        <div class="flex-1">
                            <SearchableSelect
                                v-model="letter2"
                                :options="plateLetters"
                                :option-label="l => `${l.ar} - ${l.en}`"
                                option-value="en"
                                :placeholder="'-'"
                                :label="''"
                                :compact="true"
                                :error="error && !letter2"
                            />
                        </div>
                        <div class="flex-1">
                            <SearchableSelect
                                v-model="letter3"
                                :options="plateLetters"
                                :option-label="l => `${l.ar} - ${l.en}`"
                                option-value="en"
                                :placeholder="'-'"
                                :label="''"
                                :compact="true"
                                :error="error && !letter3"
                            />
                        </div>
                    </div>
                </div>

                <!-- Numbers Input -->
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                        {{ $t('vehicles.plate.numbers') }} <span class="text-red-500">*</span> (1-4)
                    </label>
                    <input 
                        type="text"
                        :value="plateNumbers"
                        maxlength="4"
                        inputmode="numeric"
                        placeholder="1234"
                        dir="ltr"
                        class="w-full px-3 py-1.5 text-sm text-center border rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-1 focus:ring-teal-500"
                        :class="error && !plateNumbers ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600'"
                        @input="onNumbersInput($event)"
                    />
                </div>
            </div>
        </div>

        <!-- Free Text Input (when skipped or other country) -->
        <div v-else>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                {{ $t('vehicles.plate.free_input') }} <span class="text-red-500">*</span>
            </label>
            <input 
                type="text"
                :value="freeTextPlate"
                :placeholder="$t('vehicles.form.plate_placeholder')"
                dir="ltr"
                class="w-full px-3 py-2 text-center font-semibold border rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-teal-500"
                :class="error && !freeTextPlate ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600'"
                @input="onFreeTextInput($event)"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    error: {
        type: [String, Boolean],
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);
const { toEnglish } = useNumberFormat();

// Saudi plate valid letters
const plateLetters = [
    { ar: 'أ', en: 'A' }, { ar: 'ب', en: 'B' }, { ar: 'ح', en: 'J' }, { ar: 'د', en: 'D' }, { ar: 'ر', en: 'R' }, { ar: 'س', en: 'S' },
    { ar: 'ص', en: 'X' }, { ar: 'ط', en: 'T' }, { ar: 'ع', en: 'E' }, { ar: 'ق', en: 'G' }, { ar: 'ك', en: 'K' }, { ar: 'ل', en: 'L' },
    { ar: 'م', en: 'Z' }, { ar: 'ن', en: 'N' }, { ar: 'هـ', en: 'H' }, { ar: 'و', en: 'U' }, { ar: 'ي', en: 'V' },
];

const arabicNums = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

const countryOptions = [
    { value: 'SA', label: '🇸🇦 ' + 'Saudi Arabia' },
    { value: 'OTHER', label: '🌍 ' + 'Other' },
];

const selectedCountry = ref('SA');
const skipFormat = ref(false);
const letter1 = ref('');
const letter2 = ref('');
const letter3 = ref('');
const plateNumbers = ref('');
const freeTextPlate = ref('');

// Flag to prevent re-parsing when we emit our own changes
let isInternalUpdate = false;
let initialized = false;

// Digit conversion helper (always English now)
function toEnglishNum(num) {
    return toEnglish(String(num));
}

function getLetterAr(en) {
    const found = plateLetters.find(l => l.en === en);
    return found ? found.ar : '-';
}

// Display values
const displayLettersAr = computed(() => {
    const l1 = letter1.value ? getLetterAr(letter1.value) : '-';
    const l2 = letter2.value ? getLetterAr(letter2.value) : '-';
    const l3 = letter3.value ? getLetterAr(letter3.value) : '-';
    return `${l1} ${l2} ${l3}`;
});

const displayLettersEn = computed(() => {
    const l1 = letter1.value || '-';
    const l2 = letter2.value || '-';
    const l3 = letter3.value || '-';
    return `${l1} ${l2} ${l3}`;
});

const displayNumbersAr = computed(() => {
    if (!plateNumbers.value) return '- - - -';
    return plateNumbers.value.split('').map(n => toEnglishNum(n)).join(' ');
});

const displayNumbersEn = computed(() => {
    if (!plateNumbers.value) return '- - - -';
    return plateNumbers.value.split('').join(' ');
});

// Generate plate string
const plateString = computed(() => {
    if (selectedCountry.value !== 'SA' || skipFormat.value) {
        return freeTextPlate.value;
    }
    const letters = [letter1.value, letter2.value, letter3.value].filter(l => l).join('');
    if (!letters && !plateNumbers.value) return '';
    return `${letters} ${plateNumbers.value}`.trim();
});

// Convert Arabic numerals to English and filter
function onNumbersInput(e) {
    let value = e.target.value;
    // Map Arabic numerals (٠١٢٣٤٥٦٧٨٩) to English (0123456789)
    const arabicToEnglish = {
        '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
        '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
    };
    value = value.split('').map(char => arabicToEnglish[char] || char).join('');
    // Only keep digits and limit to 4
    plateNumbers.value = value.replace(/[^0-9]/g, '').slice(0, 4);
}

// Convert Arabic numerals in free text input
function onFreeTextInput(e) {
    let value = e.target.value;
    // Map Arabic numerals (٠١٢٣٤٥٦٧٨٩) to English (0123456789)
    const arabicToEnglish = {
        '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
        '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
    };
    freeTextPlate.value = value.split('').map(char => arabicToEnglish[char] || char).join('');
}

// Validate the input
function validate() {
    if (selectedCountry.value === 'SA' && !skipFormat.value) {
        // In Saudi Mode, allow 1-4 digits for numbers (e.g. "1" is valid plate 1, "9999" is valid)
        // Letters MUST be 3.
        return !!(letter1.value && letter2.value && letter3.value && plateNumbers.value);
    }
    // In Free Text mode, just require something
    return !!freeTextPlate.value;
}

defineExpose({ validate });

// Emit changes when internal values change
watch(plateString, (newVal) => {
    isInternalUpdate = true;
    emit('update:modelValue', newVal);
    // Reset flag after next tick
    setTimeout(() => {
        isInternalUpdate = false;
    }, 0);
});

// Clear fields when switching format (only after initialization)
watch(skipFormat, (newVal, oldVal) => {
    if (initialized && newVal !== oldVal) {
        // Clear structured fields when switching to free text
        letter1.value = '';
        letter2.value = '';
        letter3.value = '';
        plateNumbers.value = '';
        // Only clear freeTextPlate when switching TO structured format (from free text)
        if (!newVal) {
            freeTextPlate.value = '';
        }
    }
});

// Clear fields when switching country (only after initialization)
watch(selectedCountry, (newVal, oldVal) => {
    if (initialized && newVal !== oldVal) {
        letter1.value = '';
        letter2.value = '';
        letter3.value = '';
        plateNumbers.value = '';
        freeTextPlate.value = '';
        skipFormat.value = false;
    }
});

// Parse plate value from string format
function parseInitialValue(val) {
    if (!val) return;
    
    // Prevent emitting during initial parse
    isInternalUpdate = true;
    
    // Try to parse as structured plate (letters + space + numbers)
    const match = val.match(/^([A-Z]{1,3})\s*(\d{1,4})$/);
    if (match) {
        const letters = match[1].split('');
        letter1.value = letters[0] || '';
        letter2.value = letters[1] || '';
        letter3.value = letters[2] || '';
        plateNumbers.value = match[2] || '';
        skipFormat.value = false;
    } else if (val) {
        // Not structured format, use free text
        // IMPORTANT: Set both values together to ensure plateString computes correctly
        skipFormat.value = true;
        freeTextPlate.value = val;
    }
    
    // Reset internal update flag after Vue completes all reactive updates
    nextTick(() => {
        isInternalUpdate = false;
    });
}

// Watch for external modelValue changes (handles edit mode initialization and form reset)
watch(() => props.modelValue, (newVal, oldVal) => {
    // Skip if this is our own internal update
    if (isInternalUpdate) return;
    
    // Reset if explicitly set to empty (form reset)
    if (newVal === '' && oldVal !== '' && oldVal !== undefined) {
        letter1.value = '';
        letter2.value = '';
        letter3.value = '';
        plateNumbers.value = '';
        freeTextPlate.value = '';
        skipFormat.value = false;
        initialized = false;
        return;
    }
    
    // Parse new value if:
    // 1. Not yet initialized, OR
    // 2. Value changed from parent (different vehicle being edited)
    if (newVal) {
        // Check if current state matches incoming value
        const currentPlateString = skipFormat.value ? freeTextPlate.value : 
            `${letter1.value}${letter2.value}${letter3.value} ${plateNumbers.value}`.trim();
        
        // If values are different, re-parse (new vehicle being edited)
        if (!initialized || (currentPlateString !== newVal && oldVal !== undefined)) {
            // Reset state first if re-initializing
            // Set initialized=false FIRST to prevent watchers from clearing values
            if (initialized) {
                initialized = false;
                letter1.value = '';
                letter2.value = '';
                letter3.value = '';
                plateNumbers.value = '';
                freeTextPlate.value = '';
                skipFormat.value = false;
            }
            parseInitialValue(newVal);
            initialized = true;
        }
    }
}, { immediate: true });
</script>
