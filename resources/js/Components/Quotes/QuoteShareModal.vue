<template>
    <BaseModal :show="show" @close="$emit('close')" size="md">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6a3 3 0 100 2.684m0-2.684l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $t('quotes.share.title') }}</h3>
            </div>
        </template>

        <div class="space-y-6 py-2">
            <!-- Quote Preview Card -->
            <div class="p-4 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl text-white shadow-lg shadow-indigo-200 dark:shadow-none">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-indigo-100 text-xs font-bold uppercase tracking-wider">{{ $t('quotes.columns.code') }}</p>
                        <h4 class="text-xl font-black font-mono tracking-tighter">{{ quote.code }}</h4>
                    </div>
                    <div class="bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-white">
                        {{ $t(`quotes.status.${quote.status}`) }}
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-indigo-100 text-xs font-bold uppercase tracking-wider">{{ $t('common.customer') }}</p>
                        <p class="font-bold text-lg leading-tight">{{ quote.customer?.name || $t('common.na') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-indigo-100 text-xs font-bold uppercase tracking-wider">{{ $t('quotes.show.total') }}</p>
                        <p class="text-2xl font-black font-mono tracking-tighter">{{ formatCurrency(quote.total) }}</p>
                    </div>
                </div>
            </div>

            <!-- Share Options Grid -->
            <div class="grid grid-cols-1 gap-3">
                <!-- WhatsApp Option -->
                <button @click="shareViaWhatsApp"
                    class="group flex items-center justify-between p-4 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/10 dark:hover:bg-emerald-900/20 rounded-2xl transition-all border border-emerald-100 dark:border-emerald-800/50">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-500 rounded-xl text-white shadow-lg shadow-emerald-500/20 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.888-9.885 9.888m8.328-18.382A11.772 11.772 0 0012.057 0C5.454 0 .07 5.385.067 11.99a11.75 11.75 0 001.58 5.877l-1.647 6.02L6.18 22.25a11.708 11.708 0 005.875 1.579h.005c6.603 0 11.988-5.386 11.99-11.99a11.714 11.714 0 00-3.418-8.318"/>
                            </svg>
                        </div>
                        <div class="text-start">
                            <p class="text-sm font-black text-emerald-900 dark:text-emerald-100 uppercase tracking-tight">{{ $t('quotes.share.whatsapp') }}</p>
                            <p class="text-xs font-bold text-emerald-600/70 dark:text-emerald-400/70">{{ $t('quotes.share.direct_message') }}</p>
                        </div>
                    </div>
                    <div class="text-emerald-400 group-hover:translate-x-1 group-hover:rtl:-translate-x-1 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </button>

                <!-- Copy Link Option -->
                <button @click="copyToClipboard"
                    class="group flex items-center justify-between p-4 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/10 dark:hover:bg-indigo-900/20 rounded-2xl transition-all border border-indigo-100 dark:border-indigo-800/50">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-indigo-600 rounded-xl text-white shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-transform">
                            <svg v-if="!copied" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 00-2 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div class="text-start">
                            <p class="text-sm font-black text-indigo-900 dark:text-indigo-100 uppercase tracking-tight">{{ $t('quotes.share.copy_link') }}</p>
                            <p class="text-xs font-bold text-indigo-600/70 dark:text-indigo-400/70 truncate max-w-[200px]">{{ currentUrl }}</p>
                        </div>
                    </div>
                    <div v-if="copied" class="text-emerald-500 font-black text-xs uppercase animate-pulse">{{ $t('common.copied') }}</div>
                </button>

                <!-- More Options -->
                <button v-if="canNativeShare" @click="triggerNativeShare"
                    class="group flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 dark:bg-gray-900/40 dark:hover:bg-gray-900/60 rounded-2xl transition-all border border-gray-100 dark:border-gray-800/50">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gray-600 rounded-xl text-white shadow-lg shadow-gray-500/20 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                            </svg>
                        </div>
                        <div class="text-start">
                            <p class="text-sm font-black text-gray-900 dark:text-gray-100 uppercase tracking-tight">{{ $t('quotes.share.more_options') }}</p>
                            <p class="text-xs font-bold text-gray-500/70 dark:text-gray-400/70">{{ $t('quotes.share.system_menu') }}</p>
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <template #footer>
            <div class="flex w-full gap-3">
                <button @click="$emit('print')"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-black uppercase tracking-widest transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    {{ $t('common.print') }}
                </button>
                <button @click="$emit('close')"
                    class="flex-1 px-4 py-3 bg-gray-900 dark:bg-black hover:bg-gray-800 text-white rounded-xl text-sm font-black uppercase tracking-widest transition-all">
                    {{ $t('common.close') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
    show: Boolean,
    quote: Object,
});

const emit = defineEmits(['close', 'print']);

const { t } = useI18n();
const toast = useToast();
const { getName } = useLocalized();
const { formatCurrency } = useNumberFormat();

const copied = ref(false);
const canNativeShare = computed(() => !!navigator.share);

// Build the public share URL using the quote UUID
const currentUrl = computed(() => {
    const baseUrl = window.location.origin;
    return props.quote?.uuid ? `${baseUrl}/view/quote/${props.quote.uuid}` : window.location.href;
});

// Get center display name (Arabic first, then English, then base name)
const centerDisplayName = computed(() => {
    const center = props.quote?.center;
    if (!center) return t('common.our_center');
    return center.name_ar || center.name_en || center.name || t('common.our_center');
});

function copyToClipboard() {
    const url = currentUrl.value;
    
    // Modern API
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(() => {
            handleCopySuccess();
        }).catch(() => {
            fallbackCopy(url);
        });
    } else {
        // Fallback
        fallbackCopy(url);
    }
}

function fallbackCopy(text) {
    try {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        
        // Ensure it's not visible or scrollable
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        textArea.style.top = "0";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        const successful = document.execCommand('copy');
        document.body.removeChild(textArea);
        
        if (successful) {
            handleCopySuccess();
        }
    } catch (err) {
        console.error('Fallback copy failed', err);
    }
}

function handleCopySuccess() {
    copied.value = true;
    toast.success(t('common.link_copied'));
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

function shareViaWhatsApp() {
    const customerPhone = props.quote.customer?.phone || '';
    const message = t('quotes.share.whatsapp_message', {
        code: props.quote.code,
        center: centerDisplayName.value,
        url: currentUrl.value
    });
    
    const whatsappUrl = `https://wa.me/${customerPhone.replace(/[\s\+]/g, '')}?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

function triggerNativeShare() {
    if (navigator.share) {
        navigator.share({
            title: t('quotes.share_title', { code: props.quote.code, center: centerDisplayName.value }),
            text: t('quotes.share.system_share_text', { center: centerDisplayName.value }),
            url: currentUrl.value
        }).catch(err => {
            console.error('Share failed:', err);
        });
    }
}
</script>
