import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

export function useToast() {
    function addToast(options) {
        const id = ++toastId;
        const toast = {
            id,
            type: options.type || 'info',
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
        addToast,
        removeToast,
        success,
        error,
        warning,
        info,
        clear,
    };
}
