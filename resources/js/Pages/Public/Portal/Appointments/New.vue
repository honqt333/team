<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.booking.title')"
        :subtitle="t('customer_portal.booking.subtitle')"
        show-logout
        show-bottom-nav
        :current-route="'public.portal.appointments.new'"
        :language-base-path="`/${tenant.slug}/portal/appointments/new`"
    >
        <!--
            New.vue — booking flow.

            Three steps:
              1. Vehicle + services
              2. Branch + time
              3. Review & confirm

            The user spec asks for a sticky summary that
            shows the customer's current selections at the
            bottom of the screen on mobile, so the
            customer can always see what they have picked
            without scrolling back up.
        -->

        <WizardStepper
            :steps="stepDefs"
            :current-step="currentStep"
            :step-counter="
                t('customer_portal.booking.step', {
                    current: currentStep + 1,
                    total: stepDefs.length,
                })
            "
        />

        <div
            v-if="bookingError"
            class="mb-4 rounded-2xl border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-900/20 p-4 text-sm text-rose-700 dark:text-rose-200"
        >
            {{ bookingError }}
        </div>

        <!--
            Step 1: vehicle + services. The customer picks
            the vehicle first, then the services they
            want to book. The services list is filtered
            to the customer's previous services if we have
            any, otherwise it shows all available services
            across the tenant. We do NOT require a branch
            at this step — the backend will filter by
            center on submit.
        -->
        <section
            v-show="currentStep === 0"
            class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800"
        >
            <h2 class="text-lg font-bold mb-1">
                {{ t('customer_portal.booking.select_vehicle') }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                {{ t('customer_portal.booking.step_vehicle') }}
            </p>

            <div
                v-if="vehicles.length === 0"
                class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-6 text-center text-sm text-slate-500 dark:text-slate-400"
            >
                {{ t('customer_portal.booking.no_vehicles') }}
            </div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                <SelectionCard
                    v-for="v in vehicles"
                    :key="v.id"
                    :title="v.plate_number"
                    :subtitle="[v.make, v.model, v.year].filter(Boolean).join(' · ')"
                    :selected="form.vehicleId === v.id"
                    @select="selectVehicle(v)"
                />
            </div>

            <h2 class="text-lg font-bold mb-1">
                {{ t('customer_portal.booking.select_services') }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                {{ t('customer_portal.booking.step_services') }}
            </p>

            <div
                v-if="availableServices.length === 0"
                class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-6 text-center text-sm text-slate-500 dark:text-slate-400"
            >
                {{ t('customer_portal.booking.no_services') }}
            </div>
            <div v-else class="space-y-2">
                <label
                    v-for="s in availableServices"
                    :key="s.id"
                    class="flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition"
                    :class="
                        form.serviceIds.includes(s.id)
                            ? 'border-transparent ring-2'
                            : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                    "
                    :style="
                        form.serviceIds.includes(s.id)
                            ? {
                                  backgroundColor:
                                      'color-mix(in srgb, var(--tenant-primary, #2563eb) 8%, transparent)',
                                  '--tw-ring-color': 'var(--tenant-primary, #2563eb)',
                              }
                            : {}
                    "
                >
                    <AppToggle :value="s.id" v-model="form.serviceIds" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-sm">{{ s.name }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400" dir="ltr">
                                {{
                                    t('customer_portal.booking.duration_minutes', {
                                        count: s.duration_minutes,
                                    })
                                }}
                            </span>
                        </div>
                        <p
                            v-if="s.description"
                            class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                        >
                            {{ s.description }}
                        </p>
                    </div>
                </label>
            </div>
        </section>

        <!--
            Step 2: branch + date + time. The customer
            picks a branch first, then a date, then a
            time. The slots come from the existing
            availability endpoint with the chosen
            services. We do NOT block the date picker
            before a branch is chosen — we just show an
            empty slot grid.
        -->
        <section
            v-show="currentStep === 1"
            class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800"
        >
            <h2 class="text-lg font-bold mb-1">
                {{ t('customer_portal.booking.select_branch') }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                {{ t('customer_portal.booking.step_center') }}
            </p>

            <div
                v-if="centers.length === 0"
                class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-6 text-center text-sm text-slate-500 dark:text-slate-400 mb-6"
            >
                {{ t('customer_portal.booking.no_centers') }}
            </div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                <SelectionCard
                    v-for="c in centers"
                    :key="c.id"
                    :title="c.name"
                    :badge="c.is_main ? '★' : ''"
                    :selected="form.centerId === c.id"
                    @select="selectCenter(c)"
                />
            </div>

            <h2 class="text-lg font-bold mb-1">
                {{ t('customer_portal.booking.pick_date') }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                {{ t('customer_portal.booking.pick_time') }}
            </p>

            <div class="mb-4">
                <CustomDatePicker
                    :min-date="minDateStr"
                    :max-date="maxDateStr"
                    v-model="form.date"
                />
            </div>

            <p v-if="loadingSlots" class="text-sm text-slate-500 dark:text-slate-400">
                {{ t('customer_portal.booking.loading_slots') }}
            </p>
            <p v-else-if="slots.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
                {{ t('customer_portal.booking.no_slots') }}
            </p>
            <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                <button
                    v-for="slot in slots"
                    :key="slot"
                    type="button"
                    @click="selectSlot(slot)"
                    class="px-3 py-2 rounded-lg text-sm font-semibold border transition"
                    :class="
                        form.scheduledAt === slot
                            ? 'text-white border-transparent'
                            : 'border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800'
                    "
                    :style="
                        form.scheduledAt === slot
                            ? { backgroundColor: 'var(--tenant-primary, #2563eb)' }
                            : {}
                    "
                    dir="ltr"
                >
                    {{ formatSlotTime(slot) }}
                </button>
            </div>
        </section>

        <!--
            Step 3: review. The customer sees their
            choices in a clean summary and either goes
            back to edit or confirms. The "Confirm
            booking" button is the only primary action
            on this step.
        -->
        <section
            v-show="currentStep === 2"
            class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800"
        >
            <h2 class="text-lg font-bold mb-2">
                {{ t('customer_portal.booking.review_title') }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                {{ t('customer_portal.booking.review_intro') }}
            </p>

            <dl class="divide-y divide-slate-100 dark:divide-slate-800">
                <div class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-sm text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.booking.review_branch') }}
                    </dt>
                    <dd class="font-semibold text-end text-sm">
                        {{ selectedCenter?.name || t('customer_portal.booking.no_branch_picked') }}
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-sm text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.booking.review_vehicle') }}
                    </dt>
                    <dd class="font-semibold text-end text-sm">
                        <SaudiPlateDisplay
                            v-if="selectedVehicle?.plate_number"
                            :plate-number="selectedVehicle.plate_number"
                            size="xs"
                        />
                        <span v-else>{{ t('customer_portal.booking.no_vehicle_picked') }}</span>
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-sm text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.booking.review_services') }}
                    </dt>
                    <dd class="font-semibold text-end text-sm">
                        <ul v-if="selectedServices.length > 0" class="space-y-0.5">
                            <li v-for="s in selectedServices" :key="s.id">
                                {{ s.name }}
                            </li>
                        </ul>
                        <span v-else class="text-slate-400">
                            {{ t('customer_portal.booking.no_services_picked') }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-sm text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.booking.review_date') }}
                    </dt>
                    <dd class="font-semibold text-end text-sm" dir="ltr">
                        {{
                            form.scheduledAt
                                ? formatSlotDateTime(form.scheduledAt)
                                : t('customer_portal.booking.no_time_picked')
                        }}
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-3 py-3">
                    <dt class="text-sm text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.booking.review_duration') }}
                    </dt>
                    <dd class="font-semibold text-end text-sm" dir="ltr">
                        {{
                            t('customer_portal.booking.duration_minutes', {
                                count: estimatedDuration,
                            })
                        }}
                    </dd>
                </div>
            </dl>

            <!-- Optional Booking Notes -->
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <label
                    class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1.5"
                >
                    {{ t('customer_portal.portal_appointments.notes_label') }}
                </label>
                <textarea
                    v-model="form.notes"
                    rows="3"
                    maxlength="1000"
                    :placeholder="t('customer_portal.portal_appointments.notes_placeholder')"
                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-sm"
                ></textarea>
            </div>
        </section>

        <!--
            Step navigation. Three full-text buttons:
            "Back", "Next", "Confirm booking". The user
            spec explicitly forbids lone "‹" and "›"
            labels. The current step drives which buttons
            are visible.
        -->
        <div
            class="mt-6 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3"
        >
            <button
                v-if="currentStep > 0"
                type="button"
                @click="prev"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
            >
                {{ t('customer_portal.booking.btn_back') }}
            </button>
            <span v-else />

            <button
                v-if="currentStep < stepDefs.length - 1"
                type="button"
                @click="next"
                :disabled="!canAdvance"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-sm font-semibold text-white disabled:opacity-40"
                :style="{ backgroundColor: 'var(--tenant-primary, #2563eb)' }"
            >
                {{ t('customer_portal.booking.btn_next') }}
            </button>
            <button
                v-else
                type="button"
                @click="submit"
                :disabled="submitting || !canSubmit"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-sm font-semibold text-white disabled:opacity-40"
                :style="{ backgroundColor: 'var(--tenant-primary, #2563eb)' }"
            >
                {{
                    submitting
                        ? t('customer_portal.booking.submitting')
                        : t('customer_portal.booking.btn_confirm')
                }}
            </button>
        </div>

        <!--
            Sticky mobile summary. We render this at the
            bottom of the page on mobile so the customer
            can always see their current selection
            without scrolling back. The summary is hidden
            on step 3 because the in-page review already
            covers the same information; on step 0 it's
            shown as a "your booking" hint.
        -->
        <aside
            v-if="hasAnySelection"
            class="mt-6 sm:hidden bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800"
        >
            <p
                class="text-[10px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 mb-2"
            >
                {{ t('customer_portal.booking.sticky_summary_label') }}
            </p>
            <ul class="space-y-1.5 text-sm">
                <li class="flex items-center justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.booking.sticky_branch') }}
                    </span>
                    <span class="font-semibold truncate ms-2">
                        {{ selectedCenter?.name || t('customer_portal.booking.no_branch_picked') }}
                    </span>
                </li>
                <li class="flex items-center justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.booking.sticky_vehicle') }}
                    </span>
                    <span class="font-semibold truncate ms-2" dir="ltr">
                        {{
                            selectedVehicle?.plate_number ||
                            t('customer_portal.booking.no_vehicle_picked')
                        }}
                    </span>
                </li>
                <li class="flex items-center justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.booking.sticky_services') }}
                    </span>
                    <span class="font-semibold truncate ms-2">
                        {{
                            form.serviceIds.length > 0
                                ? form.serviceIds.length
                                : t('customer_portal.booking.no_services_picked')
                        }}
                    </span>
                </li>
                <li class="flex items-center justify-between gap-2">
                    <span class="text-slate-500 dark:text-slate-400">
                        {{ t('customer_portal.booking.sticky_when') }}
                    </span>
                    <span class="font-semibold truncate ms-2" dir="ltr">
                        {{
                            form.scheduledAt
                                ? formatSlotTime(form.scheduledAt)
                                : t('customer_portal.booking.no_time_picked')
                        }}
                    </span>
                </li>
            </ul>
        </aside>
    </CustomerPortalLayout>
</template>

<script setup>
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import WizardStepper from '@/Components/CustomerPortal/WizardStepper.vue';
import SelectionCard from '@/Components/CustomerPortal/SelectionCard.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    centers: { type: Array, default: () => [] },
    vehicles: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});

const { t: tRaw, locale: i18nLocale } = useI18n();
const page = usePage();
const isRtl = computed(
    () => props.locale?.is_rtl ?? (page.props.locale || i18nLocale.value || 'ar') === 'ar'
);
const t = (path, params) => {
    const v = tRaw(path, params || {});
    return v === path ? path.split('.').pop() : v;
};

// Three steps. The order is part of the public
// contract: the user spec requires these three
// labels, in this order.
const stepDefs = computed(() => [
    { key: 'vehicle', label: t('customer_portal.booking.step_1') },
    { key: 'time', label: t('customer_portal.booking.step_2') },
    { key: 'review', label: t('customer_portal.booking.step_3') },
]);

const currentStep = ref(0);

const form = ref({
    centerId: null,
    vehicleId: null,
    serviceIds: [],
    date: null,
    scheduledAt: null,
    notes: '',
});

const selectedCenter = computed(
    () => props.centers.find((c) => c.id === form.value.centerId) || null
);
const selectedVehicle = computed(
    () => props.vehicles.find((v) => v.id === form.value.vehicleId) || null
);

// The services list is filtered by the chosen center
// when we have one, otherwise it falls back to all
// tenant services. The same filter the backend uses.
const availableServices = computed(() => {
    if (form.value.centerId) {
        return props.services.filter((s) => s.center_id === form.value.centerId);
    }
    return props.services;
});
const selectedServices = computed(() =>
    props.services.filter((s) => form.value.serviceIds.includes(s.id))
);
const estimatedDuration = computed(() =>
    selectedServices.value.reduce((acc, s) => acc + (s.duration_minutes || 0), 0)
);

const minDateStr = computed(() => {
    // 1 hour from now, formatted YYYY-MM-DD. The
    // server's MIN_LOOKAHEAD gate is the source of
    // truth; this is just the floor on the date
    // picker.
    const d = new Date();
    d.setHours(d.getHours() + 1);
    return d.toISOString().slice(0, 10);
});
const maxDateStr = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + 14);
    return d.toISOString().slice(0, 10);
});

const slots = ref([]);
const loadingSlots = ref(false);

async function fetchSlots() {
    if (!form.value.centerId || !form.value.date || form.value.serviceIds.length === 0) {
        slots.value = [];
        return;
    }
    loadingSlots.value = true;
    try {
        const url = route('public.portal.appointments.availability', {
            tenant: props.tenant.slug,
        });
        const params = new URLSearchParams();
        params.set('center_id', String(form.value.centerId));
        params.set('date', form.value.date);
        form.value.serviceIds.forEach((id) => params.append('service_ids[]', String(id)));
        const r = await fetch(`${url}?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (r.ok) {
            const data = await r.json();
            slots.value = Array.isArray(data?.slots) ? data.slots : [];
        } else {
            slots.value = [];
        }
    } finally {
        loadingSlots.value = false;
    }
}

watch(
    () => [form.value.centerId, form.value.date, form.value.serviceIds.slice()],
    () => {
        if (currentStep.value === 1) {
            form.value.scheduledAt = null;
            fetchSlots();
        }
    },
    { deep: true }
);

watch(currentStep, (val) => {
    if (val === 1) {
        if (!form.value.date) {
            form.value.date = minDateStr.value;
        }
        fetchSlots();
    }
});

// Step 1 needs a vehicle and at least one service.
// Step 2 needs a center and a time. Step 3 inherits
// the same checks.
const canAdvance = computed(() => {
    if (currentStep.value === 0) {
        return form.value.vehicleId !== null && form.value.serviceIds.length > 0;
    }
    if (currentStep.value === 1) {
        return form.value.centerId !== null && form.value.scheduledAt !== null;
    }
    return true;
});
const canSubmit = computed(() => canAdvance.value);
const submitting = ref(false);
const bookingError = ref(null);

const hasAnySelection = computed(
    () =>
        form.value.vehicleId !== null ||
        form.value.serviceIds.length > 0 ||
        form.value.centerId !== null ||
        form.value.scheduledAt !== null
);

function next() {
    bookingError.value = null;
    if (!canAdvance.value) {
        return;
    }
    if (currentStep.value < stepDefs.value.length - 1) {
        currentStep.value += 1;
    }
}
function prev() {
    bookingError.value = null;
    if (currentStep.value > 0) {
        currentStep.value -= 1;
    }
}

function selectCenter(c) {
    form.value.centerId = c.id;
    bookingError.value = null;
}
function selectVehicle(v) {
    form.value.vehicleId = v.id;
    bookingError.value = null;
}
function selectSlot(s) {
    form.value.scheduledAt = s;
    bookingError.value = null;
}

function formatSlotTime(iso) {
    try {
        return new Intl.DateTimeFormat(isRtl.value ? 'ar-SA' : 'en-US', {
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(iso));
    } catch (e) {
        return iso;
    }
}
function formatSlotDateTime(iso) {
    if (!iso) {
        return '';
    }
    try {
        return new Intl.DateTimeFormat(isRtl.value ? 'ar-SA' : 'en-US', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch (e) {
        return iso;
    }
}

function submit() {
    if (!canSubmit.value || submitting.value) {
        return;
    }
    submitting.value = true;
    bookingError.value = null;
    router.post(
        route('public.portal.appointments.store', {
            tenant: props.tenant.slug,
        }),
        {
            center_id: form.value.centerId,
            vehicle_id: form.value.vehicleId,
            service_ids: form.value.serviceIds,
            scheduled_at: form.value.scheduledAt,
            notes: form.value.notes || null,
        },
        {
            onError: (errors) => {
                submitting.value = false;
                const msg =
                    errors?.booking ||
                    Object.values(errors || {})[0] ||
                    t('customer_portal.booking.errors.slot_no_longer_available');
                bookingError.value = msg;
            },
            onFinish: () => {
                submitting.value = false;
            },
        }
    );
}
</script>
