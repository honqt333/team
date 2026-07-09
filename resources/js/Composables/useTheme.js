/**
 * useTheme — single source of truth for the dark/light theme state.
 *
 * Why this exists: each layout used to do `const isDark = ref(false)` and
 * sync with `document.documentElement` only in `onMounted`. That caused a
 * one-frame flash on Inertia soft navigations (the new layout mounted with
 * `isDark=false` before reading the actual class back from the DOM).
 *
 * This composable reads the class SYNCHRONOUSLY at setup time so the first
 * render of any new layout already reflects the persisted theme.
 */
import { ref, onMounted, onUnmounted } from 'vue';
import { toggleTheme as toggleThemeCore, getInitialTheme, applyTheme } from '@/theme';

const STORAGE_KEY = 'theme';

// Module-level singleton so every layout sees the same `isDark` ref.
// This also means the button icon, sidebar variants, and any computed
// `isDark` stay in lockstep across the whole SPA.
const isDark = ref(
    typeof document !== 'undefined' && document.documentElement.classList.contains('dark')
);

// Watch for external mutations to <html class="dark"> (e.g. from
// the inline pre-paint script in app.blade.php, or future code paths)
// and keep our ref in sync.
let observer = null;
const ensureObserver = () => {
    if (observer || typeof document === 'undefined') return;
    observer = new MutationObserver(() => {
        const next = document.documentElement.classList.contains('dark');
        if (isDark.value !== next) isDark.value = next;
    });
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
};

export function useTheme() {
    ensureObserver();

    onMounted(() => {
        // Re-read once on mount to catch any race with the inline pre-paint
        // script or with HMR replacing the module mid-session.
        isDark.value = document.documentElement.classList.contains('dark');
    });

    onUnmounted(() => {
        // The observer is module-scoped on purpose; we never disconnect it
        // so the theme stays in sync for the lifetime of the page.
    });

    /**
     * Toggle the theme. The actual class flip + localStorage write happens
     * inside the shared theme module so every code path agrees.
     */
    const toggle = () => {
        const next = toggleThemeCore(); // 'light' | 'dark'
        isDark.value = next === 'dark';
        return next;
    };

    /**
     * Force a re-sync from the DOM. Useful when the user lands on a page
     * that has its own (legacy) theme implementation.
     */
    const sync = () => {
        isDark.value = document.documentElement.classList.contains('dark');
    };

    return {
        isDark,
        toggle,
        sync,
        getInitialTheme,
        applyTheme,
    };
}

// Re-export for convenience so a component can do
// `import { useTheme } from '@/Composables/useTheme'`
// without also importing from '@/theme'.
export { applyTheme, getInitialTheme, toggleThemeCore as toggleThemeCore };
export const __THEME_STORAGE_KEY = STORAGE_KEY;
