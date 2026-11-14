<script setup>
import { Link } from '@inertiajs/vue3';
import Tooltip from '@/Components/Tooltip.vue';
import SaudiPlateDisplay from './SaudiPlateDisplay.vue';
import { useLocalized } from '@/Composables/useLocalized';
defineProps({
    vehicle: { type: Object, default: null },
    customer: { type: Object, default: null },
    colorHex: { type: String, default: '#9ca3af' },
    customerName: { type: String, default: '' },
    vatNumber: { type: String, default: '' },
    address: { type: String, default: '' },
    partyType: { type: String, default: 'customer' },
    partyHref: { type: String, default: '' },
    partyBadge: { type: String, default: '' },
});
const { getName } = useLocalized();
</script>

<template>
    <component
        v-if="vehicle"
        :is="vehicle?.id ? Link : 'div'"
        :href="vehicle?.id ? route('vehicles.show', vehicle.id) : undefined"
        class="flex items-center justify-between bg-white dark:bg-gray-800/90 border-b border-gray-100 dark:border-gray-700/80 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors group"
    >
        <!-- Right side (RTL): Make Logo + Model Name -->
        <div class="flex items-center gap-3 min-w-0">
            <div
                class="w-11 h-11 rounded-full border border-gray-200 dark:border-gray-700 p-1 bg-gray-50 dark:bg-gray-900 flex items-center justify-center shrink-0 shadow-inner"
            >
                <img
                    v-if="vehicle?.make?.logo_path"
                    :src="`/storage/${vehicle.make.logo_path}`"
                    :alt="getName(vehicle?.make)"
                    class="w-full h-full object-contain"
                />
                <svg
                    v-else
                    class="w-6 h-6 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l3 1h6z"
                    />
                </svg>
            </div>
            <div class="min-w-0">
                <h4
                    class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"
                >
                    {{ getName(vehicle?.make) }}
                    {{ getName(vehicle?.model) || vehicle?.model_other || '' }}
                </h4>
                <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400">
                    {{ vehicle?.year || '—' }}
                </p>
            </div>
        </div>

        <!-- Left side (RTL): Plate Display + Color Badge -->
        <div class="flex items-center gap-3 shrink-0">
            <SaudiPlateDisplay :plate-number="vehicle?.plate_number" size="md" />

            <div v-if="vehicle?.color" class="relative group/color">
                <Tooltip :text="vehicle.color">
                    <div
                        class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-700 shadow-md ring-1 ring-gray-300 dark:ring-gray-600"
                        :style="{
                            backgroundColor: colorHex,
                        }"
                    ></div>
                </Tooltip>
            </div>
        </div>
    </component>

    <!-- 2. Middle: Customer / Contact Section with colored side-strip -->
    <div class="p-4 flex-1 flex flex-col justify-center gap-3">
        <div
            class="flex items-center justify-between border-r-[3.5px] pr-3.5 pl-2 py-1.5 rounded-l-lg"
            :class="
                partyType === 'supplier'
                    ? 'border-amber-500 bg-amber-50/20 dark:bg-amber-950/10'
                    : 'border-teal-500 bg-teal-50/20 dark:bg-teal-950/10'
            "
        >
            <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span
                        class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                        :class="
                            partyType === 'supplier'
                                ? 'bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300'
                                : 'bg-teal-100 dark:bg-teal-900/60 text-teal-700 dark:text-teal-300'
                        "
                    >
                        {{
                            partyBadge ||
                            (customer?.type === 'company'
                                ? $t('customers.type.company')
                                : $t('customers.type.individual'))
                        }}
                    </span>
                    <component
                        :is="
                            partyHref || (partyType === 'customer' && customer?.id) ? Link : 'span'
                        "
                        :href="
                            partyHref ||
                            (partyType === 'customer' && customer?.id
                                ? route('customers.show', customer.id)
                                : undefined)
                        "
                        class="text-sm font-bold text-gray-900 dark:text-gray-100 transition-colors truncate"
                        :class="
                            partyType === 'supplier'
                                ? 'hover:text-amber-600 dark:hover:text-amber-400'
                                : 'hover:text-teal-600 dark:hover:text-teal-400'
                        "
                    >
                        {{ customerName || customer?.name || '—' }}
                    </component>
                </div>

                <!-- Phone / WhatsApp & Optional VAT -->
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span
                        class="text-xs font-mono font-medium text-gray-500 dark:text-gray-400"
                        dir="ltr"
                    >
                        {{ customer?.phone || '—' }}
                    </span>
                    <span
                        v-if="vatNumber"
                        class="text-[11px] font-mono text-gray-500 dark:text-gray-400"
                    >
                        {{
                            partyType === 'supplier'
                                ? $t('suppliers.tax_number')
                                : $t('customers.tax_number')
                        }}: {{ vatNumber }}
                    </span>
                </div>
            </div>

            <!-- Direct WhatsApp button -->
            <a
                v-if="customer?.phone"
                :href="`https://wa.me/${String(customer.phone).replace(/[^0-9]/g, '')}`"
                target="_blank"
                rel="noopener noreferrer"
                class="p-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 transition-all shadow-sm flex items-center justify-center shrink-0"
                :title="$t('common.open_in_whatsapp')"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                    />
                </svg>
            </a>
        </div>

        <div
            v-if="address"
            class="flex items-start gap-2 text-xs text-gray-600 dark:text-gray-400 bg-gray-50/60 dark:bg-gray-900/30 px-3 py-2 rounded-xl border border-gray-100 dark:border-gray-700/40"
        >
            <svg
                class="w-4 h-4 text-gray-400 shrink-0 mt-0.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
            </svg>
            <span class="font-medium leading-relaxed" dir="auto">{{ address }}</span>
        </div>
    </div>
</template>
