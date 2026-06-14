<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PaginationNav from '@/components/PaginationNav.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useIndexFilters } from '@/composables/useIndexFilters';
import type { Paginated } from '@/types';

interface Customer {
    id: string;
    name: string;
    federal_document: string;
    email: string;
    status: { value: string; label: string };
}

const props = defineProps<{
    customers: Paginated<Customer>;
    filters: { search: string; status: string };
    statuses: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clientes', href: '/customers' }],
    },
});

const { filters, reset } = useIndexFilters('/customers', { ...props.filters }, [
    'customers',
    'filters',
]);

function destroy(customer: Customer): void {
    if (confirm(`Excluir o cliente ${customer.name}?`)) {
        router.delete(`/customers/${customer.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Clientes" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Clientes</h1>
            <Button as-child>
                <Link href="/customers/create">Novo cliente</Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-end gap-3 rounded-lg border p-3">
            <div class="grid gap-1">
                <Label for="search">Buscar</Label>
                <Input
                    id="search"
                    v-model="filters.search"
                    class="w-64"
                    placeholder="Nome, e-mail ou documento"
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
                    <th class="p-2">Nome</th>
                    <th class="p-2">Documento</th>
                    <th class="p-2">E-mail</th>
                    <th class="p-2">Status</th>
                    <th class="p-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="customer in customers.data"
                    :key="customer.id"
                    class="border-b"
                >
                    <td class="p-2">{{ customer.name }}</td>
                    <td class="p-2">{{ customer.federal_document }}</td>
                    <td class="p-2">{{ customer.email }}</td>
                    <td class="p-2">{{ customer.status.label }}</td>
                    <td class="flex justify-end gap-2 p-2">
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="`/customers/${customer.id}/edit`"
                                >Editar</Link
                            >
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="destroy(customer)"
                            >Excluir</Button
                        >
                    </td>
                </tr>
                <tr v-if="customers.data.length === 0">
                    <td
                        class="p-4 text-center text-muted-foreground"
                        colspan="5"
                    >
                        Nenhum cliente encontrado.
                    </td>
                </tr>
            </tbody>
        </table>

        <PaginationNav :meta="customers.meta" :links="customers.links" />
    </div>
</template>
