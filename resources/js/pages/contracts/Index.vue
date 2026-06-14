<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PaginationNav from '@/components/PaginationNav.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useIndexFilters } from '@/composables/useIndexFilters';
import type { Paginated } from '@/types';

interface Contract {
    id: string;
    customer: { name: string } | null;
    start_date: string | null;
    end_date: string | null;
    status: { value: string; label: string };
    pricing: { total: string };
}

const props = defineProps<{
    contracts: Paginated<Contract>;
    filters: { search: string; status: string };
    statuses: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Contratos', href: '/contracts' }],
    },
});

const { filters, reset } = useIndexFilters('/contracts', { ...props.filters }, [
    'contracts',
    'filters',
]);

function destroy(contract: Contract): void {
    if (confirm('Excluir este contrato?')) {
        router.delete(`/contracts/${contract.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Contratos" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Contratos</h1>
            <Button as-child>
                <Link href="/contracts/create">Novo contrato</Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-end gap-3 rounded-lg border p-3">
            <div class="grid gap-1">
                <Label for="search">Buscar</Label>
                <Input
                    id="search"
                    v-model="filters.search"
                    class="w-64"
                    placeholder="Nome do cliente"
                />
            </div>
            <div class="grid gap-1">
                <Label for="status">Status</Label>
                <select
                    id="status"
                    v-model="filters.status"
                    class="h-9 rounded-md border bg-background px-3 text-sm"
                >
                    <option value="">Todos</option>
                    <option
                        v-for="status in statuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </select>
            </div>
            <Button variant="ghost" size="sm" @click="reset">Limpar</Button>
        </div>

        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-b text-left">
                    <th class="p-2">Cliente</th>
                    <th class="p-2">Início</th>
                    <th class="p-2">Fim</th>
                    <th class="p-2">Status</th>
                    <th class="p-2">Total (R$)</th>
                    <th class="p-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="contract in contracts.data"
                    :key="contract.id"
                    class="border-b"
                >
                    <td class="p-2">{{ contract.customer?.name ?? '—' }}</td>
                    <td class="p-2">{{ contract.start_date ?? '—' }}</td>
                    <td class="p-2">{{ contract.end_date ?? '—' }}</td>
                    <td class="p-2">{{ contract.status.label }}</td>
                    <td class="p-2 font-medium">
                        {{ contract.pricing.total }}
                    </td>
                    <td class="flex justify-end gap-2 p-2">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="`/contracts/${contract.id}/edit`"
                                >Editar</Link
                            >
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="destroy(contract)"
                            >Excluir</Button
                        >
                    </td>
                </tr>
                <tr v-if="contracts.data.length === 0">
                    <td
                        class="p-4 text-center text-muted-foreground"
                        colspan="6"
                    >
                        Nenhum contrato encontrado.
                    </td>
                </tr>
            </tbody>
        </table>

        <PaginationNav :meta="contracts.meta" :links="contracts.links" />
    </div>
</template>
