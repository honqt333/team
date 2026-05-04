<template>
    <div class="space-y-6">
        <!-- Header with View Toggle -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-500 shadow-inner">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider">{{ $t('quotes.show.tabs.spare_parts') }}</h3>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">{{ toEnglish(parts.length) }} {{ $t('quotes.show.tabs.spare_parts') }}</p>
                </div>
            </div>

            <!-- View Toggle Buttons -->
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
                <button @click="viewMode = 'grid'" :class="[
                    'p-2 rounded-lg transition-all',
                    viewMode === 'grid'
                        ? 'bg-white dark:bg-gray-700 shadow-sm text-emerald-600 dark:text-emerald-400'
                        : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
                ]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                </button>
                <button @click="viewMode = 'table'" :class="[
                    'p-2 rounded-lg transition-all',
                    viewMode === 'table'
                        ? 'bg-white dark:bg-gray-700 shadow-sm text-emerald-600 dark:text-emerald-400'
                        : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'
                ]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" :class="[
            'grid gap-6',
            compactGrid ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
        ]">
            <div v-for="(part, index) in parts" :key="part.id"
                class="relative bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-500 group overflow-hidden">
                
                <!-- Source Color Strip -->
                <div :class="['absolute top-0 inset-x-0 h-1.5', getSourceColorClass(part.source, 'strip')]"></div>

                <!-- Card Content -->
                <div class="p-6 space-y-5">
                    <!-- Header -->
                    <div class="flex items-start justify-between">
                        <div class="space-y-1 max-w-[70%]">
                            <h4 class="font-black text-gray-900 dark:text-white leading-tight break-words" :title="part.name">
                                {{ part.name }}
                            </h4>
                            <p class="font-mono text-[10px] font-bold text-gray-400 tracking-tighter uppercase">
                                {{ part.part_number || part.sku || '---' }}
                            </p>
                        </div>
                        <span :class="['px-2.5 py-1 text-[10px] font-black rounded-lg uppercase tracking-wider', getSourceColorClass(part.source, 'badge')]">
                            {{ $t('quotes.parts.' + part.source) }}
                        </span>
                    </div>

                    <!-- Pricing Display (Simplified Grid 2x2) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $t('inventory.parts.unit_price') }}</p>
                            <p class="text-xs font-mono font-bold text-gray-900 dark:text-white" dir="ltr">{{ formatCurrency(part.unit_price) }}</p>
                        </div>
                        <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $t('quotes.show.discount') }}</p>
                            <p class="text-xs font-mono font-bold text-red-500" dir="ltr">- {{ formatCurrency(part.discount || 0) }}</p>
                        </div>
                        <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $t('work_orders.item.qty') }}</p>
                            <p class="text-xs font-mono font-black text-emerald-600 dark:text-emerald-400">{{ formatQuantity(part.qty) }} <span class="text-[10px]">{{ getUnitName(part) }}</span></p>
                        </div>
                        <div class="p-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $t('quotes.tax_summary.vat') }}</p>
                            <p class="text-xs font-mono font-bold text-blue-600 dark:text-blue-400" dir="ltr">+ {{ formatCurrency(part.tax_amount || 0) }}</p>
                        </div>
                    </div>

                    <!-- Linked Info -->
                    <div v-if="showService || isPending(part)" class="space-y-3 pt-1">
                        <div v-if="showService && (part.quote_line || part.service)" class="flex items-center gap-2 text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-900/10 p-2 rounded-lg">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            <span class="truncate">{{ part.quote_line?.description || getName(part.quote_line?.service) || getName(part.service) }}</span>
                        </div>
                        <div v-if="isPending(part)" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 text-[9px] font-black uppercase">
                            <span class="w-1 h-1 rounded-full bg-amber-500 animate-pulse"></span>
                            {{ $t('common.pending') }}
                        </div>
                    </div>
                </div>

                <!-- Card Footer (Total Bar) -->
                <div :class="['px-6 py-5 flex items-center justify-between border-t border-gray-100 dark:border-gray-700/50', getSourceColorClass(part.source, 'footer')]">
                    <div class="space-y-0.5">
                        <p class="text-[10px] font-black opacity-60 uppercase tracking-[0.2em]">{{ $t('quotes.show.total') }}</p>
                        <div v-if="part.tax_amount > 0" class="text-[9px] font-bold opacity-50 flex items-center gap-1">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                            {{ formatCurrency(part.tax_amount) }} {{ $t('quotes.tax_summary.vat') }}
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="text-2xl font-black font-mono tracking-tighter text-gray-900 dark:text-white" dir="ltr">
                            {{ formatCurrency(part.total_incl_tax || (Number(part.unit_price) * Number(part.qty) - Number(part.discount || 0) + Number(part.tax_amount || 0))) }}
                        </span>
                    </div>
                </div>

                <!-- Actions Overlay -->
                <div v-if="!readOnly" class="absolute top-12 right-2 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-4 group-hover:translate-x-0">
                    <button @click="$emit('edit', part)" 
                        :title="part.status === 'issued' ? $t('quotes.parts.issue_more') : $t('common.edit')"
                        class="p-2.5 bg-white dark:bg-gray-800 shadow-xl rounded-xl text-emerald-600 hover:scale-110 active:scale-95 transition-all">
                        <svg v-if="part.status === 'issued'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </button>
                    <button @click="$emit('delete', part)" 
                        :title="part.status === 'issued' ? $t('quotes.parts.return_to_warehouse') : $t('common.delete')"
                        class="p-2.5 bg-white dark:bg-gray-800 shadow-xl rounded-xl text-red-500 hover:scale-110 active:scale-95 transition-all">
                        <svg v-if="part.status === 'issued'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" /></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div v-else class="bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700/50">
                            <th class="px-6 py-4 text-start text-[10px] font-black text-gray-400 uppercase tracking-widest">#</th>
                            <th class="px-6 py-4 text-start text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('inventory.parts.name') }}</th>
                            <th class="px-6 py-4 text-start text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('quotes.parts.source') }}</th>
                            <th v-if="showService" class="px-6 py-4 text-start text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('quotes.parts_display.service') }}</th>
                            <th class="px-6 py-4 text-end text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('inventory.parts.unit_price') }}</th>
                            <th class="px-6 py-4 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('work_orders.item.qty') }}</th>
                            <th class="px-6 py-4 text-end text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('quotes.show.amount') }}</th>
                            <th v-if="showVat" class="px-6 py-4 text-end text-[10px] font-black text-gray-400 uppercase tracking-widest">VAT</th>
                            <th class="px-6 py-4 text-end text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ $t('quotes.show.total') }}</th>
                            <th v-if="!readOnly" class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                        <tr v-for="(part, index) in parts" :key="part.id" class="group hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4 text-xs font-bold text-gray-400">{{ toEnglish(index + 1) }}</td>
                            <td class="px-6 py-4">
                                <div class="space-y-0.5">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ part.name }}</p>
                                    <p class="font-mono text-[9px] text-gray-400 uppercase tracking-tighter">{{ part.part_number || part.sku }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['px-2 py-0.5 text-[9px] font-black rounded-md uppercase tracking-wider', getSourceColorClass(part.source, 'badge')]">
                                    {{ $t(`quotes.parts.${part.source}`) }}
                                </span>
                            </td>
                            <td v-if="showService" class="px-6 py-4">
                                <span class="text-[10px] font-bold text-gray-500 max-w-[120px] truncate block">
                                    {{ part.quote_line?.description || getName(part.quote_line?.service) || getName(part.service) || '---' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-end font-mono text-xs font-bold text-gray-600 dark:text-gray-400">{{ formatCurrency(part.unit_price) }}</td>
                            <td class="px-6 py-4 text-center font-mono text-xs font-black text-emerald-600 dark:text-emerald-400">{{ formatQuantity(part.qty) }}</td>
                            <td class="px-6 py-4 text-end font-mono text-xs font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency((part.unit_price * part.qty) - (part.discount || 0)) }}
                            </td>
                            <td v-if="showVat" class="px-6 py-4 text-end font-mono text-xs font-bold text-blue-600 dark:text-blue-400">
                                {{ formatCurrency(part.tax_amount || 0) }}
                            </td>
                            <td class="px-6 py-4 text-end font-mono text-sm font-black text-gray-900 dark:text-white">
                                {{ formatCurrency(part.total_incl_tax || (Number(part.unit_price) * Number(part.qty) - Number(part.discount || 0) + Number(part.tax_amount || 0))) }}
                            </td>
                            <td v-if="!readOnly" class="px-6 py-4">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="$emit('edit', part)" 
                                        :title="part.status === 'issued' ? $t('quotes.parts.issue_more') : $t('common.edit')"
                                        class="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors">
                                        <svg v-if="part.status === 'issued'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button @click="$emit('delete', part)" 
                                        :title="part.status === 'issued' ? $t('quotes.parts.return_to_warehouse') : $t('common.delete')"
                                        class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                        <svg v-if="part.status === 'issued'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" /></svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="parts.length === 0" class="flex flex-col items-center justify-center py-10 px-4 bg-gray-50/50 dark:bg-gray-800/30 rounded-[2rem] border-2 border-dashed border-gray-200 dark:border-gray-700 transition-all hover:bg-gray-50 dark:hover:bg-gray-800/50">
            <div class="w-14 h-14 mb-4 rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-center text-gray-400 group-hover:text-emerald-500 transition-colors">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
            </div>
            <p class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider mb-1">{{ emptyMessage || $t('quotes.show.no_parts') }}</p>
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-5">{{ $t('quotes.parts_display.empty_hint') || 'ابدأ بإضافة قطع الغيار للتقييم' }}</p>
            <button v-if="!readOnly" @click="$emit('add')"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                {{ addButtonText || $t('quotes.show.add_part') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    parts: { type: Array, default: () => [] },
    readOnly: { type: Boolean, default: false },
    showVat: { type: Boolean, default: false },
    emptyMessage: { type: String, default: '' },
    addButtonText: { type: String, default: '' },
    storageKey: { type: String, default: 'parts_display_view_mode' },
    showService: { type: Boolean, default: true },
    compactGrid: { type: Boolean, default: false },
    pendingCheck: { type: Function, default: null }
});

const emit = defineEmits(['edit', 'delete', 'add']);
const { getName } = useLocalized();
const { formatCurrency, formatQuantity, toEnglish } = useNumberFormat();

// View mode state
const viewMode = ref('grid');

onMounted(() => {
    const saved = localStorage.getItem(props.storageKey);
    if (saved && ['grid', 'table'].includes(saved)) viewMode.value = saved;
});

watch(viewMode, (newVal) => localStorage.setItem(props.storageKey, newVal));

const isPending = (part) => props.pendingCheck ? props.pendingCheck(part) : !part.id;

const getUnitName = (part) => part.unit ? getName(part.unit) : '';

function getSourceColorClass(source, type) {
    const colors = {
        warehouse: {
            strip: 'bg-emerald-500',
            badge: 'bg-emerald-500 text-white',
            footer: 'bg-emerald-50/50 dark:bg-emerald-900/20 text-emerald-900 dark:text-emerald-100'
        },
        external: {
            strip: 'bg-blue-500',
            badge: 'bg-blue-500 text-white',
            footer: 'bg-blue-50/50 dark:bg-blue-900/20 text-blue-900 dark:text-blue-100'
        },
        customer: {
            strip: 'bg-amber-500',
            badge: 'bg-amber-500 text-white',
            footer: 'bg-amber-50/50 dark:bg-amber-900/20 text-amber-900 dark:text-amber-100'
        }
    };
    return colors[source]?.[type] || colors.external[type];
}
</script>
