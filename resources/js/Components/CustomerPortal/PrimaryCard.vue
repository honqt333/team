<template>
    <!--
        PrimaryCard — the single most-important card on the
        dashboard. The page places this ABOVE all other content
        and the customer always sees their next interaction
        (upcoming appointment, active work order, or a big
        "book now" CTA) at the top of the page.

        The card has three shapes:
          1. Upcoming appointment — date, branch, status, and
             two CTAs ("view", "edit/reschedule").
          2. Active work order — status, expected end date, and
             a friendly progress bar.
          3. Empty state — a large "book now" CTA.

        The component does NOT route to any backend endpoint;
        the parent passes the `viewHref` and `editHref` it
        computed for the current data shape.
    -->
    <section
        class="relative overflow-hidden rounded-[1.75rem] p-6 text-white shadow-[0_24px_70px_-30px_rgba(15,23,42,0.55)] sm:p-8"
        :style="{
            background:
                'linear-gradient(135deg, var(--tenant-primary, #2563eb) 0%, color-mix(in srgb, var(--tenant-primary, #2563eb) 70%, var(--tenant-secondary, #0ea5e9)) 100%)',
        }"
    >
        <!--
            Subtle pattern. Inline SVG so we don't pull in
            a new dependency. Opacity is locked low so the
            text stays readable.
        -->
        <svg
            class="absolute -end-12 -top-12 w-48 h-48 opacity-15 pointer-events-none"
            viewBox="0 0 200 200"
            fill="none"
            stroke="currentColor"
            aria-hidden="true"
        >
            <circle cx="100" cy="100" r="80" stroke-width="2" />
            <circle cx="100" cy="100" r="50" stroke-width="2" />
            <circle cx="100" cy="100" r="20" stroke-width="2" />
        </svg>

        <div class="relative">
            <p class="text-xs font-semibold uppercase tracking-wider opacity-80">
                {{ kicker }}
            </p>

            <h2 class="mt-2 max-w-2xl text-2xl font-black leading-tight sm:text-4xl">
                {{ title }}
            </h2>

            <p v-if="subtitle" class="mt-3 max-w-2xl text-sm leading-7 opacity-85 sm:text-base">
                {{ subtitle }}
            </p>

            <div
                v-if="kind === 'appointment'"
                class="mt-6 grid max-w-xl grid-cols-2 gap-4 rounded-2xl bg-white/10 p-4 text-sm backdrop-blur-sm"
            >
                <div>
                    <p class="opacity-70 text-[10px] uppercase tracking-wider">
                        {{ whenLabel }}
                    </p>
                    <p class="font-semibold mt-0.5" dir="ltr">{{ whenValue }}</p>
                </div>
                <div>
                    <p class="opacity-70 text-[10px] uppercase tracking-wider">
                        {{ branchLabel }}
                    </p>
                    <p class="font-semibold mt-0.5">{{ branchValue }}</p>
                </div>
            </div>

            <div v-else-if="kind === 'work_order'" class="mt-5">
                <p class="text-xs opacity-80 mb-1">
                    {{ progressLabel }}
                </p>
                <div
                    class="h-2 rounded-full bg-white/25 overflow-hidden"
                    role="progressbar"
                    :aria-valuenow="progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div
                        class="h-full bg-white transition-all"
                        :style="{ width: progress + '%' }"
                    />
                </div>
                <p class="text-xs opacity-90 mt-1.5">
                    {{ progressCaption }}
                </p>
            </div>

            <div v-if="ctaPrimary" class="mt-6 flex flex-wrap gap-2">
                <a
                    v-if="ctaPrimary.href"
                    :href="ctaPrimary.href"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-white px-5 py-3 text-sm font-extrabold text-slate-900 shadow-sm hover:bg-slate-50"
                >
                    {{ ctaPrimary.label }}
                </a>
                <a
                    v-if="ctaSecondary && ctaSecondary.href"
                    :href="ctaSecondary.href"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-white/30 px-5 py-3 text-sm font-bold text-white hover:bg-white/10"
                >
                    {{ ctaSecondary.label }}
                </a>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    // What kind of content the card shows. The component
    // shape differs per kind, so the parent passes one of
    // three values and supplies the data for that shape.
    //   appointment: shows when + branch + 2 CTAs
    //   work_order:  shows a progress bar
    //   empty:       shows a single big "book now" CTA
    kind: {
        type: String,
        default: 'empty',
        validator: (v) => ['appointment', 'work_order', 'empty'].includes(v),
    },
    // Shared labels.
    kicker: { type: String, default: '' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    // Appointment shape.
    whenLabel: { type: String, default: '' },
    whenValue: { type: String, default: '' },
    branchLabel: { type: String, default: '' },
    branchValue: { type: String, default: '' },
    ctaPrimary: { type: Object, default: null },
    ctaSecondary: { type: Object, default: null },
    // Work order shape.
    progressLabel: { type: String, default: '' },
    progressCaption: { type: String, default: '' },
    progressValue: { type: Number, default: 0 },
});

// Cap progress to [0, 100] so an out-of-range value
// can't blow out the layout.
const progress = computed(() => Math.max(0, Math.min(100, props.progressValue || 0)));
</script>
