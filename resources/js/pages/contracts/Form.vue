<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Item {
    id: string;
    service_id: string;
    service_name: string | null;
    quantity: number;
    unit_price: string;
    line_total: string;
}

interface Adjustment {
    label: string;
    amount: string;
}

interface Contract {
    id: string;
    customer_id: string;
    start_date: string | null;
    end_date: string | null;
    status: { value: string; label: string };
    items: Item[];
    pricing: {
        subtotal: string;
        adjustments: Adjustment[];
        total: string;
    };
}

const props = defineProps<{
    contract: { data: Contract } | null;
    customers: { data: { id: string; name: string }[] };
    services: { data: { id: string; name: string; base_price: string }[] };
    statuses: { value: string; label: string }[];
}>();

// Computed so the page stays reactive when Inertia refreshes props after
// creating the contract or adding/removing items (the same Form component is reused).
const record = computed(() => props.contract?.data ?? null);
const editing = computed(() => props.contract !== null);
const isCancelled = computed(() => record.value?.status.value === 'cancelled');

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Contratos', href: '/contracts' }],
    },
});

const form = useForm({
    customer_id: record.value?.customer_id ?? '',
    start_date: record.value?.start_date ?? '',
    end_date: record.value?.end_date ?? '',
    status: record.value?.status.value ?? 'active',
});

function submit(): void {
    const current = record.value;

    if (current) {
        form.put(`/contracts/${current.id}`);
    } else {
        form.post('/contracts');
    }
}

const itemForm = useForm({
    service_id: '',
    quantity: 1,
    unit_price: '',
});

// Prefill the unit price with the selected service's base price so the field is
// visible and editable; leaving it untouched still freezes that value on the backend.
watch(
    () => itemForm.service_id,
    (serviceId) => {
        const service = props.services.data.find(
            (item) => item.id === serviceId,
        );
        itemForm.unit_price = service?.base_price ?? '';
    },
);

const itemPreviewTotal = computed(() => {
    const price =
        parseFloat(String(itemForm.unit_price).replace(',', '.')) || 0;

    return (price * (itemForm.quantity || 0)).toFixed(2);
});

function addItem(): void {
    const current = record.value;

    if (!current) {
        return;
    }

    itemForm.transform((data) => ({
        service_id: data.service_id,
        quantity: data.quantity,
        // Optional override; when blank the backend freezes the service base_price.
        unit_price:
            data.unit_price === '' || data.unit_price === null
                ? null
                : Math.round(
                      parseFloat(String(data.unit_price).replace(',', '.')) *
                          100,
                  ),
    }));

    itemForm.post(`/contracts/${current.id}/items`, {
        preserveScroll: true,
        onSuccess: () => itemForm.reset(),
    });
}

function removeItem(item: Item): void {
    const current = record.value;

    if (current) {
        router.delete(`/contracts/${current.id}/items/${item.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head :title="editing ? 'Editar contrato' : 'Novo contrato'" />

    <div class="flex max-w-3xl flex-col gap-8 p-4">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <h1 class="text-xl font-semibold">
                {{ editing ? 'Editar contrato' : 'Novo contrato' }}
            </h1>

            <div class="grid gap-2">
                <Label for="customer_id">Cliente</Label>
                <select
                    id="customer_id"
                    v-model="form.customer_id"
                    class="h-9 rounded-md border bg-background px-3 text-sm"
                    required
                >
                    <option value="" disabled>Selecione um cliente</option>
                    <option
                        v-for="customer in customers.data"
                        :key="customer.id"
                        :value="customer.id"
                    >
                        {{ customer.name }}
                    </option>
                </select>
                <InputError :message="form.errors.customer_id" />
            </div>

            <div class="grid gap-2">
                <Label for="start_date">Data de início</Label>
                <Input
                    id="start_date"
                    type="date"
                    v-model="form.start_date"
                    required
                />
                <InputError :message="form.errors.start_date" />
            </div>

            <div class="grid gap-2">
                <Label for="end_date">
                    Data de fim
                    <span class="text-muted-foreground">(opcional)</span>
                </Label>
                <Input id="end_date" type="date" v-model="form.end_date" />
                <InputError :message="form.errors.end_date" />
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
                <Button type="submit" :disabled="form.processing"
                    >Salvar</Button
                >
                <Button type="button" variant="outline" as-child>
                    <a href="/contracts">Cancelar</a>
                </Button>
            </div>
        </form>

        <section v-if="editing && record" class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">Itens do contrato</h2>

            <p v-if="isCancelled" class="text-sm text-muted-foreground">
                Contrato cancelado: não é possível adicionar ou remover itens.
            </p>

            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="p-2">Serviço</th>
                        <th class="p-2">Qtd.</th>
                        <th class="p-2">Preço unit. (R$)</th>
                        <th class="p-2">Total linha (R$)</th>
                        <th class="p-2 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in record.items"
                        :key="item.id"
                        class="border-b"
                    >
                        <td class="p-2">{{ item.service_name }}</td>
                        <td class="p-2">{{ item.quantity }}</td>
                        <td class="p-2">{{ item.unit_price }}</td>
                        <td class="p-2">{{ item.line_total }}</td>
                        <td class="p-2 text-right">
                            <Button
                                variant="destructive"
                                size="sm"
                                :disabled="isCancelled"
                                @click="removeItem(item)"
                            >
                                Remover
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="record.items.length === 0">
                        <td
                            class="p-4 text-center text-muted-foreground"
                            colspan="5"
                        >
                            Nenhum item.
                        </td>
                    </tr>
                </tbody>
            </table>

            <form
                v-if="!isCancelled"
                class="flex flex-wrap items-end gap-2"
                @submit.prevent="addItem"
            >
                <div class="grid gap-1">
                    <Label for="item_service">Serviço</Label>
                    <select
                        id="item_service"
                        v-model="itemForm.service_id"
                        class="h-9 rounded-md border bg-background px-3 text-sm"
                        required
                    >
                        <option value="" disabled>Selecione</option>
                        <option
                            v-for="service in services.data"
                            :key="service.id"
                            :value="service.id"
                        >
                            {{ service.name }} (R$ {{ service.base_price }})
                        </option>
                    </select>
                    <InputError :message="itemForm.errors.service_id" />
                </div>
                <div class="grid gap-1">
                    <Label for="item_quantity">Qtd.</Label>
                    <Input
                        id="item_quantity"
                        type="number"
                        min="1"
                        v-model.number="itemForm.quantity"
                        class="w-24"
                        required
                    />
                    <InputError :message="itemForm.errors.quantity" />
                </div>
                <div class="grid gap-1">
                    <Label for="item_price">Preço unit. (R$)</Label>
                    <Input
                        id="item_price"
                        type="text"
                        inputmode="decimal"
                        v-model="itemForm.unit_price"
                        class="w-32"
                        placeholder="base do serviço"
                    />
                    <InputError :message="itemForm.errors.unit_price" />
                </div>
                <div class="grid gap-1">
                    <Label>Total do item (R$)</Label>
                    <span class="flex h-9 items-center text-sm font-medium"
                        >R$ {{ itemPreviewTotal }}</span
                    >
                </div>
                <Button type="submit" :disabled="itemForm.processing"
                    >Adicionar item</Button
                >
            </form>

            <div class="flex flex-col gap-1 border-t pt-4 text-sm">
                <h3 class="font-semibold">Resumo do preço</h3>
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <span>R$ {{ record.pricing.subtotal }}</span>
                </div>
                <div
                    v-for="(adjustment, index) in record.pricing.adjustments"
                    :key="index"
                    class="flex justify-between text-muted-foreground"
                >
                    <span>{{ adjustment.label }}</span>
                    <span>R$ {{ adjustment.amount }}</span>
                </div>
                <div class="flex justify-between border-t pt-1 font-semibold">
                    <span>Total</span>
                    <span>R$ {{ record.pricing.total }}</span>
                </div>
            </div>
        </section>
    </div>
</template>
