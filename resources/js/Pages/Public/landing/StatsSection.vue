<script setup>
/**
 * Stats band.
 *
 * The counters animate from 0 to their final value when the band scrolls into
 * view. The animation is driven by rAF but it is a one-shot (1.8s) rather than
 * a permanent loop, so it costs nothing once it has finished.
 */
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
});

const counters = ref({});
let observer = null;
let frameId = null;

/**
 * Is this value an animated counter, or a label with a unit attached?
 *
 * "500", "+1M" and "1" are counters — they animate up.
 * "24/7", "ZATCA" and "SAR" are not — they are text, and animating them
 * produces nonsense ("24/7" would become "24/7" with the slash mangled into
 * the suffix, and "SAR" would count up from 0).
 *
 * A value is a counter only when it is ENTIRELY numeric (with an optional
 * sign, thousands separators and a trailing unit made of %/+/x characters).
 */
function isCounterValue(raw) {
    return /^[+-]?\d[\d,]*(\.\d+)?\s*[%+xX]?$/.test(raw.trim());
}

function runCounters() {
    const list = props.settings.stats_list || [];

    list.forEach((stat, i) => {
        const raw = String(stat.value ?? '');

        if (!isCounterValue(raw)) {
            // Pure text (e.g. "ZATCA", "24/7"): show it as-is, no animation.
            counters.value[i] = raw;
            return;
        }

        const num = parseFloat(raw.replace(/[^\d.]/g, '')) || 0;
        const suffix = raw.replace(/[\d.,\s]/g, '');
        const duration = 1800;
        const start = performance.now();

        const update = (now) => {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            counters.value[i] = Math.round(eased * num) + suffix;
            if (p < 1) frameId = requestAnimationFrame(update);
        };

        setTimeout(() => {
            frameId = requestAnimationFrame(update);
        }, i * 150);
    });
}

onMounted(() => {
    const el = document.getElementById('stats-section');
    if (!el) return;

    const list = props.settings.stats_list || [];

    /*
     * Seed the counters.
     *
     * Numeric values start at '0' and animate up when the band scrolls into
     * view. Values that do not start with a digit ("ZATCA", "SAR", "24/7")
     * are NOT counters — they must show their real value immediately, or the
     * band would display "0" for them until the observer fired.
     */
    list.forEach((stat, i) => {
        const raw = String(stat.value ?? '');
        counters.value[i] = isCounterValue(raw) ? '0' : raw;
    });

    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        list.forEach((stat, i) => {
            counters.value[i] = stat.value;
        });
        return;
    }

    observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                runCounters();
                observer.disconnect();
            }
        },
        { threshold: 0.3 }
    );
    observer.observe(el);
});

onUnmounted(() => {
    if (observer) observer.disconnect();
    if (frameId) cancelAnimationFrame(frameId);
});
</script>

<template>
    <section v-if="settings.stats_enabled" id="stats-section" class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div
                    v-for="(s, i) in settings.stats_list || []"
                    :key="i"
                    class="stat-item sr"
                    :style="`--d:${i * 150}ms`"
                >
                    <div class="stat-val">{{ counters[i] ?? s.value }}</div>
                    <div class="stat-lbl">{{ isRtl ? s.label_ar : s.label_en }}</div>
                </div>
            </div>
        </div>
    </section>
</template>
