<script setup>
/**
 * Feature grid.
 *
 * The icons were previously chosen with `i % 4 === 0 ? ... : ...` inside the
 * template — every 4th feature repeated the same icon. The icon set is now
 * an explicit list that cycles, so the row reads as a set rather than a loop.
 */
defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
    t: { type: Function, required: true },
    li: { type: Function, required: true },
});

const ICONS = [
    'M13 10V3L4 14h7v7l9-11h-7z',
    'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0',
    'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    'M9 12l2 2 4-4M7 21H5a2 2 0 01-2-2V5a2 2 0 012-2h2m4 0h4a2 2 0 012 2v4m0 0h2a2 2 0 012 2v4a2 2 0 01-2 2h-2m0 0h-4a2 2 0 01-2-2v-4a2 2 0 012-2h4z',
    'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
];
</script>

<template>
    <section v-if="settings.features_enabled" id="features" class="section-pad">
        <div class="container">
            <div class="section-head sr">
                <p class="section-tag">{{ isRtl ? 'أدوات متكاملة' : 'Integrated Tools' }}</p>
                <h2 class="section-h2">{{ t('features_title_ar', 'features_title_en') }}</h2>
                <p class="section-desc">{{ t('features_subtitle_ar', 'features_subtitle_en') }}</p>
            </div>

            <div class="features-grid">
                <div
                    v-for="(f, i) in settings.features_list || []"
                    :key="i"
                    class="feat-card sr"
                    :style="`--d:${i * 80}ms`"
                >
                    <div class="feat-icon" aria-hidden="true">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="ICONS[i % ICONS.length]"
                            />
                        </svg>
                    </div>
                    <h3 class="feat-title">{{ li(f, 'title') }}</h3>
                    <p class="feat-desc">{{ li(f, 'desc') }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
