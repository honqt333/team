<template>
    <AppLayout :title="$t('purchasing.grn.title')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <BackButton v-if="grn?.purchase_order_id" :href="route('app.purchasing.orders.show', grn.purchase_order_id)" />
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <span :class="statusBadgeClass">{{ $t(`purchasing.grn.statuses.${grn?.status}`) }}</span>
                            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight font-mono">{{ grn?.code }}</h1>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('purchasing.grn.received_date') }}: <span class="font-bold">{{ formatDate(grn?.received_date) }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="printGrn"
                        class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-white dark:hover:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 transition-all shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>

                    <button v-if="grn?.status === 'draft'" @click="postGrn"
                        class="flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:-translate-y-0.5 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>ترحيل السند</span>
                    </button>

                    <button v-if="grn?.status === 'draft'" @click="cancelGrn"
                        class="px-5 py-3 text-sm font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-2xl transition-all">
                        {{ $t('common.cancel') }}
                    </button>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Invoice Banner (Integrated) -->
                    <div v-if="grn?.purchase_invoice || grn?.status === 'posted'" 
                        class="p-5 rounded-3xl border transition-all"
                        :class="grn?.purchase_invoice ? 'bg-blue-50/50 border-blue-100 dark:bg-blue-900/20 dark:border-blue-800/30' : 'bg-amber-50/50 border-amber-100 dark:bg-amber-900/20 dark:border-amber-800/30'">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"
                                    :class="grn?.purchase_invoice ? 'bg-blue-600 text-white' : 'bg-amber-600 text-white'">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-0.5">{{ $t('invoices.title') }}</p>
                                    <h4 v-if="grn?.purchase_invoice" class="text-lg font-black font-mono">{{ grn.purchase_invoice.code }}</h4>
                                    <h4 v-else class="text-lg font-black">لم يتم إنشاء فاتورة بعد</h4>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Link v-if="grn?.purchase_invoice" :href="route('app.invoices.purchases.show', grn.purchase_invoice.id)"
                                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl shadow-lg shadow-blue-600/20 transition-all hover:-translate-y-0.5">
                                    {{ $t('common.view') }}
                                </Link>
                                <button v-else @click="createInvoice"
                                    class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-black rounded-xl shadow-lg shadow-amber-600/20 transition-all hover:-translate-y-0.5">
                                    إنشاء الفاتورة الآن
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                            <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-wider text-sm">{{ $t('purchasing.orders.items') }}</h3>
                            <span class="px-2.5 py-1 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs font-bold font-mono">
                                {{ toEnglish(grn.items?.length || 0) }}
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50/50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-6 py-3 text-start text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('inventory.parts.title') }}</th>
                                        <th class="px-6 py-3 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('common.quantity') }}</th>
                                        <th class="px-6 py-3 text-end text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('inventory.parts.unit_price') }}</th>
                                        <th class="px-6 py-3 text-end text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('common.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="item in grn.items" :key="item.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900 dark:text-white">{{ item.part?.name_ar || item.part?.name_en }}</div>
                                            <div class="text-xs text-gray-400 font-mono">{{ item.part?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center font-black font-mono text-gray-600 dark:text-gray-400">{{ toEnglish(item.qty_received) }}</td>
                                        <td class="px-6 py-4 text-end font-mono text-gray-600 dark:text-gray-400">{{ formatCurrency(item.unit_cost) }}</td>
                                        <td class="px-6 py-4 text-end font-black font-mono text-gray-900 dark:text-white">{{ formatCurrency(item.qty_received * item.unit_cost) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 bg-gray-50/30 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('common.total') }}</span>
                                <span class="text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">{{ formatCurrency(totalAmount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="grn?.notes" class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">{{ $t('common.notes') }}</h4>
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ grn?.notes }}</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Logistics Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 space-y-6">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ $t('inventory.warehouses.title') }}</label>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-700 flex items-center justify-center text-gray-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <span class="font-bold text-gray-900 dark:text-white">{{ grn.warehouse?.name }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ $t('purchasing.grn.for_po') }}</label>
                            <Link :href="route('app.purchasing.orders.show', grn.purchase_order_id)" 
                                class="flex items-center gap-3 group p-2 -m-2 rounded-2xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-black text-gray-900 dark:text-white font-mono group-hover:text-indigo-600 transition-colors">{{ grn.purchase_order?.code }}</div>
                                    <div class="text-[10px] text-gray-500 uppercase font-black">{{ grn.purchase_order?.supplier?.name }}</div>
                                </div>
                            </Link>
                        </div>

                        <div v-if="grn.delivery_note">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">{{ $t('purchasing.grn.delivery_note') }}</label>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700 font-mono font-bold text-gray-700 dark:text-gray-300">
                                {{ grn.delivery_note }}
                            </div>
                        </div>
                    </div>

                    <!-- Posting Info -->
                    <div v-if="grn.status === 'posted'" class="bg-emerald-50/50 dark:bg-emerald-900/10 rounded-3xl p-6 border border-emerald-100 dark:border-emerald-800/30">
                        <h4 class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-4">معلومات الترحيل</h4>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 flex items-center justify-center text-emerald-600 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="text-xs">
                                    <div class="text-emerald-900/50 dark:text-emerald-400/50 uppercase font-black text-[9px] mb-0.5">تاريخ الترحيل</div>
                                    <div class="font-bold text-emerald-900 dark:text-emerald-100">{{ formatDateTime(grn.posted_at) }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 flex items-center justify-center text-emerald-600 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div class="text-xs">
                                    <div class="text-emerald-900/50 dark:text-emerald-400/50 uppercase font-black text-[9px] mb-0.5">بواسطة</div>
                                    <div class="font-bold text-emerald-900 dark:text-emerald-100">{{ grn.posted_by_user?.name }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Version -->
        <Teleport to="body">
            <div class="print-section hidden p-8 bg-white text-black" id="print-area">
                <div class="flex justify-between border-b-2 pb-6 mb-8">
                    <div>
                        <h1 class="text-4xl font-black mb-2 uppercase">{{ $t('purchasing.grn.title') }}</h1>
                        <p class="text-xl font-mono">{{ grn.code }}</p>
                    </div>
                    <div class="text-end">
                        <div class="text-sm font-bold uppercase text-gray-500 mb-1">تاريخ الاستلام</div>
                        <div class="text-lg font-black font-mono">{{ formatDate(grn.received_date) }}</div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import AppLayout from '@/Layouts/AppLayout.vue';
import BackButton from '@/Components/BackButton.vue';

const props = defineProps({
    grn: Object,
});

const { formatCurrency, toEnglish } = useNumberFormat();
const { confirm } = useConfirm();
const { toast } = useToast();

const totalAmount = computed(() => {
    return props.grn.items.reduce((sum, item) => sum + (parseFloat(item.qty_received) * parseFloat(item.unit_cost)), 0);
});

const statusBadgeClass = computed(() => {
    const classes = {
        draft: 'bg-gray-100 text-gray-600 border-gray-200',
        posted: 'bg-emerald-50 text-emerald-700 border-emerald-100',
        cancelled: 'bg-red-50 text-red-700 border-red-100',
    };
    return `${classes[props.grn.status] || classes.draft} px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border`;
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
};

const postGrn = () => {
    confirm({
        title: 'ترحيل سند الاستلام',
        message: 'هل أنت متأكد من ترحيل هذا السند؟ سيتم تحديث المخزون فوراً وإنشاء الفاتورة.',
        type: 'success',
        onConfirm: () => {
            router.post(route('app.purchasing.grn.post', props.grn.id), {}, {
                onSuccess: () => toast({ message: 'تم ترحيل السند بنجاح', type: 'success' })
            });
        }
    });
};

const cancelGrn = () => {
    confirm({
        title: 'إلغاء سند الاستلام',
        message: 'هل أنت متأكد من إلغاء هذا السند؟',
        type: 'danger',
        onConfirm: () => {
            router.post(route('app.purchasing.grn.cancel', props.grn.id), {}, {
                onSuccess: () => toast({ message: 'تم إلغاء السند', type: 'info' })
            });
        }
    });
};

const createInvoice = () => {
    confirm({
        title: 'إنشاء فاتورة شراء',
        message: 'هل تريد إنشاء فاتورة شراء لهذا السند الآن؟',
        type: 'info',
        onConfirm: () => {
            router.post(route('app.purchasing.grn.create-invoice', props.grn.id), {}, {
                onSuccess: () => toast({ message: 'تم إنشاء الفاتورة بنجاح', type: 'success' })
            });
        }
    });
};

const printGrn = () => {
    window.print();
};
</script>

<style scoped>
@media print {
    @page { size: portrait; margin: 10mm; }
    body * { visibility: hidden; }
    .print-section, .print-section * { visibility: visible; }
    .print-section {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: block !important;
    }
}
</style>
