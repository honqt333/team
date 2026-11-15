import { onMounted, nextTick } from 'vue';

/**
 * Automatically invokes a modal opener when the page is visited with `?<paramName>=1`,
 * then strips `<paramName>=1` from the browser URL so refreshing does not re-open the modal.
 *
 * @param {Function} onOpen - Callback to open the page's modal.
 * @param {string} [paramName='create'] - Query parameter name to watch for `'1'`.
 */
export function useAutoOpenCreate(onOpen, paramName = 'create') {
    onMounted(() => {
        if (typeof window === 'undefined' || typeof onOpen !== 'function') {
            return;
        }

        const url = new URL(window.location.href);
        if (url.searchParams.get(paramName) !== '1') {
            return;
        }

        url.searchParams.delete(paramName);
        const cleanedUrl = `${url.pathname}${url.search}${url.hash}`;
        window.history.replaceState(window.history.state, '', cleanedUrl);

        nextTick(() => {
            onOpen();
        });
    });
}
