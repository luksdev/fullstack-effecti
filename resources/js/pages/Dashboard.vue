<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, Package, Users } from '@lucide/vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const shortcuts = [
    {
        title: 'Clientes',
        description: 'Cadastre e gerencie clientes',
        href: '/customers',
        icon: Users,
    },
    {
        title: 'Serviços',
        description: 'Cadastre e gerencie serviços',
        href: '/services',
        icon: Package,
    },
    {
        title: 'Contratos',
        description: 'Monte contratos e veja o preço calculado',
        href: '/contracts',
        icon: FileText,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <Link
                v-for="shortcut in shortcuts"
                :key="shortcut.href"
                :href="shortcut.href"
                class="flex items-center gap-4 rounded-xl border border-sidebar-border/70 p-6 transition-colors hover:bg-accent dark:border-sidebar-border"
            >
                <span
                    class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <component :is="shortcut.icon" class="size-6" />
                </span>
                <span class="flex flex-col">
                    <span class="text-lg font-semibold">{{
                        shortcut.title
                    }}</span>
                    <span class="text-sm text-muted-foreground">{{
                        shortcut.description
                    }}</span>
                </span>
            </Link>
        </div>
    </div>
</template>
