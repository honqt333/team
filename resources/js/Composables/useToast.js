import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;
let containerCounter = 0;
const activeContainers = ref([]);

// Tracks when the last non-flash toast of each type was shown.
// Flash toasts (fromFlash:true) are suppressed if a frontend-initiated toast
// of the same type was shown within FLASH_SUPPRESS_MS milliseconds.
// This prevents double-toasting when onSuccess callbacks and backend flash
// messages both fire for the same user action.
const FLASH_SUPPRESS_MS = 4000;
const lastFrontendToastAt = {
    success: 0,
    error: 0,
    warning: 0,
    info: 0,
};

export function useToast() {
    function registerContainer() {
        const id = ++containerCounter;
        activeContainers.value.push(id);
        return id;
    }

    function unregisterContainer(id) {
        const index = activeContainers.value.indexOf(id);
        if (index > -1) {
            activeContainers.value.splice(index, 1);
        }
    }

    function addToast(options) {
        const type = options.type || 'info';
        const isFlash = !!options.fromFlash;

        // If this is a flash toast and a frontend toast of the same type was shown
        // recently, suppress it to avoid duplicates regardless of message text.
        if (isFlash && (Date.now() - (lastFrontendToastAt[type] || 0)) < FLASH_SUPPRESS_MS) {
            return null;
        }

        // Prevent truly duplicate messages (same type + same text) already visible
        const isDuplicate = toasts.value.some(
            t => t.type === type && t.message === options.message
        );
        if (isDuplicate) {
            return null;
        }

        // Record when a frontend-initiated toast was shown so we can suppress
        // a subsequent flash of the same type.
        if (!isFlash) {
            lastFrontendToastAt[type] = Date.now();
        }

        const id = ++toastId;
        const toast = {
            id,
            type,
            title: options.title || null,
            message: options.message,
            duration: options.duration ?? 4000,
        };

        toasts.value.push(toast);

        if (toast.duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, toast.duration);
        }

        return id;
    }

    function removeToast(id) {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    }

    function success(message, options = {}) {
        return addToast({ ...options, type: 'success', message });
    }

    function error(message, options = {}) {
        return addToast({ ...options, type: 'error', message, duration: options.duration ?? 6000 });
    }

    function warning(message, options = {}) {
        return addToast({ ...options, type: 'warning', message });
    }

    function info(message, options = {}) {
        return addToast({ ...options, type: 'info', message });
    }

    function clear() {
        toasts.value = [];
    }

    return {
        toasts,
        activeContainers,
        registerContainer,
        unregisterContainer,
        addToast,
        removeToast,
        success,
        error,
        warning,
        info,
        clear,
    };
}
