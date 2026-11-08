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

function runCounters() {
    const list = props.settings.stats_list || [];

    list.forEach((stat, i) => {
        const raw = String(stat.value ?? '');
        const num = parseFloat(raw.replace(/[^\d.]/g, '')) || 0;
        const suffix = raw.replace(/[\d.]/g, '');
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

    (props.settings.stats_list || []).forEach((_, i) => {
        counters.value[i] = '0';
    });

    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        (props.settings.stats_list || []).forEach((stat, i) => {
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
