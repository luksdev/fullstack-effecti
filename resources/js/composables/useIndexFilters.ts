import { router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

/**
 * Drives server-side index filters: debounced, history-replacing, and reloading
 * only the given props so filtering a paginated list stays cheap.
 */
export function useIndexFilters<T extends Record<string, string>>(
    url: string,
    initial: T,
    only: string[],
) {
    const filters = reactive({ ...initial }) as T;
    let timeout: ReturnType<typeof setTimeout> | undefined;

    watch(
        filters,
        () => {
            if (timeout) {
                clearTimeout(timeout);
            }

            timeout = setTimeout(() => {
                const query = Object.fromEntries(
                    Object.entries(filters).filter(
                        ([, value]) => value !== '' && value != null,
                    ),
                );

                router.get(url, query, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only,
                });
            }, 300);
        },
        { deep: true },
    );

    function reset(): void {
        for (const key of Object.keys(filters)) {
            (filters as Record<string, string>)[key] = '';
        }
    }

    return { filters, reset };
}
