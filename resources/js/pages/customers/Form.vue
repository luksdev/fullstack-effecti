<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Customer {
    id: string;
    name: string;
    federal_document: string;
    email: string;
    status: { value: string; label: string };
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    customer: { data: Customer } | null;
    statuses: StatusOption[];
}>();

const editing = props.customer !== null;
const record = props.customer?.data ?? null;

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Clientes', href: '/customers' }],
    },
});

const form = useForm({
    name: record?.name ?? '',
    federal_document: record?.federal_document ?? '',
    email: record?.email ?? '',
    status: record?.status.value ?? 'active',
});

function submit(): void {
    if (editing && record) {
        form.put(`/customers/${record.id}`);
    } else {
        form.post('/customers');
    }
}
</script>

<template>
    <Head :title="editing ? 'Editar cliente' : 'Novo cliente'" />

    <form class="flex max-w-xl flex-col gap-4 p-4" @submit.prevent="submit">
        <h1 class="text-xl font-semibold">
            {{ editing ? 'Editar cliente' : 'Novo cliente' }}
        </h1>

        <div class="grid gap-2">
            <Label for="name">Nome</Label>
            <Input id="name" v-model="form.name" required />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="federal_document">CPF / CNPJ</Label>
            <Input
                id="federal_document"
                v-model="form.federal_document"
                required
            />
            <InputError :message="form.errors.federal_document" />
        </div>

        <div class="grid gap-2">
            <Label for="email">E-mail</Label>
            <Input id="email" type="email" v-model="form.email" required />
            <InputError :message="form.errors.email" />
        </div>

        <div class="grid gap-2">
            <Label for="status">Status</Label>
            <select
                id="status"
                v-model="form.status"
                class="h-9 rounded-md border bg-background px-3 text-sm"
            >
                <option
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </select>
            <InputError :message="form.errors.status" />
        </div>

        <div class="flex gap-2">
            <Button type="submit" :disabled="form.processing">Salvar</Button>
            <Button type="button" variant="outline" as-child>
                <a href="/customers">Cancelar</a>
            </Button>
        </div>
    </form>
</template>
