/**
 * Composable to handle smooth scrolling, tab auto-switching,
 * and focus highlighting on form validation errors.
 */
export function useFormScrollToError() {
    /**
     * Scrolls to the first field with an error, switches the tab if necessary,
     * and sets focus with an animated ring.
     *
     * @param {Object} errors - Inertia form.errors object { fieldName: 'error message' }
     * @param {Object} [config]
     * @param {Object} [config.tabMap] - Map of field name to tab key { [fieldName]: 'tabKey' }
     * @param {Function} [config.onSwitchTab] - Function called with tabKey to activate tab
     * @param {number} [config.delay=120] - Delay in ms to allow DOM/tab update
     * @returns {boolean} true if an error was handled, false otherwise
     */
    const scrollToError = (errors, config = {}) => {
        if (!errors || typeof errors !== 'object') return false;

        const errorKeys = Object.keys(errors);
        if (errorKeys.length === 0) return false;

        const firstErrorKey = errorKeys[0];

        // 1. If tab switcher is provided and the field belongs to a specific tab, switch to it
        if (
            config.tabMap &&
            config.tabMap[firstErrorKey] &&
            typeof config.onSwitchTab === 'function'
        ) {
            config.onSwitchTab(config.tabMap[firstErrorKey]);
        }

        const delay = config.delay ?? 120;

        setTimeout(() => {
            // Find target element by name, id, data-field, or fallback
            const selector = `[name="${firstErrorKey}"], #${firstErrorKey}, [data-field="${firstErrorKey}"]`;
            let el = document.querySelector(selector);

            if (!el) {
                // Try finding by input inside container with data-field
                el = document.querySelector(
                    `[data-field-container="${firstErrorKey}"] input, [data-field-container="${firstErrorKey}"] select`
                );
            }

            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });

                if (typeof el.focus === 'function') {
                    el.focus();
                }

                // Add visual highlight ring
                el.classList.add('ring-2', 'ring-red-500', 'ring-offset-2', 'transition-all');
                setTimeout(() => {
                    el.classList.remove('ring-2', 'ring-red-500', 'ring-offset-2');
                }, 3000);
            }
        }, delay);

        return true;
    };

    return {
        scrollToError,
    };
}
