import { ref, watch, onScopeDispose } from 'vue';
import axios from 'axios';
import { useDebounce } from './useDebounce';
import { useNumberFormat } from './useNumberFormat';

export function useVehicleSearch(endpoint, isOpen) {
    const searchQuery = ref('');
    const searchResults = ref([]);
    const searching = ref(false);
    const showResults = ref(false);
    const { toEnglish } = useNumberFormat();
    let pending;
    const request = useDebounce(async (query, signal) => {
        try {
            const { data } = await axios.get(endpoint, { params: { q: query }, signal });
            if (!signal.aborted) searchResults.value = data;
        } catch {
            if (!signal.aborted) searchResults.value = [];
        } finally {
            if (!signal.aborted) searching.value = false;
        }
    });

    function cancel() {
        request.cancel();
        pending?.abort();
        searching.value = false;
        searchResults.value = [];
    }

    watch([searchQuery, isOpen], cancel, { flush: 'sync' });
    onScopeDispose(cancel);

    function debouncedSearch() {
        cancel();
        searchQuery.value = toEnglish(searchQuery.value);
        if (searchQuery.value.length < 2 || !isOpen()) return;
        pending = new AbortController();
        showResults.value = true;
        searching.value = true;
        request(searchQuery.value, pending.signal);
    }
    return { searchQuery, searchResults, searching, showResults, debouncedSearch };
}
