<template>
    <Head>
        <title>{{ t('customer_portal.booking.title') }} — {{ tenant.site_name }}</title>
    </Head>

    <div
        :dir="isRtl ? 'rtl' : 'ltr'"
        :data-theme="isDark ? 'dark' : 'light'"
        :style="cssVars"
        class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <header
            class="sticky top-0 z-40 backdrop-blur bg-white/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800"
        >
            <div
                class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="h-9 w-9 rounded-xl flex items-center justify-center text-white font-bold flex-shrink-0"
                        :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                    >
                        {{ initial }}
                    </div>
                    <div class="min-w-0">
                        <h1 class="font-bold text-base truncate">
                            {{ t('customer_portal.booking.title') }}
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                            {{ t('customer_portal.booking.subtitle') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="backToDashboard"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        {{ t('customer_portal.booking.back_to_dashboard') }}
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 py-6 sm:py-10">
            <!-- Stepper. The bar is informational —
                 nothing is clickable. The Next button
                 is what advances the user, because the
                 time step needs server data before
                 it can show anything. -->
            <nav
                class="mb-6 flex items-center gap-2 text-xs sm:text-sm overflow-x-auto"
                aria-label="progress"
            >
                <span
                    v-for="(s, i) in steps"
                    :key="s.key"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full font-semibold whitespace-nowrap"
                    :class="
                        i < currentStep
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                            : i === currentStep
                              ? 'text-white'
                              : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
                    "
                    :style="
                        i === currentStep
                            ? { backgroundColor: 'var(--tenant-primary, #4f46e5)' }
                            : {}
                    "
                >
                    <span class="text-[10px]">{{ i + 1 }}</span>
                    <span>{{ s.label }}</span>
                </span>
            </nav>

            <div
                v-if="bookingError"
                class="mb-4 rounded-2xl border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-900/20 p-4 text-sm text-rose-700 dark:text-rose-200"
            >
                {{ bookingError }}
            </div>

            <!-- Step 1: center -->
            <section
                v-show="currentStep === 0"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800"
            >
                <h2 class="text-lg font-bold mb-1">
                    {{ t('customer_portal.booking.select_center') }}
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                    {{ t('customer_portal.booking.step_center') }}
                </p>
                <div
                    v-if="centers.length === 0"
                    class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-6 text-center text-sm text-slate-500 dark:text-slate-400"
                >
                    {{ t('customer_portal.booking.no_centers') }}
                </div>
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button
                        v-for="c in centers"
                        :key="c.id"
                        type="button"
                        @click="selectCenter(c)"
                        class="text-left p-4 rounded-2xl border transition"
                        :class="
                            form.centerId === c.id
                                ? 'border-transparent ring-2'
                                : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                        "
                        :style="
                            form.centerId === c.id
                                ? {
                                      backgroundColor:
                                          'color-mix(in srgb, var(--tenant-primary, #4f46e5) 8%, transparent)',
                                      '--tw-ring-color': 'var(--tenant-primary, #4f46e5)',
                                  }
                                : {}
                        "
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-base">{{ c.name }}</span>
                            <span
                                v-if="c.is_main"
                                class="text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full"
                                :style="{
                                    backgroundColor:
                                        'color-mix(in srgb, var(--tenant-primary, #4f46e5) 12%, transparent)',
                                    color: 'var(--tenant-primary, #4f46e5)',
                                }"
                            >
                                ★
                            </span>
                        </div>
                    </button>
                </div>
            </section>

            <!-- Step 2: vehicle -->
            <section
                v-show="currentStep === 1"
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
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button
                        v-for="v in vehicles"
                        :key="v.id"
                        type="button"
                        @click="selectVehicle(v)"
                        class="text-left p-4 rounded-2xl border transition"
                        :class="
                            form.vehicleId === v.id
                                ? 'border-transparent ring-2'
                                : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                        "
                        :style="
                            form.vehicleId === v.id
                                ? {
                                      backgroundColor:
                                          'color-mix(in srgb, var(--tenant-primary, #4f46e5) 8%, transparent)',
                                      '--tw-ring-color': 'var(--tenant-primary, #4f46e5)',
                                  }
                                : {}
                        "
                    >
                        <div class="font-bold" dir="ltr">{{ v.plate_number }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ v.label }}
                        </div>
                    </button>
                </div>
            </section>

            <!-- Step 3: services -->
            <section
                v-show="currentStep === 2"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800"
            >
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
                                          'color-mix(in srgb, var(--tenant-primary, #4f46e5) 8%, transparent)',
                                      '--tw-ring-color': 'var(--tenant-primary, #4f46e5)',
                                  }
                                : {}
                        "
                    >
                        <input
                            type="checkbox"
                            :value="s.id"
                            v-model="form.serviceIds"
                            class="mt-1 w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
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
                <p
                    v-if="estimatedDuration"
                    class="mt-4 text-sm font-semibold"
                    :style="{ color: 'var(--tenant-primary, #4f46e5)' }"
                    dir="ltr"
                >
                    {{ t('customer_portal.booking.review_duration') }}:
                    {{
                        t('customer_portal.booking.duration_minutes', { count: estimatedDuration })
                    }}
                </p>
            </section>

            <!-- Step 4: date + time -->
            <section
                v-show="currentStep === 3"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800"
            >
                <h2 class="text-lg font-bold mb-1">
                    {{ t('customer_portal.booking.pick_date') }}
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                    {{ t('customer_portal.booking.step_time') }}
                </p>

                <div class="flex flex-wrap items-center gap-3 mb-5">
                    <label class="text-sm font-medium">
                        {{ t('customer_portal.booking.pick_date') }}
                    </label>
                    <input
                        type="date"
                        :min="minDateStr"
                        :max="maxDateStr"
                        v-model="form.date"
                        class="rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-sm"
                    />
                </div>

                <h3 class="text-sm font-semibold mb-2">
                    {{ t('customer_portal.booking.pick_time') }}
                </h3>

                <p v-if="loadingSlots" class="text-sm text-slate-500 dark:text-slate-400">
                    {{ t('customer_portal.booking.loading_slots') }}
                </p>
                <p
                    v-else-if="slots.length === 0"
                    class="text-sm text-slate-500 dark:text-slate-400"
                >
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
                                ? { backgroundColor: 'var(--tenant-primary, #4f46e5)' }
                                : {}
                        "
                        dir="ltr"
                    >
                        {{ formatSlotTime(slot) }}
                    </button>
                </div>
            </section>

            <!-- Step 5: review -->
            <section
                v-show="currentStep === 4"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 sm:p-6 border border-slate-200 dark:border-slate-800"
            >
                <h2 class="text-lg font-bold mb-4">
                    {{ t('customer_portal.booking.review_title') }}
                </h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.review_branch') }}
                        </dt>
                        <dd class="font-semibold text-end">
                            {{ selectedCenter?.name }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.review_vehicle') }}
                        </dt>
                        <dd class="font-semibold text-end" dir="ltr">
                            {{ selectedVehicle?.plate_number }}
                            <div class="text-xs text-slate-500 dark:text-slate-400 font-normal">
                                {{ selectedVehicle?.label }}
                            </div>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.review_services') }}
                        </dt>
                        <dd class="font-semibold text-end">
                            <ul class="space-y-0.5">
                                <li v-for="s in selectedServices" :key="s.id">
                                    {{ s.name }}
                                </li>
                            </ul>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.review_date') }}
                        </dt>
                        <dd class="font-semibold text-end" dir="ltr">
                            {{ formatSlotDateTime(form.scheduledAt) }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-500 dark:text-slate-400">
                            {{ t('customer_portal.booking.review_duration') }}
                        </dt>
                        <dd class="font-semibold text-end" dir="ltr">
                            {{
                                t('customer_portal.booking.duration_minutes', {
                                    count: estimatedDuration,
                                })
                            }}
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- Navigation buttons -->
            <div class="mt-6 flex items-center justify-between gap-3">
                <button
                    type="button"
                    @click="prev"
                    :disabled="currentStep === 0"
                    class="px-4 py-2.5 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-700 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    ‹ {{ tPrevLabel }}
                </button>

                <button
                    v-if="currentStep < steps.length - 1"
                    type="button"
                    @click="next"
                    :disabled="!canAdvance"
                    class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white disabled:opacity-40"
                    :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                >
                    {{ tNextLabel }} ›
                </button>

                <button
                    v-else
                    type="button"
                    @click="submit"
                    :disabled="submitting || !canSubmit"
                    class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white disabled:opacity-40"
                    :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                >
                    {{
                        submitting
                            ? t('customer_portal.booking.submitting')
                            : t('customer_portal.booking.submit')
                    }}
                </button>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    centers: { type: Array, default: () => [] },
    vehicles: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});

const { t: tRaw } = useI18n();
const page = usePage();
const isRtl = computed(() => props.locale?.is_rtl ?? (page.props.locale || 'ar') === 'ar');
const t = (key, params) => {
    const v = tRaw(key, params || {});
    return v === key ? key.split('.').pop() : v;
};

const isDark = ref(true);
const csrf = computed(() => {
    if (typeof document === 'undefined') {
        return '';
    }
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
});

const cssVars = computed(() => ({
    '--tenant-primary': props.tenant?.primary_color || '#4f46e5',
    '--tenant-secondary': props.tenant?.secondary_color || '#0ea5e9',
}));

const backToDashboard = computed(() => `/${props.tenant.slug}/portal/dashboard`);

const steps = computed(() => [
    { key: 'center', label: t('customer_portal.booking.step_center') },
    { key: 'vehicle', label: t('customer_portal.booking.step_vehicle') },
    { key: 'services', label: t('customer_portal.booking.step_services') },
    { key: 'time', label: t('customer_portal.booking.step_time') },
    { key: 'review', label: t('customer_portal.booking.step_review') },
]);

const currentStep = ref(0);

const form = ref({
    centerId: null,
    vehicleId: null,
    serviceIds: [],
    date: null,
    scheduledAt: null,
});

const selectedCenter = computed(
    () => props.centers.find((c) => c.id === form.value.centerId) || null
);
const selectedVehicle = computed(
    () => props.vehicles.find((v) => v.id === form.value.vehicleId) || null
);
const availableServices = computed(() =>
    form.value.centerId
        ? props.services.filter((s) => s.center_id === form.value.centerId)
        : props.services
);
const selectedServices = computed(() =>
    props.services.filter((s) => form.value.serviceIds.includes(s.id))
);
const estimatedDuration = computed(() =>
    selectedServices.value.reduce((acc, s) => acc + (s.duration_minutes || 0), 0)
);

const minDateStr = computed(() => {
    // 1 hour from now, formatted YYYY-MM-DD in the
    // server's timezone. The server's MIN_LOOKAHEAD
    // gate is the source of truth; this is just the
    // floor on the date picker.
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
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (r.ok) {
            const data = await r.json();
            slots.value = Array.isArray(data?.slots) ? data.slots : [];
            if (data?.duration_minutes) {
                // The server is the authority on duration.
                // We do not write this into form — the
                // server recomputes it on submit. The
                // UI's estimatedDuration is for
                // display only.
            }
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
        if (currentStep.value === 3) {
            form.value.scheduledAt = null;
            fetchSlots();
        }
    },
    { deep: true }
);

watch(currentStep, (val) => {
    if (val === 3) {
        if (!form.value.date) {
            form.value.date = minDateStr.value;
        }
        fetchSlots();
    }
});

const canAdvance = computed(() => {
    if (currentStep.value === 0) {
        return form.value.centerId !== null;
    }
    if (currentStep.value === 1) {
        return form.value.vehicleId !== null;
    }
    if (currentStep.value === 2) {
        return form.value.serviceIds.length > 0;
    }
    if (currentStep.value === 3) {
        return form.value.scheduledAt !== null;
    }
    return true;
});
const canSubmit = computed(() => canAdvance.value);
const submitting = ref(false);
const bookingError = ref(null);

function next() {
    bookingError.value = null;
    if (!canAdvance.value) {
        return;
    }
    if (currentStep.value < steps.value.length - 1) {
        currentStep.value += 1;
    }
}
function prev() {
    bookingError.value = null;
    if (currentStep.value > 0) {
        currentStep.value -= 1;
    }
}

const tPrevLabel = computed(() => {
    const labels = [
        t('customer_portal.booking.back_to_dashboard'),
        t('customer_portal.booking.step_center'),
        t('customer_portal.booking.step_vehicle'),
        t('customer_portal.booking.step_services'),
        t('customer_portal.booking.step_time'),
    ];
    return labels[currentStep.value] || '';
});
const tNextLabel = computed(() => {
    const labels = [
        t('customer_portal.booking.step_vehicle'),
        t('customer_portal.booking.step_services'),
        t('customer_portal.booking.step_time'),
        t('customer_portal.booking.step_review'),
        t('customer_portal.booking.submit'),
    ];
    return labels[currentStep.value] || '';
});

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

const initial = computed(() => {
    const n = props.tenant?.site_name || '';
    return n.trim().charAt(0).toUpperCase() || '?';
});

function submit() {
    if (!canSubmit.value || submitting.value) {
        return;
    }
    submitting.value = true;
    bookingError.value = null;
    router.post(
        route('public.portal.appointments.store', { tenant: props.tenant.slug }),
        {
            center_id: form.value.centerId,
            vehicle_id: form.value.vehicleId,
            service_ids: form.value.serviceIds,
            scheduled_at: form.value.scheduledAt,
        },
        {
            onError: (errors) => {
                submitting.value = false;
                const msg = errors?.booking || Object.values(errors || {})[0] || 'Booking failed';
                bookingError.value = msg;
            },
            onFinish: () => {
                submitting.value = false;
            },
        }
    );
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem('cp-dark');
        if (stored !== null) {
            isDark.value = stored === '1';
        } else if (window.matchMedia) {
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        document.documentElement.classList.toggle('dark', isDark.value);
    }
});
</script>
