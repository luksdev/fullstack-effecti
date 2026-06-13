import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    // Fires after every successful Inertia visit; the server flashes a one-off
    // toast into the shared `flash` prop, only present right after a redirect.
    router.on('success', (event) => {
        const page = (event as CustomEvent).detail?.page;
        const data = page?.props?.flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });
}
