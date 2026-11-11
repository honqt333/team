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
        />

        <!-- Partner / Third-Party Payer Banner -->
        <div
            v-if="workOrder?.dealer"
            class="flex items-center justify-between gap-2 px-4 py-2 border-t border-indigo-100 dark:border-indigo-900/40 bg-indigo-50/70 dark:bg-indigo-950/30 text-xs"
        >
            <div class="flex items-center gap-2 min-w-0">
                <span class="font-bold text-indigo-800 dark:text-indigo-300 truncate">
                    {{ workOrder.dealer.name }}
                </span>
                <span
                    v-if="workOrder.dealer.type"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/80 dark:bg-gray-800 text-indigo-700 dark:text-indigo-300"
                >
                    {{ $t(`dealers.types.${workOrder.dealer.type}`) }}
                </span>
            </div>
            <span
                class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold"
                :class="
                    workOrder.is_paid_by_dealer
                        ? 'bg-indigo-600 text-white'
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
import VehicleCustomerIdentity from '@/Components/Vehicles/VehicleCustomerIdentity.vue';

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
