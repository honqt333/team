<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.actions.reschedule_title')"
        :subtitle="t('customer_portal.actions.reschedule_subtitle')"
        show-logout
        :language-base-path="`/${tenant.slug}/portal/appointment/${appointment.id}/edit`"
    >
        <!--
            Reschedule.vue — move an existing appointment.

            Two steps:
              1. Branch + time
              2. Review & confirm

            The vehicle and services on the appointment
            are immutable here — the customer can only
            move the slot. The current appointment is
            always visible so the customer can compare
            their new choice with the existing one.
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
            class="rounded-2xl border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-900/20 p-4 text-sm text-rose-700 dark:text-rose-200 mb-4"
        >
            {{ bookingError }}
        </div>

        <!--
            Current-appointment summary. Visible on
            every step so the customer always knows
            what they are moving.
        -->
        <section
            class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 mb-6"
        >
            <p
                class="text-[10px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 mb-2"
            >
                {{ t('customer_portal.actions.current_label') }}
            </p>
            <div class="flex items-center justify-between gap-3" dir="ltr">
                <div class="font-semibold">
                    {{ formatDateTime(appointment.scheduled_at) }}
                </div>
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    {{ appointment.center_name }}
                </div>
            </div>
            <ul
                v-if="appointment.services && appointment.services.length"
                class="mt-3 flex flex-wrap gap-2 text-xs"
            >
                <li
                    v-for="s in appointment.services"
                    :key="s.id"
                    class="px-2 py-1 rounded-full bg-slate-100 dark:bg-slate-800"
                >
                    {{ s.name }}
                </li>
            </ul>
        </section>

        <!--
            Step 1: branch + date + time. Slots come
            from the existing reschedule-availability
            endpoint.
        -->
        <section
            v-show="currentStep === 0"
            class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800"
        >
            <h2 class="text-lg font-bold mb-1">
                {{ t('customer_portal.actions.reschedule_step_1') }}
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
                    :subtitle="
                        c.id === appointment.center_id
                            ? t('customer_portal.actions.reschedule_same_center')
                            : ''
                    "
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
                    @click="form.scheduledAt = slot"
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
            Step 2: review. The customer sees the
            chosen branch + new time before they
            confirm. The button is full text and is
            the only primary action on this step.
        -->
        <section
            v-show="currentStep === 1"
            class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800"
        >
            <h2 class="text-lg font-bold mb-3">
                {{ t('customer_portal.actions.reschedule_summary_title') }}
            </h2>
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
                        {{ t('customer_portal.booking.review_date') }}
                    </dt>
                    <dd class="font-semibold text-end text-sm" dir="ltr">
                        {{
                            form.scheduledAt
                                ? formatDateTime(form.scheduledAt)
                                : t('customer_portal.booking.no_time_picked')
                        }}
                    </dd>
                </div>
            </dl>
        </section>

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
            <a
                v-else
                :href="backToShow"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
            >
                {{ t('customer_portal.booking.back_to_dashboard') }}
            </a>

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
                :disabled="submitting"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-sm font-semibold text-white disabled:opacity-40"
                :style="{ backgroundColor: 'var(--tenant-primary, #2563eb)' }"
            >
                {{
                    submitting
                        ? t('customer_portal.actions.reschedule_submitting')
                        : t('customer_portal.actions.reschedule_submit')
                }}
            </button>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import WizardStepper from '@/Components/CustomerPortal/WizardStepper.vue';
import SelectionCard from '@/Components/CustomerPortal/SelectionCard.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    appointment: { type: Object, required: true },
    centers: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    minHoursBefore: { type: Number, default: 2 },
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

const backToShow = computed(
    () => `/${props.tenant.slug}/portal/appointment/${props.appointment.id}`
);

// Two steps for reschedule. The customer picks a
// new branch+time, then confirms. We do NOT use the
// shared 3-step booking labels because reschedule
// does not have a "vehicle & services" step — those
// are immutable.
const stepDefs = computed(() => [
    { key: 'time', label: t('customer_portal.actions.reschedule_step_2') },
    { key: 'review', label: t('customer_portal.actions.reschedule_step_3') },
]);

const currentStep = ref(0);
const form = ref({
    centerId: props.appointment.center_id,
    date: null,
    scheduledAt: null,
});

const selectedCenter = computed(
    () => props.centers.find((c) => c.id === form.value.centerId) || null
);

const minDateStr = computed(() => {
    const d = new Date();
    d.setHours(d.getHours() + props.minHoursBefore);
    return d.toISOString().slice(0, 10);
});
const maxDateStr = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + 14);
    return d.toISOString().slice(0, 10);
});

const slots = ref([]);
const loadingSlots = ref(false);
const submitting = ref(false);
const bookingError = ref(null);

async function fetchSlots() {
    if (!form.value.centerId || !form.value.date) {
        slots.value = [];
        return;
    }
    loadingSlots.value = true;
    try {
        const url = route('public.portal.appointments.rescheduleAvailability', {
            tenant: props.tenant.slug,
            id: props.appointment.id,
        });
        const params = new URLSearchParams();
        params.set('center_id', String(form.value.centerId));
        params.set('date', form.value.date);
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
    () => [form.value.centerId, form.value.date],
    () => {
        if (currentStep.value === 0) {
            form.value.scheduledAt = null;
            fetchSlots();
        }
    }
);

watch(currentStep, (val) => {
    if (val === 0) {
        if (!form.value.date) {
            form.value.date = minDateStr.value;
        }
        fetchSlots();
    }
});

const canAdvance = computed(() => {
    if (currentStep.value === 0) {
        return form.value.centerId !== null && form.value.scheduledAt !== null;
    }
    return true;
});

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
function formatDateTime(iso) {
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
    if (submitting.value) {
        return;
    }
    submitting.value = true;
    bookingError.value = null;
    router.post(
        route('public.portal.appointments.reschedule', {
            tenant: props.tenant.slug,
            id: props.appointment.id,
        }),
        {
            center_id: form.value.centerId,
            scheduled_at: form.value.scheduledAt,
        },
        {
            onError: (errors) => {
                submitting.value = false;
                const msg =
                    errors?.booking || Object.values(errors || {})[0] || 'Reschedule failed';
                bookingError.value = msg;
            },
            onFinish: () => {
                submitting.value = false;
            },
        }
    );
}
</script>
