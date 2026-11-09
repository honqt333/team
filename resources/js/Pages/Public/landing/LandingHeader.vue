<script setup>
/**
 * Landing header.
 *
 * ACCESSIBILITY FIXES:
 *   - The icon-only buttons (theme, mobile menu) had no accessible name
 *     (axe: `button-name`). They now have `aria-label` and `aria-expanded`.
 *   - The language switch announces what it will switch TO.
 */
import { Link } from '@inertiajs/vue3';

defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
    isDark: { type: Boolean, default: false },
    isScrolled: { type: Boolean, default: false },
    isMobileMenuOpen: { type: Boolean, default: false },
    t: { type: Function, required: true },
});

const emit = defineEmits(['toggle-theme', 'toggle-mobile', 'switch-lang']);
</script>

<template>
    <header :class="['lp-header', isScrolled ? 'scrolled' : '']">
        <nav class="lp-nav" :aria-label="isRtl ? 'التنقل الرئيسي' : 'Main navigation'">
            <a href="#" class="lp-logo">
                <span class="logo-mark">
                    <!--
                        The logo comes from the CMS, so it can be any file.
                        The optimised WebP is offered first and the original
                        PNG/SVG is the fallback — the CMS keeps working, and
                        browsers that support WebP get the small file.
                    -->
                    <picture>
                        <source
                            v-if="settings.logo"
                            type="image/webp"
                            srcset="/images/landing/logo.webp"
                        />
                        <img
                            v-if="settings.logo"
                            :src="settings.logo"
                            :alt="t('title_ar', 'title_en')"
                            width="44"
                            height="44"
                            decoding="async"
                        />
                    </picture>
                    <span v-if="!settings.logo" aria-hidden="true">C</span>
                </span>
                <span class="logo-name">{{ t('title_ar', 'title_en') }}</span>
            </a>

            <div class="nav-links">
                <a
                    v-for="m in settings.header_menu || []"
                    :key="m.url"
                    :href="m.url || '#'"
                    class="nav-link"
                >
                    {{ isRtl ? m.label_ar : m.label_en }}
                </a>
            </div>

            <div class="nav-actions">
                <button
                    type="button"
                    class="icon-btn"
                    :aria-label="
                        isDark
                            ? isRtl
                                ? 'التبديل للوضع الفاتح'
                                : 'Switch to light mode'
                            : isRtl
                              ? 'التبديل للوضع الداكن'
                              : 'Switch to dark mode'
                    "
                    @click="emit('toggle-theme')"
                >
                    <svg
                        v-if="isDark"
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M16.95 16.95l.707.707M7.05 7.05l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                        />
                    </svg>
                </button>

                <button
                    type="button"
                    class="lang-btn"
                    :aria-label="isRtl ? 'Switch to English' : 'التبديل إلى العربية'"
                    @click="emit('switch-lang')"
                >
                    {{ isRtl ? 'EN' : 'ع' }}
                </button>

                <Link :href="route('login')" class="btn-ghost-sm">
                    {{ t('login_button_text_ar', 'login_button_text_en') }}
                </Link>
                <Link
                    :href="route('register')"
                    class="btn-primary-sm"
                    data-track="trial_start"
                    data-track-placement="header"
                >
                    {{ t('hero_cta_text_ar', 'hero_cta_text_en') }}
                </Link>

                <button
                    type="button"
                    class="icon-btn lg-hidden"
                    :aria-label="isRtl ? 'فتح القائمة' : 'Open menu'"
                    :aria-expanded="isMobileMenuOpen"
                    @click="emit('toggle-mobile')"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            :d="
                                isMobileMenuOpen
                                    ? 'M6 18L18 6M6 6l12 12'
                                    : 'M4 6h16M4 12h16M4 18h16'
                            "
                        />
                    </svg>
                </button>
            </div>
        </nav>

        <transition name="slide-down">
            <div v-if="isMobileMenuOpen" class="mobile-menu">
                <a
                    v-for="m in settings.header_menu || []"
                    :key="m.url"
                    :href="m.url || '#'"
                    class="mobile-link"
                >
                    {{ isRtl ? m.label_ar : m.label_en }}
                </a>

                <div class="mobile-actions">
                    <Link :href="route('login')" class="btn-ghost-sm w-full text-center">
                        {{ t('login_button_text_ar', 'login_button_text_en') }}
                    </Link>
                    <Link
                        :href="route('register')"
                        class="btn-primary-sm w-full text-center"
                        data-track="trial_start"
                        data-track-placement="header-mobile"
                    >
                        {{ t('hero_cta_text_ar', 'hero_cta_text_en') }}
                    </Link>
                </div>

                <div class="mobile-actions">
                    <button type="button" class="maint-btn" @click="emit('toggle-theme')">
                        {{ isDark ? '☀️ Light' : '🌙 Dark' }}
                    </button>
                    <button type="button" class="maint-btn" @click="emit('switch-lang')">
                        {{ isRtl ? '🌐 English' : '🌐 العربية' }}
                    </button>
                </div>
            </div>
        </transition>
    </header>
</template>
