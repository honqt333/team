<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col h-full">
        <!-- Gray Header: Plate + Model + Color -->
        <!--
            The header is a link to the vehicle page when we have one.
            We intentionally do NOT fall back to a hardcoded vehicle id
            (the old `workOrder.vehicle?.id || 1` would silently link to
            whatever vehicle #1 actually is) — instead we render a static
            header when the relation is missing.
        -->
        <component
            :is="workOrder.vehicle?.id ? Link : 'div'"
            :href="workOrder.vehicle?.id ? route('vehicles.show', workOrder.vehicle.id) : undefined"
            class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/60 border-b border-gray-100 dark:border-gray-700 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-800/80 transition-colors group rounded-t-2xl">
            <div class="w-8"></div>
            <div class="text-center flex flex-col items-center">
                <SaudiPlateDisplay :plate-number="workOrder.vehicle?.plate_number" size="md" />
                <p class="text-xs font-semibold text-blue-500 dark:text-blue-400 mt-1.5">
                    {{ getName(workOrder.vehicle?.make) }} {{ getName(workOrder.vehicle?.model) }} {{
                        workOrder.vehicle?.year }}
                </p>
            </div>
            <div class="w-8 flex items-center justify-end">
                <div v-if="workOrder.vehicle?.color" class="relative group/color">
                    <div class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-700 shadow-md ring-1 ring-gray-300 dark:ring-gray-600"
                        :style="{ backgroundColor: getColorHex(workOrder.vehicle.color) }"></div>
                    <div
                        class="absolute bottom-full right-0 mb-2 hidden group-hover/color:block z-50 pointer-events-none">
                        <div
                            class="bg-gray-900 dark:bg-gray-700 text-white text-xs font-medium px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                            {{ workOrder.vehicle.color }}
                        </div>
                        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1">
                        </div>
                    </div>
                </div>
            </div>
        </component>

        <!-- Middle: Logo (centered) + Customer Info -->
        <div class="flex items-center px-5 py-4 gap-4 flex-1">
            <!-- Vehicle make logo (or fallback icon) -->
            <div class="flex-shrink-0 w-16 h-16 flex items-center justify-center">
                <img v-if="workOrder.vehicle?.make?.logo_path"
                    :src="`/storage/${workOrder.vehicle.make.logo_path}`"
                    :alt="getName(workOrder.vehicle?.make)" class="w-full h-full object-contain" />
                <div v-else
                    class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l3 1h6z" />
                    </svg>
                </div>
            </div>

            <!-- Customer info (right-aligned) -->
            <div class="flex-1 flex flex-col gap-2.5 items-end">
                <!-- Customer name (links to customer profile when present) -->
                <div class="relative group/customer">
                    <component
                        :is="workOrder.customer?.id ? Link : 'div'"
                        :href="workOrder.customer?.id ? route('customers.show', workOrder.customer.id) : undefined"
                        class="flex items-center justify-end gap-2 text-base font-bold text-gray-800 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        <span>{{ workOrder.customer?.name }}</span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </component>
                    <div class="absolute bottom-full right-0 mb-2 hidden group-hover/customer:block z-50 pointer-events-none">
                        <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs font-medium px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                            {{ $t('customers.view_profile') }}
                        </div>
                        <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1"></div>
                    </div>
                </div>

                <!-- Contact name (if different from customer) -->
                <div v-if="workOrder.contact_name"
                    class="relative group/contact flex items-center justify-end gap-1.5 text-xs text-gray-500 font-medium">
                    <span>{{ workOrder.contact_name }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <div class="absolute bottom-full right-0 mb-2 hidden group-hover/contact:block z-50 pointer-events-none">
                        <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs font-medium px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                            {{ $t('work_orders.form.contact_name') }}
                        </div>
                        <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1"></div>
                    </div>
                </div>

                <!-- WhatsApp quick link (contact phone or customer phone) -->
                <div class="relative group/whatsapp">
                    <a :href="`https://wa.me/${(workOrder.contact_phone || workOrder.customer?.phone)?.replace(/\+/g, '').replace(/\s/g, '')}`"
                        target="_blank"
                        class="flex items-center justify-end gap-2 text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition-colors">
                        <span class="text-sm font-mono font-semibold" dir="ltr">{{ workOrder.contact_phone
                            || workOrder.customer?.phone }}</span>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                        </svg>
                    </a>
                    <div class="absolute bottom-full right-0 mb-2 hidden group-hover/whatsapp:block z-50 pointer-events-none">
                        <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs font-medium px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                            {{ $t('common.open_in_whatsapp') }}
                        </div>
                        <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom: VIN + Odometer -->
        <div
            class="flex items-center justify-between px-5 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 rounded-b-2xl">
            <div class="relative group/vin">
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400 cursor-default">
                    {{ workOrder.vehicle?.vin || '—' }}
                </span>
                <div v-if="workOrder.vehicle?.vin"
                    class="absolute bottom-full left-0 mb-2 hidden group-hover/vin:block z-50 pointer-events-none">
                    <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                        {{ $t('vehicles.form.vin') }}: {{ workOrder.vehicle.vin }}
                    </div>
                    <div class="absolute top-full left-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1"></div>
                </div>
            </div>
            <div class="relative group/odo flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span class="text-sm font-bold text-teal-600 dark:text-teal-400 font-mono cursor-default" dir="ltr">
                    {{ workOrder.mileage ? Number(workOrder.mileage).toLocaleString() + ' km' : '—' }}
                </span>
                <div class="absolute bottom-full right-0 mb-2 hidden group-hover/odo:block z-50 pointer-events-none">
                    <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                        {{ $t('work_orders.form.mileage') }}
                    </div>
                    <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import { useLocalized } from '@/Composables/useLocalized';

const props = defineProps({
    workOrder: { type: Object, required: true },
    colors: { type: Array, default: () => [] },
});

const { getName } = useLocalized();

/**
 * Resolve a vehicle color name (ar/en) to its hex code.
 * Only the colors table is consulted; unknown colors fall back to a
 * neutral grey. The hardcoded Tailwind-palette map was removed because
 * it disagreed with the real-world hex values stored in `vehicle_colors`
 * (silver: #9ca3af vs #C0C0C0, etc.).
 */
function getColorHex(colorName) {
    if (!colorName) return '#9ca3af';
    const trimmedColor = String(colorName).trim();
    if (props.colors && props.colors.length > 0) {
        const searchColor = trimmedColor.toLowerCase();
        const found = props.colors.find(c =>
            (c.name_ar && c.name_ar.toLowerCase() === searchColor) ||
            (c.name_en && c.name_en.toLowerCase() === searchColor) ||
            (c.hex_code && c.hex_code.toLowerCase() === searchColor)
        );
        if (found && found.hex_code) return found.hex_code;
    }
    return '#9ca3af';
}
</script>
