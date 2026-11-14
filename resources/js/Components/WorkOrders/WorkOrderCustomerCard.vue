<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col h-full overflow-hidden transition-all hover:shadow-md"
    >
        <slot name="header" />

        <VehicleCustomerIdentity
            :vehicle="resolvedVehicle"
            :customer="resolvedCustomer"
            :customer-name="customerName"
            :vat-number="vatNumber"
            :address="address"
            :party-type="partyType"
            :party-href="partyHref"
            :party-badge="partyBadge"
            :color-hex="getColorHex(resolvedVehicle?.color)"
        >
            <!-- Partner / Third-Party Payer Identity Block (Color-coded side strip matching Wrshah style) -->
            <template v-if="workOrder?.dealer" #extra-identity>
                <div
                    class="flex items-center justify-between gap-2 border-r-[3.5px] border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/10 pr-3.5 pl-2 py-1.5 rounded-l-lg"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300"
                            >
                                {{
                                    workOrder.dealer.type
                                        ? $t(`dealers.types.${workOrder.dealer.type}`)
                                        : $t('nav.partners')
                                }}
                            </span>
                            <component
                                :is="workOrder.dealer.id ? Link : 'span'"
                                :href="
                                    workOrder.dealer.id
                                        ? route('app.dealers.show', workOrder.dealer.id)
                                        : undefined
                                "
                                class="text-sm font-bold text-gray-900 dark:text-gray-100 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors truncate"
                            >
                                {{ dealerDisplayName }}
                            </component>
                            <span
                                class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="
                                    workOrder.is_paid_by_dealer
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200'
                                "
                            >
                                {{
                                    workOrder.is_paid_by_dealer
                                        ? $t('work_orders.billed_to_dealer')
                                        : $t('work_orders.billed_to_customer')
                                }}
                            </span>
                        </div>

                        <div
                            v-if="workOrder.dealer.phone || workOrder.dealer.tax_number"
                            class="flex flex-wrap items-center gap-x-3 gap-y-1"
                        >
                            <span
                                v-if="workOrder.dealer.phone"
                                class="text-xs font-mono font-medium text-gray-500 dark:text-gray-400"
                                dir="ltr"
                            >
                                {{ workOrder.dealer.phone }}
                            </span>
                            <span
                                v-if="workOrder.dealer.tax_number"
                                class="text-[11px] font-mono text-gray-500 dark:text-gray-400"
                            >
                                {{ $t('customers.tax_number') }}: {{ workOrder.dealer.tax_number }}
                            </span>
                        </div>
                    </div>

                    <!-- Direct WhatsApp button for Partner -->
                    <a
                        v-if="workOrder.dealer.phone"
                        :href="`https://wa.me/${String(workOrder.dealer.phone).replace(/[^0-9]/g, '')}`"
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
            </template>
        </VehicleCustomerIdentity>

        <!-- 3. Bottom Data Section: Odometer & VIN with vertical divider -->
        <div
            v-if="resolvedVehicle"
            class="flex items-center justify-between h-10 px-4 border-t border-gray-100 dark:border-gray-700/80 bg-gray-50/70 dark:bg-gray-900/50"
        >
            <!-- Odometer item -->
            <div
                class="flex items-center gap-2 flex-1 min-w-0"
                :title="$t('work_orders.form.mileage')"
            >
                <svg
                    class="w-4 h-4 text-gray-400 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"
                    />
                </svg>
                <span
                    class="text-xs font-mono font-semibold text-gray-700 dark:text-gray-300 truncate"
                    dir="ltr"
                >
                    {{
                        resolvedMileage !== null &&
                        resolvedMileage !== undefined &&
                        resolvedMileage !== ''
                            ? Number(resolvedMileage).toLocaleString() + ' ' + $t('common.km')
                            : '—'
                    }}
                </span>
            </div>

            <!-- Divider -->
            <div class="w-px h-4 bg-gray-200 dark:bg-gray-700 shrink-0 mx-2"></div>

            <!-- VIN item -->
            <div
                class="flex items-center gap-2 flex-1 min-w-0 justify-end"
                :title="$t('vehicles.form.vin')"
            >
                <svg
                    class="w-4 h-4 text-gray-400 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
                    />
                </svg>
                <span
                    class="text-xs font-mono font-medium text-gray-600 dark:text-gray-400 truncate"
                    dir="ltr"
                >
                    {{ resolvedVehicle?.vin || '—' }}
                </span>
            </div>
        </div>

        <slot name="footer" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import VehicleCustomerIdentity from '@/Components/Vehicles/VehicleCustomerIdentity.vue';

const { locale } = useI18n();

const props = defineProps({
    workOrder: { type: Object, default: null },
    vehicle: { type: Object, default: null },
    customer: { type: Object, default: null },
    mileage: { type: [Number, String], default: null },
    customerName: { type: String, default: '' },
    vatNumber: { type: String, default: '' },
    address: { type: String, default: '' },
    partyType: { type: String, default: 'customer' },
    partyHref: { type: String, default: '' },
    partyBadge: { type: String, default: '' },
    colors: { type: Array, default: () => [] },
});

const resolvedVehicle = computed(() => props.vehicle ?? props.workOrder?.vehicle ?? null);
const resolvedCustomer = computed(() => props.customer ?? props.workOrder?.customer ?? null);
const dealerDisplayName = computed(() => {
    const dealer = props.workOrder?.dealer;
    if (!dealer) return '';
    return locale.value === 'en'
        ? dealer.name_en || dealer.name || ''
        : dealer.name || dealer.name_en || '';
});
const resolvedMileage = computed(
    () => props.mileage ?? props.workOrder?.mileage ?? props.workOrder?.odometer ?? null
);

/**
 * Resolve a vehicle color name (ar/en) to its hex code.
 */
function getColorHex(colorName) {
    if (!colorName) return '#9ca3af';
    const trimmedColor = String(colorName).trim();
    const searchColor = trimmedColor.toLowerCase();

    if (props.colors && props.colors.length > 0) {
        const found = props.colors.find(
            (c) =>
                (c.name_ar && c.name_ar.toLowerCase() === searchColor) ||
                (c.name_en && c.name_en.toLowerCase() === searchColor) ||
                (c.name && c.name.toLowerCase() === searchColor) ||
                (c.hex_code && c.hex_code.toLowerCase() === searchColor)
        );
        if (found && found.hex_code) {
            return found.hex_code.startsWith('#') ? found.hex_code : `#${found.hex_code}`;
        }
    }

    const colorMap = {
        أبيض: '#ffffff',
        أسود: '#111827',
        فضي: '#9ca3af',
        رمادي: '#4b5563',
        أحمر: '#dc2626',
        أزرق: '#2563eb',
        أخضر: '#16a34a',
        ذهبي: '#ca8a04',
        بني: '#78350f',
        برتقالي: '#ea580c',
        white: '#ffffff',
        black: '#111827',
        silver: '#9ca3af',
        gray: '#4b5563',
        red: '#dc2626',
        blue: '#2563eb',
        green: '#16a34a',
        gold: '#ca8a04',
        brown: '#78350f',
        orange: '#ea580c',
    };

    return colorMap[searchColor] || colorMap[trimmedColor] || '#9ca3af';
}
</script>
