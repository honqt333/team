/**
 * Global plugin to automatically convert Arabic numerals to English numerals
 * in all input fields across the application.
 */
export const ArabicNumeralsPlugin = {
    install(app) {
        if (typeof window === 'undefined') return;

        const arabicNumerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        const englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        const toEnglish = (value) => {
            if (typeof value !== 'string') return value;
            let result = value;
            let changed = false;
            for (let i = 0; i < 10; i++) {
                if (result.includes(arabicNumerals[i])) {
                    result = result.replaceAll(arabicNumerals[i], englishNumerals[i]);
                    changed = true;
                }
            }
            return changed ? result : null;
        };

        // Listed for 'input' events on the document (capturing phase)
        window.addEventListener('input', (e) => {
            const target = e.target;

            // Only process input and textarea elements
            if (!target || (target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA')) {
                return;
            }

            // Skip if readonly or disabled
            if (target.readOnly || target.disabled) {
                return;
            }

            // Get current value
            const val = target.value;

            // Attempt conversion
            const newVal = toEnglish(val);

            // If conversion happened
            if (newVal !== null) {
                // Update value
                target.value = newVal;

                // Dispatch a new input event so Vue/v-model picks up the change
                // We need to prevent infinite loops, but since '123' won't convert again, 
                // it should be safe. 
                // However, to be extra safe, we can set a flag or rely on the fact that
                // the next event won't trigger a change.

                // For Vue 3 v-model to update, we need to dispatch 'input'
                target.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }, true); // Use capturing phase to handle it before other listeners if possible
    }
};
