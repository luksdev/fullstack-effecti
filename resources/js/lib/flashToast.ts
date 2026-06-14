import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    // Fires after every successful Inertia visit; the server flashes a one-off
    // toast into the shared `flash` prop, only present right after a redirect.
    router.on('success', (event) => {
        const page = (event as CustomEvent).detail?.page;
        const flash = page?.props?.flash as
            | { toast?: FlashToast | null }
            | undefined;
        const data = flash?.toast;

        if (!data) {
            return;
        }

        toast[data.type](data.message);

        // Partial reloads (e.g. index filters) don't refresh the `flash` prop, so the
        // stale toast would re-fire on the next visit; clear it once it has been shown.
        flash.toast = null;
    });
}
