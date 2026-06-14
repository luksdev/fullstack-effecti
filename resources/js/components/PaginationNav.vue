<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

const props = defineProps<{
    meta: Paginated<unknown>['meta'];
    links: Paginated<unknown>['links'];
}>();

function go(url: string | null): void {
    if (url) {
        router.get(
            url,
            {},
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }
}
</script>

<template>
    <div class="flex items-center justify-between text-sm">
        <span class="text-muted-foreground">
            Página {{ meta.current_page }} de {{ meta.last_page }} ({{
                meta.total
            }}
            no total)
        </span>
        <div class="flex gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="!props.links.prev"
                @click="go(props.links.prev)"
            >
                Anterior
            </Button>
            <Button
                variant="outline"
                size="sm"
                :disabled="!props.links.next"
                @click="go(props.links.next)"
            >
                Próxima
            </Button>
        </div>
    </div>
</template>
