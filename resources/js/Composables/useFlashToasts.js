import { watch } from 'vue';
import { useToast } from '@/Composables/useToast';

let lastHandledFlashRef = null;

/**
 * Shared flash-message watcher for all Inertia layouts.
 * Ensures server flash messages (success, error, warning, info, status, message)
 * are displayed consistently without duplicating frontend onSuccess toasts or
 * flashing "success then error" when a controller returns with('error', ...).
 */
export function useFlashToasts(page, { immediate = true } = {}) {
    const {
        success: toastSuccess,
        error: toastError,
        warning: toastWarning,
        info: toastInfo,
        markPendingFlashError,
        clearPendingFlashError,
    } = useToast();

    watch(
        () => page.props.flash,
        (flash) => {
            if (!flash || flash === lastHandledFlashRef) return;

            const hasAnyFlash =
                flash.success ||
                flash.error ||
                flash.warning ||
                flash.info ||
                flash.status ||
                flash.message;

            if (!hasAnyFlash) {
                lastHandledFlashRef = flash;
                return;
            }

            lastHandledFlashRef = flash;

            // Synchronously mark if the server response carries an error flash,
            // so any Inertia onSuccess callback running on this tick will NOT
            // fire a false optimistic green success toast before the error shows.
            if (flash.error) {
                markPendingFlashError();
            }

            // Defer by one event-loop turn so any synchronous onSuccess callback
            // runs first (recording recentFrontendToasts) before flash toasts fire.
            setTimeout(() => {
                if (flash.error) {
                    toastError(flash.error, { fromFlash: true });
                    clearPendingFlashError();
                } else if (flash.success) {
                    toastSuccess(flash.success, { fromFlash: true });
                }

                if (flash.warning) toastWarning(flash.warning, { fromFlash: true });
                if (flash.info) toastInfo(flash.info, { fromFlash: true });
                if (flash.status && !flash.success && !flash.error) {
                    toastSuccess(flash.status, { fromFlash: true });
                }
                if (flash.message && !flash.success && !flash.error) {
                    toastInfo(flash.message, { fromFlash: true });
                }
            }, 0);
        },
        { deep: true, immediate }
    );
}
