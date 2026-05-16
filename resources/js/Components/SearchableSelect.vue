<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    modelValue: [String, Number, null],
    options: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: null,
    },
    optionLabel: {
        type: [String, Function],
        default: 'name',
    },
    optionValue: {
        type: [String, Function],
        default: 'id',
    },
    searchPlaceholder: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    compact: {
        type: Boolean,
        default: false,
    },
    asyncSearch: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['update:modelValue', 'change', 'search']);
const { t } = useI18n();
const { toEnglish } = useNumberFormat();

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);
const inputRef = ref(null);
const highlightedIndex = ref(-1);
const optionsListRef = ref(null);

// Get the display label for an option
const getOptionLabel = (option) => {
    if (option === null || option === undefined) return '';
    if (typeof option === 'object') {
        if (typeof props.optionLabel === 'function') {
            return props.optionLabel(option);
        }
        return toEnglish(option[props.optionLabel]);
    }
    return toEnglish(String(option));
};

// Get the value for an option
const getOptionValue = (option) => {
    if (option === null || option === undefined) return null;
    if (typeof option === 'object') {
        if (typeof props.optionValue === 'function') {
            return props.optionValue(option);
        }
        return option[props.optionValue];
    }
    return option;
};

// Filtered options based on search
const filteredOptions = computed(() => {
    if (props.asyncSearch) return props.options; // Let parent handle filtering
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(option => {
        const label = String(getOptionLabel(option)).toLowerCase();
        return label.includes(query);
    });
});

// Emit search event for async mode
watch(searchQuery, (val) => {
    if (props.asyncSearch && isOpen.value) {
        emit('search', val);
    }
});

// Selected option object
const selectedOption = computed(() => {
    return props.options.find(opt => getOptionValue(opt) === props.modelValue);
});

// Watch filtering to reset highlight
watch(filteredOptions, () => {
    highlightedIndex.value = -1;
    // Automatically highlight first match if searching?
    if (searchQuery.value && filteredOptions.value.length > 0) {
        highlightedIndex.value = 0;
    }
});

const inputValue = computed({
    get() {
        if (isOpen.value) return searchQuery.value;
        return selectedOption.value ? getOptionLabel(selectedOption.value) : '';
    },
    set(val) {
        searchQuery.value = toEnglish(val);
        if (!isOpen.value && !props.asyncSearch) isOpen.value = true;
        if (props.asyncSearch && val.length > 0) isOpen.value = true;
    }
});

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const openDropdown = () => {
    if (props.disabled) return;
    isOpen.value = true;
    if (searchQuery.value === '') {
        if (props.asyncSearch) emit('search', '');
    } else {
        searchQuery.value = ''; 
    }
    highlightedIndex.value = -1;
    nextTick(() => {
        if(inputRef.value) inputRef.value.focus();
    });
};

const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
    highlightedIndex.value = -1;
};

const selectOption = (option) => {
    const value = getOptionValue(option);
    emit('update:modelValue', value);
    emit('change', value);
    closeDropdown();
    nextTick(() => {
        if (inputRef.value) inputRef.value.blur();
    });
};

const clearSelection = (e) => {
    e.stopPropagation(); 
    emit('update:modelValue', null);
    emit('change', null);
};

const handleInputClick = () => {
    if (!isOpen.value) {
        openDropdown();
    }
};

const handleKeydown = (e) => {
    if (!isOpen.value) {
        if (e.key === 'ArrowDown' || e.key === 'Enter') {
            e.preventDefault();
            openDropdown();
        }
        return;
    }

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (highlightedIndex.value < filteredOptions.value.length - 1) {
                highlightedIndex.value++;
                scrollToHighlighted();
            }
            break;
        case 'ArrowUp':
            e.preventDefault();
            if (highlightedIndex.value > 0) {
                highlightedIndex.value--;
                scrollToHighlighted();
            }
            break;
        case 'Enter':
            e.preventDefault();
            if (highlightedIndex.value >= 0 && filteredOptions.value[highlightedIndex.value]) {
                selectOption(filteredOptions.value[highlightedIndex.value]);
            } else if (filteredOptions.value.length > 0) {
                // Determine if we should select the first one if none highlighted? 
                // Let's stick to explicit highlight or auto-highlight on search (handled in watch)
                // If nothing highlighted but options exist, maybe select first? 
                // Currently watch filteredOptions sets index to 0 if searching.
            }
            break;
        case 'Escape':
            e.preventDefault();
            closeDropdown();
            break;
        case 'Tab':
             closeDropdown();
             break;
    }
};

const scrollToHighlighted = () => {
    nextTick(() => {
        const list = optionsListRef.value;
        if (!list) return;
        const item = list.children[highlightedIndex.value]; // Ensure structure matches
        if (item) {
            item.scrollIntoView({ block: 'nearest' });
        }
    });
};
</script>

<template>
    <div ref="containerRef" class="relative">
        <label v-if="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <div class="relative">
            <input
                ref="inputRef"
                v-model="inputValue"
                type="text"
                :placeholder="placeholder || t('common.select')"
                :disabled="disabled"
                class="w-full border rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                :class="[
                    compact ? 'px-2 py-1.5 text-xs' : 'px-4 py-2.5 text-sm',
                    error ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-violet-500',
                    { 'pr-10': selectedOption && !isOpen && !compact }
                ]"
                @click="handleInputClick"
                @focus="handleInputClick"
                @keydown="handleKeydown"
                autocomplete="off"
            />

            <!-- Icons -->
            <div class="absolute inset-y-0 end-0 flex items-center pe-3 pointer-events-none">
                 <!-- Arrow Icon -->
                <svg v-if="!selectedOption || isOpen" class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
             <!-- Clear Button (Only when selected and closed) -->
            <div v-if="selectedOption && !isOpen && !required" 
                class="absolute inset-y-0 end-0 flex items-center pe-3 cursor-pointer group"
                @click="clearSelection"
            >
                <svg class="w-4 h-4 text-gray-400 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
        </div>

        <!-- Dropdown -->
        <div 
            v-if="isOpen"
            class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-60 overflow-y-auto"
        >
            <ul v-if="filteredOptions.length > 0" ref="optionsListRef" class="py-1">
                <li 
                    v-for="(option, index) in filteredOptions" 
                    :key="getOptionValue(option)"
                    @click="selectOption(option)"
                    @mousemove="highlightedIndex = index"
                    class="px-4 py-2 text-sm cursor-pointer flex items-center justify-between"
                    :class="[
                        highlightedIndex === index 
                            ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300' 
                            : 'text-gray-700 dark:text-gray-300 hover:bg-violet-50 dark:hover:bg-violet-900/50 hover:text-violet-700 dark:hover:text-violet-300',
                        {'font-medium': getOptionValue(option) === modelValue}
                    ]"
                >
                    <slot name="option" :option="option">
                        <span>{{ getOptionLabel(option) }}</span>
                    </slot>
                    <svg v-if="getOptionValue(option) === modelValue" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </li>
            </ul>
            <div v-else-if="!asyncSearch || (asyncSearch && searchQuery.length >= 2)" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                {{ t('common.no_results') || 'No results found' }}
            </div>
            <div v-else class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                {{ t('inventory.parts.search') || 'Type to search...' }}
            </div>
        </div>
        
        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
    </div>
</template>
