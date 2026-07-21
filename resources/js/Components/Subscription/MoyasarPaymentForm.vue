<template>
    <div class="space-y-4">
        <!-- Moyasar form mount point.
             CRITICAL: Must use class selector (.mysr-form) — Moyasar's init()
             replaces the matched element internally and an id selector
             doesn't resolve correctly. The element must be empty before
             init() is called. -->
        <div class="mysr-form min-h-[180px]"></div>

        <!-- Loading state -->
        <div v-if="loading" class="text-center py-4 text-sm text-gray-500">
            <svg class="animate-spin h-5 w-5 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>
            {{ locale === 'ar' ? 'جاري تحميل نموذج الدفع...' : 'Loading payment form...' }}
        </div>

        <!-- Error state -->
        <div
            v-if="error"
            class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg p-3 text-sm text-rose-700 dark:text-rose-300"
        >
            {{ error }}
        </div>

        <!-- Security note -->
        <div
            class="flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                />
            </svg>
            <span>
                {{ locale === 'ar' ? 'الدفع آمن ومشفر (PCI DSS)' : 'Secure PCI-DSS payment' }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    formData: { type: Object, required: true },
    onCompleted: { type: Function, default: null },
});
const emit = defineEmits(['completed', 'error']);

const locale = ref(document.documentElement.lang || 'ar');
const loading = ref(true);
const error = ref(null);
const moyasarScriptId = 'moyasar-script';
const moyasarCssId = 'moyasar-css';

onMounted(() => {
    loadMoyasar();
});

onBeforeUnmount(() => {
    // Don't remove the script — it's cached globally and other pages may use it.
    // Just clean up the form container.
    const el = document.querySelector('.mysr-form');
    if (el) el.innerHTML = '';
});

function loadMoyasar() {
    // If Moyasar is already loaded (cached), skip script load
    if (typeof window.Moyasar !== 'undefined') {
        initMoyasar();
        return;
    }

    // Load CSS (only once)
    if (!document.getElementById(moyasarCssId)) {
        const link = document.createElement('link');
        link.id = moyasarCssId;
        link.rel = 'stylesheet';
        link.href = 'https://cdn.moyasar.com/mpf/1.14.0/moyasar.css';
        document.head.appendChild(link);
    }

    // Load script (only once)
    if (document.getElementById(moyasarScriptId)) {
        // Already loading or loaded — wait for it
        const check = setInterval(() => {
            if (typeof window.Moyasar !== 'undefined') {
                clearInterval(check);
                initMoyasar();
            }
        }, 100);
        return;
    }

    const script = document.createElement('script');
    script.id = moyasarScriptId;
    script.src = 'https://cdn.moyasar.com/mpf/1.14.0/moyasar.js';
    script.onload = () => initMoyasar();
    script.onerror = () => {
        error.value =
            locale.value === 'ar'
                ? 'فشل تحميل نموذج الدفع. حاول مرة أخرى.'
                : 'Failed to load payment form. Please try again.';
        loading.value = false;
    };
    document.head.appendChild(script);
}

function initMoyasar() {
    if (typeof window.Moyasar === 'undefined') {
        error.value = 'Moyasar not loaded';
        loading.value = false;
        return;
    }

    try {
        window.Moyasar.init({
            element: '.mysr-form',
            amount: props.formData.amount,
            currency: props.formData.currency,
            description: props.formData.description,
            publishable_api_key: props.formData.publishable_key,
            callback_url: props.formData.callback_url,
            metadata: props.formData.metadata || {},
            // Only creditcard — Apple Pay requires a real validate_merchant_url
            // (https://api.moyasar.com/v1/applepay/initiate returns 365 bytes
            // of config-notice instead of a form when not configured for it).
            methods: ['creditcard'],
            on_completed: function (payment) {
                // Moyasar redirects to callback_url on success, so this
                // mainly fires for 3DS failures or cancelled flows.
                emit('completed', payment);
                if (typeof props.onCompleted === 'function') {
                    props.onCompleted(payment);
                }
            },
        });
        loading.value = false;
    } catch (e) {
        error.value = e.message;
        loading.value = false;
    }
}
</script>
