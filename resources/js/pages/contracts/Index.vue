<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

interface Contract {
    id: string;
    customer: { name: string } | null;
    start_date: string | null;
    end_date: string | null;
    status: { value: string; label: string };
    pricing: { total: string };
}

defineProps<{
    contracts: { data: Contract[] };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Contratos', href: '/contracts' }],
    },
});

function destroy(contract: Contract): void {
    if (confirm('Excluir este contrato?')) {
        router.delete(`/contracts/${contract.id}`);
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
                        Nenhum contrato cadastrado.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
