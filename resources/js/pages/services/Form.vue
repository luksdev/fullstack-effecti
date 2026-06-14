<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import CurrencyInput from '@/components/CurrencyInput.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Service {
    id: string;
    name: string;
    base_price_cents: number;
    base_price: string;
}

const props = defineProps<{
    service: { data: Service } | null;
}>();

const editing = props.service !== null;
const record = props.service?.data ?? null;

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Serviços', href: '/services' }],
    },
});

const form = useForm({
    name: record?.name ?? '',
    // Money is kept as integer cents end to end; the CurrencyInput handles the BRL mask.
    base_price: record?.base_price_cents ?? 0,
});

function submit(): void {
    if (editing && record) {
        form.put(`/services/${record.id}`);
    } else {
        form.post('/services');
    }
}
</script>

<template>
    <Head :title="editing ? 'Editar serviço' : 'Novo serviço'" />

    <form class="flex max-w-xl flex-col gap-4 p-4" @submit.prevent="submit">
        <h1 class="text-xl font-semibold">
            {{ editing ? 'Editar serviço' : 'Novo serviço' }}
        </h1>

        <div class="grid gap-2">
            <Label for="name">Nome</Label>
            <Input id="name" v-model="form.name" required />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="base_price">Preço base</Label>
            <CurrencyInput
                id="base_price"
                v-model="form.base_price"
                placeholder="0,00"
            />
            <InputError :message="form.errors.base_price" />
        </div>

        <div class="flex gap-2">
            <Button type="submit" :disabled="form.processing">Salvar</Button>
            <Button type="button" variant="outline" as-child>
                <a href="/services">Cancelar</a>
            </Button>
        </div>
    </form>
</template>
