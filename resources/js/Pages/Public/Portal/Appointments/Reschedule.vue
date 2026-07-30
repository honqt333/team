<template>
    <Head>
        <title>{{ t('customer_portal.actions.reschedule_title') }} — {{ tenant.site_name }}</title>
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
                class="max-w-3xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4"
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
                            {{ t('customer_portal.actions.reschedule_title') }}
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                            {{ t('customer_portal.actions.reschedule_subtitle') }}
                        </p>
                    </div>
                </div>
                <a
                    :href="backToShow"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    ‹
                </a>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">
            <div
                v-if="bookingError"
                class="rounded-2xl border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-900/20 p-4 text-sm text-rose-700 dark:text-rose-200"
            >
                {{ bookingError }}
            </div>

            <!-- Current appointment summary. The
                 customer always sees what they are
                 moving before they pick a new time. -->
            <section
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800"
            >
                <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-2">
                    {{ t('customer_portal.actions.reschedule_current_when') }}
                </h2>
                <div class="flex items-center justify-between gap-3" dir="ltr">
                    <div class="font-semibold">
                        {{ formatDateTime(appointment.scheduled_at) }}
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        {{ appointment.center_name }}
                    </div>
                </div>
                <ul class="mt-3 flex flex-wrap gap-2 text-xs">
                    <li
                        v-for="s in appointment.services"
                        :key="s.id"
                        class="px-2 py-1 rounded-full bg-slate-100 dark:bg-slate-800"
                    >
                        {{ s.name }}
                    </li>
                </ul>
            </section>

            <!-- Step 1: branch. The customer can keep
                 the same branch or pick another active
                 branch in the same tenant. -->
            <section
                v-show="currentStep === 0"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800"
            >
                <h2 class="text-lg font-bold mb-3">
                    {{ t('customer_portal.actions.reschedule_step_branch') }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                        <div
                            v-if="c.id === appointment.center_id"
                            class="text-xs text-slate-500 dark:text-slate-400 mt-1"
                        >
                            {{ t('customer_portal.actions.reschedule_same_center') }}
                        </div>
                    </button>
                </div>
            </section>

            <!-- Step 2: date + time. The slots come from
                 the same availability endpoint the
                 booking flow uses; the wizard never
                 lets the customer type a time. -->
            <section
                v-show="currentStep === 1"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800"
            >
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
                        @click="form.scheduledAt = slot"
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

            <!-- Step 3: review. -->
            <section
                v-show="currentStep === 2"
                class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800"
            >
                <h2 class="text-lg font-bold mb-3">
                    {{ t('customer_portal.actions.reschedule_step_review') }}
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
                            {{ t('customer_portal.booking.review_date') }}
                        </dt>
                        <dd class="font-semibold text-end" dir="ltr">
                            {{ formatDateTime(form.scheduledAt) }}
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- Navigation -->
            <div class="flex items-center justify-between gap-3">
                <button
                    type="button"
                    @click="prev"
                    :disabled="currentStep === 0"
                    class="px-4 py-2.5 rounded-lg text-sm font-semibold border border-slate-200 dark:border-slate-700 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-800"
                >
                    ‹
                </button>
                <button
                    v-if="currentStep < 2"
                    type="button"
                    @click="next"
                    :disabled="!canAdvance"
                    class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white disabled:opacity-40"
                    :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                >
                    ›
                </button>
                <button
                    v-else
                    type="button"
                    @click="submit"
                    :disabled="submitting"
                    class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white disabled:opacity-40"
                    :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                >
                    {{
                        submitting
                            ? t('customer_portal.actions.reschedule_submitting')
                            : t('customer_portal.actions.reschedule_submit')
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
    appointment: { type: Object, required: true },
    centers: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    minHoursBefore: { type: Number, default: 2 },
});

const { t: tRaw } = useI18n();
const page = usePage();
const isRtl = computed(() => props.locale?.is_rtl ?? (page.props.locale || 'ar') === 'ar');
const t = (key, params) => {
    const v = tRaw(key, params || {});
    return v === key ? key.split('.').pop() : v;
};

const isDark = ref(true);
const cssVars = computed(() => ({
    '--tenant-primary': props.tenant?.primary_color || '#4f46e5',
    '--tenant-secondary': props.tenant?.secondary_color || '#0ea5e9',
}));

const backToShow = computed(
    () => `/${props.tenant.slug}/portal/appointment/${props.appointment.id}`
);
const initial = computed(() => {
    const n = props.tenant?.site_name || '';
    return n.trim().charAt(0).toUpperCase() || '?';
});

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
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
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
        if (currentStep.value === 1) {
            form.value.scheduledAt = null;
            fetchSlots();
        }
    }
);

watch(currentStep, (val) => {
    if (val === 1) {
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
        return form.value.scheduledAt !== null;
    }
    return true;
});

function next() {
    bookingError.value = null;
    if (!canAdvance.value) {
        return;
    }
    if (currentStep.value < 2) {
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
