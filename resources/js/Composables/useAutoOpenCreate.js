import { onMounted, nextTick } from 'vue';

/**
 * Automatically invokes a create-modal opener when the page is visited with `?create=1`,
 * then strips `create=1` from the browser URL so refreshing does not re-open the modal.
 *
 * @param {Function} onCreate - Callback to open the page's create modal.
 */
export function useAutoOpenCreate(onCreate) {
    onMounted(() => {
        if (typeof window === 'undefined' || typeof onCreate !== 'function') {
            return;
        }

        const url = new URL(window.location.href);
        if (url.searchParams.get('create') !== '1') {
            return;
        }

        url.searchParams.delete('create');
        const cleanedUrl = `${url.pathname}${url.search}${url.hash}`;
        window.history.replaceState(window.history.state, '', cleanedUrl);

        nextTick(() => {
            onCreate();
        });
    });
}
