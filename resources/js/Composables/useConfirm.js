import { ref } from 'vue';

const isOpen = ref(false);
const options = ref({
    title: '',
    message: '',
    confirmText: '',
    cancelText: '',
    type: 'danger',
});

let resolvePromise = null;

export function useConfirm() {
    function confirm(opts = {}) {
        options.value = {
            title: opts.title || '',
            message: opts.message || '',
            confirmText: opts.confirmText || '',
            cancelText: opts.cancelText || '',
            type: opts.type || 'danger',
        };

        isOpen.value = true;

        return new Promise((resolve) => {
            resolvePromise = resolve;
        });
    }

    function resolve(value) {
        isOpen.value = false;
        if (resolvePromise) {
            resolvePromise(value);
            resolvePromise = null;
        }
    }

    return {
        isOpen,
        options,
        confirm,
        resolve,
    };
}
