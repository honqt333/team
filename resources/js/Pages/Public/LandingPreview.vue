<script setup>
/**
 * Public landing page.
 *
 * REBUILD NOTES (Phase 1 + 2 of docs/landing-preview-rebuild-plan.md)
 *
 * This file used to be 2,962 lines: script + template + 1,680 lines of CSS
 * in one SFC. Lighthouse put the whole page at 49% performance with 1,130 ms
 * of total blocking time, and the single chunk spent 2,685 ms on the main
 * thread.
 *
 * What changed:
 *   1. Every section is now its own component in ./landing/ and is loaded with
 *      `defineAsyncComponent`, so the browser only parses a section when it
 *      is needed.
 *   2. The CSS moved to resources/css/landing.css and is imported once.
 *   3. The particle canvas, the cursor-glow follower and the animated aurora
 *      are gone (they were the main-thread cost, and the clearest AI-template
 *      tell). The background is now a static gradient.
 *   4. The hero's typed line is no longer the LCP element — the LCP is now a
 *      real AVIF/WebP product image.
 *   5. Accessibility: <main> landmark, real heading order, labelled form
 *      fields, named icon buttons and social links, and a fixed contrast ratio.
 */
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch, defineAsyncComponent } from 'vue';
import { useLanding } from './landing/useLanding';
import { getSocialIcon } from './landing/socialIcons';
import { darkenForWhiteText } from '@/Support/colourHelpers';

import '../../../css/landing.css';

// ── Eager: above-the-fold, must be in the first paint ──────────────────────
import LandingHeader from './landing/LandingHeader.vue';
import HeroSection from './landing/HeroSection.vue';

// ── Lazy: below the fold, parsed only when needed ──────────────────────────
const FeaturesSection = defineAsyncComponent(() => import('./landing/FeaturesSection.vue'));
const AudienceSection = defineAsyncComponent(() => import('./landing/AudienceSection.vue'));
const HowItWorksSection = defineAsyncComponent(() => import('./landing/HowItWorksSection.vue'));
const StatsSection = defineAsyncComponent(() => import('./landing/StatsSection.vue'));
const PricingSection = defineAsyncComponent(() => import('./landing/PricingSection.vue'));
const FaqSection = defineAsyncComponent(() => import('./landing/FaqSection.vue'));
const ClientsSection = defineAsyncComponent(() => import('./landing/ClientsSection.vue'));
const TestimonialsSection = defineAsyncComponent(() => import('./landing/TestimonialsSection.vue'));
const ContactSection = defineAsyncComponent(() => import('./landing/ContactSection.vue'));
const CtaSection = defineAsyncComponent(() => import('./landing/CtaSection.vue'));
const LandingFooter = defineAsyncComponent(() => import('./landing/LandingFooter.vue'));
const LegalModal = defineAsyncComponent(() => import('./landing/LegalModal.vue'));

const props = defineProps({
    settings: { type: Object, required: true },
    plans: { type: Array, default: () => [] },
    isAdminPreview: { type: Boolean, default: false },
    /** The canonical URL of this page (built server-side). */
    canonicalUrl: { type: String, default: '' },
    /** The absolute URL of the 1200×630 social share card. */
    ogImageUrl: { type: String, default: '' },
    /** The schema.org JSON-LD graph (built server-side). */
    jsonLd: { type: Object, default: null },
});

/**
 * The CSS custom properties for the page.
 *
 * `--primary` is the brand colour from the CMS. `--primary-strong` is the
 * same colour darkened until WHITE text on top of it clears 4.5:1 — the
 * primary call-to-action buttons carry white text, and a light brand colour
 * (a gold `#e5a448`, for instance) put that text at 2.15:1.
 */
const cssVars = computed(() => {
    const primary = settings.value.primary_color || '#4f46e5';

    return {
        '--primary': primary,
        '--primary-strong': darkenForWhiteText(primary, 4.5),
    };
});

// ── Live settings (synced from the CMS editor via postMessage) ─────────────
const liveSettings = ref({ ...(props.settings || {}) });
const settings = computed(() => liveSettings.value);

/**
 * Whether there is a real testimonial to show.
 *
 * The check lives HERE and not only inside the section component: the
 * section is code-split, so a `v-if` inside it still fetches the chunk.
 * Without a real entry there is nothing to render, and the request is
 * pure waste on a page that has to stay fast.
 */
const hasTestimonials = computed(() =>
    (settings.value.testimonials_list || []).some(
        (entry) =>
            entry && entry.name && ((entry.quote_ar || '').trim() || (entry.quote_en || '').trim())
    )
);

watch(
    () => props.settings,
    (val) => {
        if (val) liveSettings.value = { ...liveSettings.value, ...val };
    },
    { deep: true }
);

const {
    isRtl,
    isDark,
    isScrolled,
    isMobileMenuOpen,
    activeLegalPage,
    langLoading,
    t,
    li,
    toggleTheme,
} = useLanding(settings);

// ── Contact form ───────────────────────────────────────────────────────────
const contactForm = useForm({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});
const contactSuccess = ref(false);

function submitContact() {
    contactForm.post(route('public.landing.contact'), {
        preserveScroll: true,
        onSuccess: () => {
            contactForm.reset();
            contactSuccess.value = true;
            setTimeout(() => (contactSuccess.value = false), 5000);
        },
    });
}

// ── Language switch ────────────────────────────────────────────────────────
function switchLang() {
    if (langLoading.value) return;
    langLoading.value = true;
    const next = isRtl.value ? 'en' : 'ar';
    localStorage.setItem('app.locale', next);
    router.post(
        route('locale.set'),
        { locale: next },
        {
            onFinish: () => {
                langLoading.value = false;
                window.location.reload();
            },
        }
    );
}

// ── CMS live preview sync ──────────────────────────────────────────────────
function handlePostMessage(event) {
    if (event.data?.type === 'CARAG_PREVIEW_UPDATE' && event.data?.settings) {
        liveSettings.value = { ...liveSettings.value, ...event.data.settings };
    }
}

// ── Inject third-party scripts from the CMS ─────────────────────────────────
function injectScripts(html, target = document.body) {
    if (!html) return;
    const holder = document.createElement('div');
    holder.innerHTML = html;
    holder.querySelectorAll('script').forEach((original) => {
        const script = document.createElement('script');
        script.text = original.text;
        if (original.src) script.src = original.src;
        original.attributes.forEach((attr) => {
            if (attr.name !== 'src' && attr.name !== 'text') {
                script.setAttribute(attr.name, attr.value);
            }
        });
        target.appendChild(script);
    });
}

onMounted(() => {
    window.addEventListener('message', handlePostMessage);

    if (settings.value.chatbot_enabled && settings.value.chatbot_script) {
        injectScripts(settings.value.chatbot_script);
    }

    injectScripts(settings.value.scripts_header, document.head);
    injectScripts(settings.value.scripts_footer, document.body);
});

onUnmounted(() => {
    window.removeEventListener('message', handlePostMessage);
});
</script>

<template>
    <Head>
        <title>{{ t('title_ar', 'title_en') }}</title>
        <meta name="description" :content="t('description_ar', 'description_en')" />
        <meta v-if="settings.keywords" name="keywords" :content="settings.keywords" />
        <meta property="og:title" :content="t('title_ar', 'title_en')" />
        <meta property="og:description" :content="t('description_ar', 'description_en')" />
        <!--
            The share image is a dedicated 1200×630 JPEG, generated from the
            same headline the page shows (`npm run og:image`).

            It used to point at the optimised WebP banner, which produced
            an EMPTY card on every platform: the Open Graph crawlers do
            not render WebP. The URL is absolute because a relative
            `og:image` is ignored as well.
        -->
        <meta property="og:image" :content="ogImageUrl" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:image:type" content="image/jpeg" />
        <meta property="og:type" content="website" />
        <meta property="og:locale" :content="isRtl ? 'ar_SA' : 'en_US'" />
        <meta property="og:locale:alternate" :content="isRtl ? 'en_US' : 'ar_SA'" />
        <!--
            The favicon is rendered by app.blade.php, which reads the CMS
            setting. A second <link rel="icon"> here would make the browser
            download two icons.
        -->

        <!--
            Canonical URL and language alternates.

            The page is served at both `/` and `/landing-preview`, so a
            canonical URL is what tells the search engines which one to
            index. The alternates declare that the same page serves Arabic
            and English.
        -->
        <link rel="canonical" :href="canonicalUrl" />
        <link rel="alternate" hreflang="ar" :href="canonicalUrl" />
        <link rel="alternate" hreflang="en" :href="canonicalUrl" />
        <link rel="alternate" hreflang="x-default" :href="canonicalUrl" />

        <!--
            Social share card. Without the Twitter tags the share falls back
            to a plain text link with no image.
        -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="t('title_ar', 'title_en')" />
        <meta name="twitter:description" :content="t('description_ar', 'description_en')" />
        <meta name="twitter:image" :content="ogImageUrl" />

        <!--
            The JSON-LD graph is rendered server-side by app.blade.php.
        -->

        <!--
            The hero image is the LCP candidate. Preloading the AVIF source
            means the browser starts fetching it before it discovers the
            <picture> element, which removes the discovery round-trip.
        -->
        <link
            rel="preload"
            as="image"
            type="image/avif"
            href="/images/landing/FXHzyVlYFrkltsYphSMwQJs5tN5b5RjkAvAQw1wo.avif"
            fetchpriority="high"
        />

        <!--
            Fonts are self-hosted and preloaded globally from app.blade.php
            (public/css/fonts.css). The old implementation pulled Cairo +
            Outfit with 7 weights each from Google Fonts — 14 font files,
            render-blocking, on a third-party origin.
        -->
    </Head>

    <div
        class="lp-root min-h-screen overflow-x-hidden"
        :data-theme="isDark ? 'dark' : 'light'"
        :dir="isRtl ? 'rtl' : 'ltr'"
        :style="cssVars"
    >
        <!-- Admin preview notice when maintenance mode is ON -->
        <div
            v-if="isAdminPreview && settings.maintenance_mode === '1'"
            class="sticky top-0 z-50 bg-gradient-to-r from-amber-600 to-yellow-600 text-white text-xs font-bold py-2.5 px-4 text-center shadow-lg flex items-center justify-center gap-2"
        >
            <span aria-hidden="true">⚠️</span>
            <span>{{ isRtl ? 'وضع المعاينة الإدارية نشط' : 'Admin preview mode is active' }}</span>
        </div>

        <!-- MAINTENANCE -->
        <div v-if="settings.maintenance_mode === '1' && !isAdminPreview" class="maint-screen">
            <div class="maint-box sr">
                <div class="maint-icon-ring" aria-hidden="true">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </div>
                <h1>{{ t('maintenance_title_ar', 'maintenance_title_en') }}</h1>
                <p>{{ t('maintenance_message_ar', 'maintenance_message_en') }}</p>
                <div class="maint-actions">
                    <button
                        type="button"
                        @click="toggleTheme"
                        class="maint-btn"
                        :aria-label="isDark ? 'Light mode' : 'Dark mode'"
                    >
                        {{ isDark ? '☀️' : '🌙' }}
                    </button>
                    <button type="button" @click="switchLang" class="maint-btn">
                        {{ isRtl ? 'EN' : 'ع' }}
                    </button>
                </div>
            </div>
        </div>

        <template v-if="settings.maintenance_mode !== '1' || isAdminPreview">
            <!-- ANNOUNCEMENT BAR -->
            <div
                v-if="settings.announcement_enabled"
                class="announcement-bar"
                :style="{
                    backgroundColor: settings.announcement_bg_color,
                    color: settings.announcement_text_color,
                }"
            >
                <div
                    class="container mx-auto px-4 py-2 text-center text-sm font-bold flex items-center justify-center gap-4"
                >
                    <span>
                        {{ isRtl ? settings.announcement_text_ar : settings.announcement_text_en }}
                    </span>
                    <a
                        v-if="settings.announcement_url"
                        :href="settings.announcement_url"
                        class="underline hover:opacity-80 transition-opacity"
                    >
                        {{ isRtl ? 'المزيد' : 'Learn More' }} →
                    </a>
                </div>
            </div>

            <LandingHeader
                :settings="settings"
                :is-rtl="isRtl"
                :is-dark="isDark"
                :is-scrolled="isScrolled"
                :is-mobile-menu-open="isMobileMenuOpen"
                :t="t"
                @toggle-theme="toggleTheme"
                @toggle-mobile="isMobileMenuOpen = !isMobileMenuOpen"
                @switch-lang="switchLang"
            />

            <!--
                <main> is the landmark the page was missing entirely
                (axe: landmark-one-main).
            -->
            <main>
                <HeroSection :settings="settings" :is-rtl="isRtl" :t="t" />

                <FeaturesSection
                    v-if="settings.features_enabled"
                    :settings="settings"
                    :is-rtl="isRtl"
                    :t="t"
                    :li="li"
                />
                <AudienceSection
                    v-if="settings.audience_enabled"
                    :settings="settings"
                    :is-rtl="isRtl"
                    :t="t"
                    :li="li"
                />
                <HowItWorksSection :settings="settings" :is-rtl="isRtl" />
                <StatsSection v-if="settings.stats_enabled" :settings="settings" :is-rtl="isRtl" />
                <PricingSection
                    v-if="settings.pricing_enabled"
                    :settings="settings"
                    :plans="plans"
                    :is-rtl="isRtl"
                    :t="t"
                />
                <FaqSection v-if="settings.faq_enabled" :settings="settings" :is-rtl="isRtl" />
                <TestimonialsSection
                    v-if="settings.testimonials_enabled && hasTestimonials"
                    :settings="settings"
                    :is-rtl="isRtl"
                    :t="t"
                    :li="li"
                />
                <ClientsSection
                    v-if="settings.testimonials_enabled"
                    :settings="settings"
                    :is-rtl="isRtl"
                />
                <ContactSection
                    v-if="settings.contact_enabled"
                    :settings="settings"
                    :is-rtl="isRtl"
                    :form="contactForm"
                    :success="contactSuccess"
                    @submit="submitContact"
                />
                <CtaSection :settings="settings" :is-rtl="isRtl" :t="t" />
            </main>

            <LandingFooter
                :settings="settings"
                :is-rtl="isRtl"
                :t="t"
                :get-social-icon="getSocialIcon"
                :active-legal-page="activeLegalPage"
                @open-legal="activeLegalPage = $event"
            />

            <LegalModal
                :settings="settings"
                :is-rtl="isRtl"
                :active-legal-page="activeLegalPage"
                @close="activeLegalPage = null"
            />

            <!-- Floating WhatsApp -->
            <a
                v-if="settings.whatsapp_floating_enabled && settings.whatsapp_floating_number"
                :href="'https://wa.me/' + settings.whatsapp_floating_number.replace(/\D/g, '')"
                target="_blank"
                rel="noopener noreferrer"
                :aria-label="isRtl ? 'تواصل عبر واتساب' : 'Chat on WhatsApp'"
                class="whatsapp-float sr"
                :style="isRtl ? 'left:30px' : 'right:30px'"
            >
                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.067 2.877 1.215 3.076.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.615 11.615 0 0012.03 0C5.399 0 .007 5.391 0 12.02c0 2.119.554 4.188 1.606 6.006L0 24l6.135-1.61a11.604 11.604 0 005.891 1.607h.005c6.632 0 12.023-5.391 12.027-12.021a11.615 11.615 0 00-3.644-8.283z"
                    />
                </svg>
            </a>
        </template>
    </div>
</template>
