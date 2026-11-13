import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;
let containerCounter = 0;
const activeContainers = ref([]);
const toastTimers = new Map();

// Tracks recent frontend-initiated toasts to prevent double-toasting
// when both frontend onSuccess callbacks and backend flash messages fire for the exact same action.
const RECENT_DEDUPE_MS = 3500;
const SAME_RESPONSE_DEDUPE_MS = 300;
const recentFrontendToasts = [];
let pendingFlashErrorUntil = 0;

function markPendingFlashError() {
    pendingFlashErrorUntil = Date.now() + 150;
}

function clearPendingFlashError() {
    pendingFlashErrorUntil = 0;
}

function cleanOldRecentToasts() {
    const now = Date.now();
    while (recentFrontendToasts.length > 0 && now - recentFrontendToasts[0].timestamp > 10000) {
        recentFrontendToasts.shift();
    }
}

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

    function startTimer(toast) {
        if (!toast || toast.duration <= 0) return;
        const timerData = {
            startedAt: Date.now(),
            remaining: toast.duration,
            timeoutId: null,
        };
        timerData.timeoutId = setTimeout(() => {
            removeToast(toast.id);
        }, toast.duration);
        toastTimers.set(toast.id, timerData);
    }

    function pauseToast(id) {
        const timerData = toastTimers.get(id);
        if (!timerData) return;
        if (timerData.timeoutId) {
            clearTimeout(timerData.timeoutId);
            timerData.timeoutId = null;
            const elapsed = Date.now() - timerData.startedAt;
            timerData.remaining = Math.max(0, timerData.remaining - elapsed);
        }
    }

    function resumeToast(id) {
        const timerData = toastTimers.get(id);
        if (!timerData || timerData.remaining <= 0) return;
        timerData.startedAt = Date.now();
        timerData.timeoutId = setTimeout(() => {
            removeToast(id);
        }, timerData.remaining);
    }

    function addToast(options) {
        const type = options.type || 'info';
        const isFlash = !!options.fromFlash;
        const msg = String(options.message || '').trim();

        if (!msg) return null;

        // If the current Inertia response already carries a server flash error,
        // suppress any optimistic frontend onSuccess toast so users never see "success then error".
        if (!isFlash && type === 'success' && Date.now() < pendingFlashErrorUntil) {
            return null;
        }

        cleanOldRecentToasts();

        // Prevent truly duplicate messages (same type + same text) currently visible
        const isCurrentlyVisible = toasts.value.some(
            (t) => t.type === type && String(t.message || '').trim() === msg
        );
        if (isCurrentlyVisible) {
            return null;
        }

        // If this is a flash toast, suppress it if:
        // 1. A frontend toast of the same type fired in the same response tick (< 300ms), even with different wording, OR
        // 2. A frontend toast with matching/similar wording fired recently (< 3500ms).
        if (isFlash) {
            const now = Date.now();
            const matchingRecent = recentFrontendToasts.find(
                (r) =>
                    r.type === type &&
                    (now - r.timestamp < SAME_RESPONSE_DEDUPE_MS ||
                        (now - r.timestamp < RECENT_DEDUPE_MS &&
                            (r.message === msg ||
                                r.message.includes(msg) ||
                                msg.includes(r.message))))
            );
            if (matchingRecent) {
                return null;
            }
        }

        // Record frontend-initiated toasts for intelligent flash deduplication
        if (!isFlash) {
            recentFrontendToasts.push({
                type,
                message: msg,
                timestamp: Date.now(),
            });
        }

        const id = ++toastId;
        const toast = {
            id,
            type,
            title: options.title || null,
            message: options.message,
            duration: options.duration ?? (type === 'error' ? 6000 : 4000),
            dismissOnClick: options.dismissOnClick ?? true,
            action: options.action || null,
        };

        toasts.value.push(toast);

        startTimer(toast);

        return id;
    }

    function removeToast(id) {
        const timerData = toastTimers.get(id);
        if (timerData?.timeoutId) {
            clearTimeout(timerData.timeoutId);
        }
        toastTimers.delete(id);

        const index = toasts.value.findIndex((t) => t.id === id);
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
        for (const timerData of toastTimers.values()) {
            if (timerData.timeoutId) clearTimeout(timerData.timeoutId);
        }
        toastTimers.clear();
        recentFrontendToasts.length = 0;
        pendingFlashErrorUntil = 0;
        toasts.value = [];
    }

    return {
        toasts,
        activeContainers,
        registerContainer,
        unregisterContainer,
        addToast,
        removeToast,
        pauseToast,
        resumeToast,
        markPendingFlashError,
        clearPendingFlashError,
        success,
        error,
        warning,
        info,
        clear,
    };
}
