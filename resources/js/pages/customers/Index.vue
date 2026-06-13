<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

interface Customer {
    id: string;
    name: string;
    federal_document: string;
    email: string;
    status: { value: string; label: string };
}

defineProps<{
    customers: { data: Customer[] };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clientes', href: '/customers' }],
    },
});

function destroy(customer: Customer): void {
    if (confirm(`Excluir o cliente ${customer.name}?`)) {
        router.delete(`/customers/${customer.id}`);
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
                        Nenhum cliente cadastrado.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
