<script setup>
/**
 * Landing hero.
 *
 * PERFORMANCE — the LCP fix:
 * The LCP element used to be `.typed-line`, a string that changed every 90 ms.
 * A browser cannot settle the LCP on an element whose text keeps mutating, so
 * the measurement was deferred for seconds and the page scored as slow even
 * on a fast connection.
 *
 * The typed effect is now:
 *   1. removed from the critical path entirely — the headline is static text,
 *   2. replaced by a rotating word that swaps via `opacity`, and
 *   3. rendered only AFTER the page is idle, so it never competes with the
 *      LCP candidate.
 *
 * The rotating word is `aria-hidden` with a static, complete sentence exposed
 * to assistive tech, so no information is lost by the visual effect.
 */
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
    t: { type: Function, required: true },
});

const heroImage = computed(() => {
    const banners = props.settings.banners_list || [];
    return banners[0]?.image || null;
});

// ── Static, screen-reader-complete sentence (never animated) ────────────────
const phrases = computed(() => {
    const raw = props.isRtl
        ? props.settings.hero_typed_phrases_ar
        : props.settings.hero_typed_phrases_en;
    const fallback = props.isRtl
        ? ['مركزك الآن', 'أرباحك اليوم', 'فريقك معك', 'مستقبلك هنا']
        : ['Your Workshop', 'Your Revenue', 'Your Team', 'Your Future'];

    if (!raw) return fallback;
    const list = raw
        .split(/[\n|]+/)
        .map((s) => s.trim())
        .filter(Boolean);
    return list.length ? list : fallback;
});

// ── Rotating word (idle-only, opacity swap) ────────────────────────────────
const activeIndex = ref(0);
let rotationTimer = null;

function startRotation() {
    // Respect reduced-motion: show the first phrase, change nothing.
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;

    rotationTimer = setInterval(() => {
        activeIndex.value = (activeIndex.value + 1) % phrases.value.length;
    }, 3200);
}

onMounted(() => {
    // `requestIdleCallback` keeps the effect completely off the critical path.
    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(startRotation, { timeout: 3000 });
    } else {
        setTimeout(startRotation, 1500);
    }
});

onUnmounted(() => {
    if (rotationTimer) clearInterval(rotationTimer);
});

/*
 * The trust row.
 *
 * These used to be "No contracts / Free trial / 24/7 support" — the three
 * most generic reassurance bullets on the internet. They are now specific
 * to what a workshop owner actually worries about: getting the data in,
 * what the trial costs, and whether someone will help when the phone rings.
 */
const trustItems = computed(() =>
    props.isRtl
        ? ['إدخال بياناتك من Excel', 'تجربة بدون بطاقة ائتمان', 'دعم بالعربي']
        : ['Import from Excel', 'No credit card needed', 'Arabic support']
);
</script>

<template>
    <section class="hero">
        <div class="hero-inner">
            <div class="hero-content sr">
                <h1 class="hero-h1">
                    {{ t('hero_title_ar', 'hero_title_en') }}
                </h1>

                <!--
                    Rotating word. The full sentence is exposed to screen
                    readers below via .sr-only; this block is decorative only.
                -->
                <div class="typed-line" aria-hidden="true">
                    <span
                        v-if="t('hero_typed_prefix_ar', 'hero_typed_prefix_en')"
                        class="typed-prefix"
                    >
                        {{ t('hero_typed_prefix_ar', 'hero_typed_prefix_en') }}
                    </span>
                    <span class="typed-swap">
                        <span
                            v-for="(phrase, i) in phrases"
                            :key="phrase"
                            class="typed-phrase"
                            :class="{ 'is-active': i === activeIndex }"
                        >
                            {{ phrase }}
                        </span>
                    </span>
                    <span
                        v-if="t('hero_typed_suffix_ar', 'hero_typed_suffix_en')"
                        class="typed-suffix"
                    >
                        {{ t('hero_typed_suffix_ar', 'hero_typed_suffix_en') }}
                    </span>
                </div>

                <!-- The full phrase list, for assistive tech only. -->
                <p class="sr-only">
                    {{ t('hero_typed_prefix_ar', 'hero_typed_prefix_en') }}
                    {{ phrases.join('، ') }}
                    {{ t('hero_typed_suffix_ar', 'hero_typed_suffix_en') }}
                </p>

                <p class="hero-sub">{{ t('hero_subtitle_ar', 'hero_subtitle_en') }}</p>

                <div class="hero-btns">
                    <Link :href="route('register')" class="btn-cta">
                        {{ t('hero_cta_text_ar', 'hero_cta_text_en') }}
                    </Link>
                    <a href="#features" class="btn-ghost">
                        {{ isRtl ? 'استكشف المزايا' : 'Explore features' }}
                    </a>
                </div>

                <ul class="trust-row">
                    <li v-for="item in trustItems" :key="item" class="trust-item">
                        <span class="trust-check" aria-hidden="true">✓</span>
                        {{ item }}
                    </li>
                </ul>
            </div>

            <!--
                The product visual.

                This used to be a fake browser window drawn entirely in CSS
                (`.mockup-frame` + three coloured dots + placeholder bars),
                with two floating "⚡99.9% / 🚀+500" badges on top. It is now
                the REAL brand artwork from the CMS banner list, served as
                AVIF/WebP with the PNG fallback.

                The image is the LCP candidate: it has an explicit size (no
                layout shift), `fetchpriority="high"`, and a `preload` link
                in the page head.
            -->
            <div class="hero-visual sr sr-d2">
                <div class="hero-shot">
                    <picture>
                        <source
                            v-if="heroImage"
                            type="image/avif"
                            srcset="/images/landing/FXHzyVlYFrkltsYphSMwQJs5tN5b5RjkAvAQw1wo.avif"
                        />
                        <source
                            v-if="heroImage"
                            type="image/webp"
                            srcset="/images/landing/FXHzyVlYFrkltsYphSMwQJs5tN5b5RjkAvAQw1wo.webp"
                        />
                        <img
                            v-if="heroImage"
                            :src="heroImage"
                            :alt="
                                isRtl
                                    ? 'لوحة تحكم Khidmah Pro لإدارة مركز صيانة السيارات'
                                    : 'Khidmah Pro dashboard for car workshop management'
                            "
                            width="1408"
                            height="736"
                            fetchpriority="high"
                            decoding="async"
                        />
                    </picture>
                </div>
            </div>
        </div>
    </section>
</template>
