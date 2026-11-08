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

        if (!revealObserver) {
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
        }

        // Only observe elements we have not already seen, so repeated calls
        // (triggered by the MutationObserver when a lazy section arrives)
        // do not stack duplicate observers on the same element.
        const targets = [...root.querySelectorAll('.sr')].filter(
            (el) => !el.dataset.srObserved && !el.classList.contains('is-visible')
        );

        targets.forEach((el) => {
            el.dataset.srObserved = '1';
            revealObserver.observe(el);
        });

        // Force-reveal anything already on screen (critical for LCP).
        //
        // PERFORMANCE: reading getBoundingClientRect() interleaved with a
        // class write forces a synchronous layout on every element. Batching
        // all the reads first, then all the writes, collapses that into a
        // single layout pass (measured: 39 ms of forced reflow -> ~0).
        requestAnimationFrame(() => {
            const viewportHeight = window.innerHeight;
            const aboveFold = [];
            targets.forEach((el) => {
                if (el.getBoundingClientRect().top < viewportHeight) {
                    aboveFold.push(el);
                }
            });
            aboveFold.forEach((el) => el.classList.add('is-visible'));
        });
    }

    // ── Lifecycle ───────────────────────────────────────────────────────────
    let scrollHandler = null;
    let mutationObserver = null;

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

        /*
         * The page sections are loaded with defineAsyncComponent, so they
         * arrive AFTER the first initReveal() call. Without this observer
         * their `.sr` elements would stay at `opacity: 0` forever — the
         * content would be in the DOM but invisible.
         */
        mutationObserver = new MutationObserver((mutations) => {
            let added = false;
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType !== 1) return;
                    if (node.classList?.contains('sr')) {
                        added = true;
                    } else if (node.querySelector?.('.sr')) {
                        added = true;
                    }
                });
            });
            if (added) initReveal();
        });

        mutationObserver.observe(document.body, { childList: true, subtree: true });
    });

    onUnmounted(() => {
        if (scrollHandler) window.removeEventListener('scroll', scrollHandler);
        if (revealObserver) revealObserver.disconnect();
        if (mutationObserver) mutationObserver.disconnect();
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
