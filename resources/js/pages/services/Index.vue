<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PaginationNav from '@/components/PaginationNav.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useIndexFilters } from '@/composables/useIndexFilters';
import { formatBRL } from '@/lib/money';
import type { Paginated } from '@/types';

interface Service {
    id: string;
    name: string;
    base_price_cents: number;
    base_price: string;
}

const props = defineProps<{
    services: Paginated<Service>;
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Serviços', href: '/services' }],
    },
});

const { filters, reset } = useIndexFilters('/services', { ...props.filters }, [
    'services',
    'filters',
]);

function destroy(service: Service): void {
    if (confirm(`Excluir o serviço ${service.name}?`)) {
        router.delete(`/services/${service.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Serviços" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Serviços</h1>
            <Button as-child>
                <Link href="/services/create">Novo serviço</Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-end gap-3 rounded-lg border p-3">
            <div class="grid gap-1">
                <Label for="search">Buscar</Label>
                <Input
                    id="search"
                    v-model="filters.search"
                    class="w-64"
                    placeholder="Nome do serviço"
                />
            </div>
            <Button variant="ghost" size="sm" @click="reset">Limpar</Button>
        </div>

        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="p-2">Nome</th>
                    <th class="p-2">Preço base</th>
                    <th class="p-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="service in services.data"
                    :key="service.id"
                    class="border-b"
                >
                    <td class="p-2">{{ service.name }}</td>
                    <td class="p-2">
                        {{ formatBRL(service.base_price_cents) }}
                    </td>
                    <td class="flex justify-end gap-2 p-2">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="`/services/${service.id}/edit`"
                                >Editar</Link
                            >
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="destroy(service)"
                            >Excluir</Button
                        >
                    </td>
                </tr>
                <tr v-if="services.data.length === 0">
                    <td
                        class="p-4 text-center text-muted-foreground"
                        colspan="3"
                    >
                        Nenhum serviço encontrado.
                    </td>
                </tr>
            </tbody>
        </table>

        <PaginationNav :meta="services.meta" :links="services.links" />
    </div>
</template>
