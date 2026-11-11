<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.portal_work_orders.title')"
        :subtitle="t('customer_portal.portal_work_orders.subtitle')"
        show-logout
        show-back-to-site
        show-bottom-nav
        :current-route="'public.portal.workOrders.index'"
        :language-base-path="`/${tenant.slug}/portal/work-orders`"
    >
        <!-- Vehicles Fleet Section with Interactive Filter -->
        <section class="mb-8">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">
                    {{ t('customer_portal.portal_work_orders.vehicles_section') }}
                </h2>
                <button
                    v-if="selectedVehicleId !== null"
                    type="button"
                    class="text-xs font-extrabold text-[color:var(--tenant-primary,#2563eb)] hover:underline"
                    @click="selectedVehicleId = null"
                >
                    {{ t('customer_portal.portal_work_orders.all_vehicles') }}
                </button>
            </div>

            <div
                v-if="!vehicles || vehicles.length === 0"
                class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500"
            >
                {{ t('customer_portal.dashboard.no_vehicles') }}
            </div>

            <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <button
                    v-for="v in vehicles"
                    :key="v.id"
                    type="button"
                    class="flex items-center justify-between gap-3 rounded-2xl border bg-white p-4 text-start shadow-sm transition hover:shadow-md"
                    :class="
                        selectedVehicleId === v.id
                            ? 'border-[color:var(--tenant-primary,#2563eb)] ring-2 ring-[color:var(--tenant-primary,#2563eb)]/20'
                            : 'border-slate-200/90 hover:border-slate-300'
                    "
                    @click="selectedVehicleId = selectedVehicleId === v.id ? null : v.id"
                >
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-extrabold text-slate-900">
                            {{ [v.make, v.model].filter(Boolean).join(' ') || v.plate_number }}
                        </div>
                        <div
                            class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-slate-500"
                        >
                            <span v-if="v.year">{{ v.year }}</span>
                            <span v-if="v.color">· {{ v.color }}</span>
                            <span v-if="v.odometer" dir="ltr">
                                ·
                                {{
                                    t('customer_portal.portal_work_orders.odometer_unit', {
                                        km: Number(v.odometer).toLocaleString(),
                                    })
                                }}
                            </span>
                        </div>
                    </div>
                    <SaudiPlateDisplay :plate-number="v.plate_number" size="sm" />
                </button>
            </div>
        </section>

        <!-- Work Orders List -->
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">
                    {{ t('customer_portal.portal_work_orders.title') }}
                </h2>
            </div>

            <div
                v-if="filteredWorkOrders.length === 0"
                class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500"
            >
                {{ t('customer_portal.portal_work_orders.empty') }}
            </div>

            <div v-else class="space-y-4">
                <a
                    v-for="w in filteredWorkOrders"
                    :key="w.id"
                    :href="`/${tenant.slug}/portal/work-order/${w.id}`"
                    class="block rounded-3xl border border-slate-200/90 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow-md sm:p-6"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="text-base font-black text-slate-900">
                                    {{
                                        t('customer_portal.portal_work_orders.code_title', {
                                            code: w.code,
                                        })
                                    }}
                                </span>
                                <StatusBadge
                                    :status="w.status"
                                    :label="workOrderStatusLabel(w.status)"
                                />
                            </div>
                            <p
                                v-if="w.vehicle_label || w.center_name"
                                class="mt-1 text-xs text-slate-500"
                            >
                                {{ [w.vehicle_label, w.center_name].filter(Boolean).join(' · ') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <SaudiPlateDisplay
                                v-if="w.vehicle_plate"
                                :plate-number="w.vehicle_plate"
                                size="sm"
                            />
                        </div>
                    </div>

                    <!-- 4-Stage Visual Stepper -->
                    <div class="mt-5">
                        <div class="mb-2 flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-600">
                                {{ t('customer_portal.portal_work_orders.progress_title') }}
                            </span>
                            <span v-if="w.items_count > 0" class="font-semibold text-slate-500">
                                {{
                                    t('customer_portal.portal_work_orders.completed_items', {
                                        done: w.completed_items_count,
                                        total: w.items_count,
                                    })
                                }}
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :style="{
                                    width: `${w.progress || 20}%`,
                                    backgroundColor: 'var(--tenant-primary, #2563eb)',
                                }"
                            />
                        </div>

                        <div class="mt-3 grid grid-cols-4 gap-2 text-[11px]">
                            <div
                                v-for="(stage, idx) in stages"
                                :key="stage.key"
                                class="font-bold"
                                :class="
                                    (w.stage_index || 1) >= idx + 1
                                        ? 'text-[color:var(--tenant-primary,#2563eb)]'
                                        : 'text-slate-400'
                                "
                            >
                                {{ stage.label }}
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500"
                    >
                        <div class="flex flex-wrap items-center gap-4">
                            <span v-if="w.entry_date || w.created_at">
                                {{ t('customer_portal.portal_work_orders.entry_date') }}:
                                <strong class="text-slate-700">
                                    {{ formatDateOnly(w.entry_date || w.created_at) }}
                                </strong>
                            </span>
                            <span v-if="w.expected_end_date">
                                {{ t('customer_portal.portal_work_orders.expected_delivery') }}:
                                <strong class="text-slate-700">
                                    {{ formatDateOnly(w.expected_end_date) }}
                                </strong>
                            </span>
                        </div>

                        <span
                            class="inline-flex items-center gap-1 font-extrabold text-[color:var(--tenant-primary,#2563eb)]"
                        >
                            {{ t('customer_portal.portal_work_orders.track_cta') }} ←
                        </span>
                    </div>
                </a>
            </div>
        </section>
    </CustomerPortalLayout>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import StatusBadge from '@/Components/CustomerPortal/StatusBadge.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    vehicles: { type: Array, default: () => [] },
    workOrders: { type: Array, default: () => [] },
});

const { t: tRaw, locale: i18nLocale } = useI18n();
const page = usePage();

const t = (path, params) => {
    const v = tRaw(path, params || {});
    return v === path ? path.split('.').pop() : v;
};

const selectedVehicleId = ref(null);

onMounted(() => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const vId = Number(params.get('vehicle'));
        if (vId > 0) {
            selectedVehicleId.value = vId;
        }
    }
});

const activeLocale = computed(() => {
    const current = props.locale?.current || page.props.locale || i18nLocale.value || 'ar';
    return current === 'en' ? 'en-US' : 'ar-SA';
});

const stages = computed(() => [
    { key: 1, label: t('customer_portal.portal_work_orders.stage_1') },
    { key: 2, label: t('customer_portal.portal_work_orders.stage_2') },
    { key: 3, label: t('customer_portal.portal_work_orders.stage_3') },
    { key: 4, label: t('customer_portal.portal_work_orders.stage_4') },
]);

const filteredWorkOrders = computed(() => {
    if (!selectedVehicleId.value) {
        return props.workOrders || [];
    }
    return (props.workOrders || []).filter((w) => Number(w.vehicle_id) === selectedVehicleId.value);
});

function workOrderStatusLabel(status) {
    const key = `customer_portal.work_order_statuses.${status}`;
    const v = tRaw(key);
    return v === key ? status : v;
}

function formatDateOnly(iso) {
    if (!iso) return '';
    try {
        const d = new Date(iso);
        return d.toLocaleDateString(activeLocale.value, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return iso;
    }
}
</script>
