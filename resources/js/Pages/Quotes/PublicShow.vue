<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/50 dark:from-gray-900 dark:via-gray-900 dark:to-slate-900" dir="rtl">

        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.success" class="fixed top-4 inset-x-4 z-50 flex justify-center">
            <div class="bg-emerald-500 text-white px-6 py-3 rounded-2xl shadow-xl flex items-center gap-3 max-w-md">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="font-bold text-sm">{{ $page.props.flash.success }}</span>
            </div>
        </div>

        <!-- Header -->
        <header class="bg-white/80 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-100 dark:border-gray-800 sticky top-0 z-40">
            <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
                <!-- Center Logo & Name -->
                <div class="flex items-center gap-3">
                    <img v-if="quote.center?.logo_light_url" :src="quote.center.logo_light_url" alt="logo"
                        class="h-10 w-auto object-contain" />
                    <div v-else class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h1 class="font-black text-gray-900 dark:text-white text-base leading-tight">{{ centerName }}</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('quotes.public.quote_offer') }}</p>
                    </div>
                </div>
                <!-- Quote Code Badge -->
                <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800/50 px-3 py-1.5 rounded-xl">
                    <p class="font-black text-indigo-700 dark:text-indigo-300 text-sm tracking-widest font-mono">{{ quote.code }}</p>
                </div>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 py-8 space-y-6">

            <!-- Status Banner -->
            <div :class="statusBannerClass" class="rounded-2xl px-5 py-4 flex items-center gap-4">
                <div :class="statusIconClass" class="p-2.5 rounded-xl flex-shrink-0">
                    <!-- Draft/Sent -->
                    <svg v-if="['draft','sent'].includes(quote.status)" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <!-- Approved -->
                    <svg v-else-if="quote.status === 'approved'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <!-- Rejected -->
                    <svg v-else-if="quote.status === 'rejected'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <!-- Converted -->
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div class="flex-1">
                    <p class="font-black text-sm">{{ statusText }}</p>
                    <p v-if="quote.status === 'rejected' && quote.rejection_reason" class="text-xs opacity-75 mt-0.5">{{ t('quotes.public.rejection_reason') }}: {{ quote.rejection_reason }}</p>
                    <p v-else class="text-xs opacity-75 mt-0.5">{{ statusSubtext }}</p>
                </div>
            </div>

            <!-- Vehicle & Customer Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/50">
                    <h2 class="font-black text-gray-900 dark:text-white text-sm uppercase tracking-wider">{{ t('quotes.public.vehicle_info') }}</h2>
                </div>
                <div class="p-5 grid grid-cols-2 gap-4">
                    <div v-if="quote.vehicle">
                        <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ t('common.vehicle') }}</p>
                        <p class="font-bold text-gray-900 dark:text-white text-sm">{{ quote.vehicle.make }} {{ quote.vehicle.model }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ quote.vehicle.year }}</p>
                    </div>
                    <div v-if="quote.vehicle?.plate_number">
                        <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ t('vehicles.columns.plate_number') }}</p>
                        <p class="font-black text-gray-900 dark:text-white text-sm font-mono tracking-widest">{{ quote.vehicle.plate_number }}</p>
                    </div>
                    <div v-if="quote.customer">
                        <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ t('common.customer') }}</p>
                        <p class="font-bold text-gray-900 dark:text-white text-sm">{{ quote.customer.name }}</p>
                    </div>
                    <div v-if="quote.odometer">
                        <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">{{ t('work_orders.form.odometer') }}</p>
                        <p class="font-bold text-gray-900 dark:text-white text-sm">{{ Number(quote.odometer).toLocaleString('ar-SA') }} كم</p>
                    </div>
                </div>
            </div>

            <!-- Services -->
            <div v-if="quote.lines?.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/50">
                    <h2 class="font-black text-gray-900 dark:text-white text-sm uppercase tracking-wider">{{ t('quotes.public.services') }}</h2>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    <div v-for="line in quote.lines" :key="line.id" class="px-5 py-4 flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 dark:text-white text-sm">{{ line.title }}</p>
                            <p v-if="line.description" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ line.description }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ t('quotes.public.qty') }}: {{ line.qty }} × {{ formatAmount(line.unit_price) }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p v-if="line.discount_amount > 0" class="text-xs text-gray-400 line-through">{{ formatAmount(line.qty * line.unit_price) }}</p>
                            <p class="font-black text-gray-900 dark:text-white text-sm">{{ formatAmount(line.line_total) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parts -->
            <div v-if="quote.parts?.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/50">
                    <h2 class="font-black text-gray-900 dark:text-white text-sm uppercase tracking-wider">{{ t('quotes.public.parts') }}</h2>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    <div v-for="part in quote.parts" :key="part.id" class="px-5 py-4 flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 dark:text-white text-sm">{{ part.name }}</p>
                            <p v-if="part.part_number" class="text-xs text-gray-400 font-mono mt-0.5">{{ part.part_number }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ t('quotes.public.qty') }}: {{ part.qty }} × {{ formatAmount(part.unit_price) }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="font-black text-gray-900 dark:text-white text-sm">{{ formatAmount(part.total_incl_tax || part.total) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price Summary -->
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl text-white shadow-xl shadow-indigo-200 dark:shadow-none overflow-hidden">
                <div class="p-5 space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-indigo-200">{{ t('quotes.show.subtotal') }}</span>
                        <span class="font-bold">{{ formatAmount(quote.subtotal) }}</span>
                    </div>
                    <div v-if="Number(quote.total_discount) > 0" class="flex justify-between items-center text-sm">
                        <span class="text-indigo-200">{{ t('quotes.show.discount') }}</span>
                        <span class="font-bold text-red-300">- {{ formatAmount(quote.total_discount) }}</span>
                    </div>
                    <div v-if="quote.tax_enabled_snapshot" class="flex justify-between items-center text-sm">
                        <span class="text-indigo-200">{{ t('quotes.show.vat') }} ({{ quote.tax_rate_snapshot }}%)</span>
                        <span class="font-bold">{{ formatAmount(quote.total_tax) }}</span>
                    </div>
                    <div class="border-t border-white/20 pt-3 mt-3 flex justify-between items-center">
                        <span class="text-indigo-100 font-bold uppercase tracking-wider text-sm">{{ t('quotes.show.total') }}</span>
                        <span class="text-2xl font-black tracking-tight">{{ formatAmount(quote.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Notes / Complaint -->
            <div v-if="quote.customer_complaint || quote.notes" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/50">
                    <h2 class="font-black text-gray-900 dark:text-white text-sm uppercase tracking-wider">{{ t('quotes.public.notes') }}</h2>
                </div>
                <div class="p-5 space-y-3">
                    <div v-if="quote.customer_complaint">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">{{ t('quotes.form.customer_complaint') }}</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ quote.customer_complaint }}</p>
                    </div>
                    <div v-if="quote.notes">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">{{ t('quotes.form.notes') }}</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ quote.notes }}</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons (only for draft/sent) -->
            <div v-if="quote.can_be_actioned" class="space-y-4">
                <p class="text-center text-sm font-bold text-gray-500 dark:text-gray-400">{{ t('quotes.public.action_prompt') }}</p>
                <div class="grid grid-cols-2 gap-4">
                    <button @click="handleApprove"
                        :disabled="submitting"
                        class="group flex items-center justify-center gap-2 py-4 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white font-black text-sm uppercase tracking-widest rounded-2xl shadow-lg shadow-emerald-200 dark:shadow-none transition-all active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ t('quotes.public.approve') }}
                    </button>
                    <button @click="showRejectModal = true"
                        :disabled="submitting"
                        class="group flex items-center justify-center gap-2 py-4 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white font-black text-sm uppercase tracking-widest rounded-2xl shadow-lg shadow-red-200 dark:shadow-none transition-all active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ t('quotes.public.reject') }}
                    </button>
                </div>
            </div>

            <!-- Contact Hub -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/50">
                    <h2 class="font-black text-gray-900 dark:text-white text-sm uppercase tracking-wider">{{ t('quotes.public.contact_us') }}</h2>
                </div>
                <div class="p-4 grid grid-cols-3 gap-3">
                    <!-- WhatsApp -->
                    <a v-if="quote.center?.phone" :href="`https://wa.me/${cleanPhone(quote.center.phone)}`" target="_blank"
                        class="flex flex-col items-center gap-2 p-3 bg-emerald-50 dark:bg-emerald-900/10 hover:bg-emerald-100 dark:hover:bg-emerald-900/20 rounded-xl transition-all active:scale-95 border border-emerald-100 dark:border-emerald-800/50">
                        <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center shadow-md shadow-emerald-200">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.888-9.885 9.888m8.328-18.382A11.772 11.772 0 0012.057 0C5.454 0 .07 5.385.067 11.99a11.75 11.75 0 001.58 5.877l-1.647 6.02L6.18 22.25a11.708 11.708 0 005.875 1.579h.005c6.603 0 11.988-5.386 11.99-11.99a11.714 11.714 0 00-3.418-8.318"/></svg>
                        </div>
                        <span class="text-xs font-black text-emerald-700 dark:text-emerald-300">واتساب</span>
                    </a>
                    <!-- Call -->
                    <a v-if="quote.center?.phone" :href="`tel:${quote.center.phone}`"
                        class="flex flex-col items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/10 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-xl transition-all active:scale-95 border border-blue-100 dark:border-blue-800/50">
                        <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center shadow-md shadow-blue-200">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <span class="text-xs font-black text-blue-700 dark:text-blue-300">{{ t('quotes.public.call') }}</span>
                    </a>
                    <!-- Print -->
                    <button @click="window.print()"
                        class="flex flex-col items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all active:scale-95 border border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 bg-gray-700 dark:bg-gray-600 rounded-xl flex items-center justify-center shadow-md shadow-gray-200 dark:shadow-none">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        </div>
                        <span class="text-xs font-black text-gray-700 dark:text-gray-300">{{ t('common.print') }}</span>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="pb-8 text-center">
                <p class="text-xs text-gray-400">{{ t('quotes.public.powered_by') }}</p>
            </div>
        </main>

        <!-- Rejection Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-end sm:items-center justify-center p-4" @click.self="showRejectModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-black text-gray-900 dark:text-white text-lg">{{ t('quotes.public.reject_title') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('quotes.public.reject_subtitle') }}</p>
                </div>
                <div class="p-6">
                    <textarea v-model="rejectionReason" rows="4"
                        :placeholder="t('quotes.public.rejection_reason_placeholder')"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"
                    ></textarea>
                    <div class="flex gap-3 mt-4">
                        <button @click="showRejectModal = false"
                            class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-black text-sm rounded-xl transition-all">
                            {{ t('common.cancel') }}
                        </button>
                        <button @click="handleReject"
                            :disabled="!rejectionReason.trim() || submitting"
                            class="flex-1 py-3 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white font-black text-sm rounded-xl transition-all">
                            {{ t('quotes.public.confirm_reject') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    quote: Object,
});

const { t } = useI18n();

const showRejectModal = ref(false);
const rejectionReason = ref('');
const submitting = ref(false);

// Center display name (Arabic first, then English)
const centerName = computed(() => {
    const c = props.quote?.center;
    if (!c) return '';
    return c.name_ar || c.name_en || c.name || '';
});

// Format currency amounts
function formatAmount(value) {
    const num = parseFloat(value || 0);
    const currency = props.quote?.currency_code || 'SAR';
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
    }).format(num);
}

function cleanPhone(phone) {
    return phone ? phone.replace(/[\s\+]/g, '') : '';
}

// Status display
const statusBannerClass = computed(() => ({
    'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 border border-amber-100 dark:border-amber-800/50': ['draft', 'sent'].includes(props.quote.status),
    'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800/50': props.quote.status === 'approved',
    'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border border-red-100 dark:border-red-800/50': props.quote.status === 'rejected',
    'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/50': props.quote.status === 'converted',
}));

const statusIconClass = computed(() => ({
    'bg-amber-100 dark:bg-amber-800/50': ['draft', 'sent'].includes(props.quote.status),
    'bg-emerald-100 dark:bg-emerald-800/50': props.quote.status === 'approved',
    'bg-red-100 dark:bg-red-800/50': props.quote.status === 'rejected',
    'bg-indigo-100 dark:bg-indigo-800/50': props.quote.status === 'converted',
}));

const statusText = computed(() => t(`quotes.public.status_text.${props.quote.status}`));
const statusSubtext = computed(() => t(`quotes.public.status_subtext.${props.quote.status}`));

// Actions
function handleApprove() {
    if (submitting.value) return;
    submitting.value = true;
    router.post(`/view/quote/${props.quote.uuid}/approve`, {}, {
        onFinish: () => { submitting.value = false; },
    });
}

function handleReject() {
    if (!rejectionReason.value.trim() || submitting.value) return;
    submitting.value = true;
    router.post(`/view/quote/${props.quote.uuid}/reject`, {
        rejection_reason: rejectionReason.value,
    }, {
        onFinish: () => {
            submitting.value = false;
            showRejectModal.value = false;
        },
    });
}
</script>

<style>
@media print {
    .print-hidden { display: none !important; }
    body { background: white !important; }
}
</style>
