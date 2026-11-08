/**
 * Shared state for the public landing page.
 *
 * The landing page used to be a single 2,962-line SFC. Everything below is
 * what that file used to hold inline, extracted so each section component can
 * stay small and be code-split.
 */
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useLanding(settings) {
    const page = usePage();

    const isRtl = computed(() => page.props.locale === 'ar');
    const isDark = ref(true);
    const isScrolled = ref(false);
    const isMobileMenuOpen = ref(false);
    const activeLegalPage = ref(null);
    const langLoading = ref(false);

    // ── Localisation helpers ────────────────────────────────────────────────
    // `t()` reads a *_ar / *_en pair out of the CMS settings blob.
    const t = (ar, en) => (isRtl.value ? settings.value[ar] || '' : settings.value[en] || '');
    const li = (item, key) =>
        isRtl.value ? item[`${key}_ar`] || item[key] || '' : item[`${key}_en`] || item[key] || '';

    // ── Theme ───────────────────────────────────────────────────────────────
    function applyTheme() {
        document.documentElement.classList.toggle('dark', isDark.value);
        document.documentElement.classList.toggle('light-mode', !isDark.value);
    }

    function toggleTheme() {
        isDark.value = !isDark.value;
        localStorage.setItem('lp_theme', isDark.value ? 'dark' : 'light');
        applyTheme();
    }

    // ── Scroll reveal ───────────────────────────────────────────────────────
    /**
     * Reveal-on-scroll for `.sr` elements.
     *
     * IMPORTANT: elements already inside the viewport are revealed
     * immediately. Without that the hero content stays at `opacity: 0`
     * until the observer fires, which delays the LCP paint.
     */
    let revealObserver = null;

    function initReveal(root = document) {
        const prefersReduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

        if (prefersReduced) {
            root.querySelectorAll('.sr').forEach((el) => el.classList.add('is-visible'));
            return;
        }

        revealObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry, i) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => entry.target.classList.add('is-visible'), i * 80);
                    }
                });
            },
            { threshold: 0.08, rootMargin: '0px 0px -40px 0px' }
        );

        root.querySelectorAll('.sr').forEach((el) => revealObserver.observe(el));

        // Force-reveal anything already on screen (critical for LCP).
        requestAnimationFrame(() => {
            root.querySelectorAll('.sr').forEach((el) => {
                const rect = el.getBoundingClientRect();
                if (rect.top < window.innerHeight) el.classList.add('is-visible');
            });
        });
    }

    // ── Lifecycle ───────────────────────────────────────────────────────────
    let scrollHandler = null;

    onMounted(async () => {
        const savedTheme = localStorage.getItem('lp_theme');
        isDark.value = savedTheme
            ? savedTheme === 'dark'
            : window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme();

        scrollHandler = () => {
            isScrolled.value = window.scrollY > 60;
        };
        window.addEventListener('scroll', scrollHandler, { passive: true });

        await nextTick();
        initReveal();
    });

    onUnmounted(() => {
        if (scrollHandler) window.removeEventListener('scroll', scrollHandler);
        if (revealObserver) revealObserver.disconnect();
    });

    return {
        isRtl,
        isDark,
        isScrolled,
        isMobileMenuOpen,
        activeLegalPage,
        langLoading,
        t,
        li,
        toggleTheme,
        applyTheme,
        initReveal,
    };
}
