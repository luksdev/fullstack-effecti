<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatBRL } from '@/lib/money';

interface Item {
    id: string;
    service_id: string;
    service_name: string | null;
    quantity: number;
    unit_price_cents: number;
    line_total_cents: number;
}

interface Adjustment {
    label: string;
    amount_cents: number;
}

interface Contract {
    id: string;
    customer_id: string;
    start_date: string | null;
    end_date: string | null;
    status: { value: string; label: string };
    items: Item[];
    pricing: {
        subtotal_cents: number;
        adjustments: Adjustment[];
        total_cents: number;
    };
}

const props = defineProps<{
    contract: { data: Contract } | null;
    customers: { data: { id: string; name: string }[] };
    services: {
        data: {
            id: string;
            name: string;
            base_price: string;
            base_price_cents: number;
        }[];
    };
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

// Unit price is kept in integer cents; the CurrencyInput handles the BRL mask.
const itemForm = useForm({
    service_id: '',
    quantity: 1,
    unit_price: 0,
});

// Prefill the unit price with the selected service's base price so the field is
// visible and editable; the value sent is the one frozen on the contract item.
watch(
    () => itemForm.service_id,
    (serviceId) => {
        const service = props.services.data.find(
            (item) => item.id === serviceId,
        );
        itemForm.unit_price = service?.base_price_cents ?? 0;
    },
);

const itemPreviewTotal = computed(() =>
    formatBRL(itemForm.unit_price * (itemForm.quantity || 0)),
);

// Live indicators for the pricing rules panel (kept in sync with the backend rules:
// QuantityDiscountRule min 3 / 5%, ProgressiveDiscountRule 10% >= R$1000 else 5% >= R$500).
const totalQuantity = computed(() =>
    (record.value?.items ?? []).reduce((sum, item) => sum + item.quantity, 0),
);
const subtotalCents = computed(() => record.value?.pricing.subtotal_cents ?? 0);

function addItem(): void {
    const current = record.value;

    if (!current) {
        return;
    }

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
                        <th class="p-2">Preço unit.</th>
                        <th class="p-2">Total linha</th>
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
                        <td class="p-2">
                            {{ formatBRL(item.unit_price_cents) }}
                        </td>
                        <td class="p-2">
                            {{ formatBRL(item.line_total_cents) }}
                        </td>
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
                <div class="grid w-40 gap-1">
                    <Label for="item_price">Preço unit.</Label>
                    <CurrencyInput
                        id="item_price"
                        v-model="itemForm.unit_price"
                        placeholder="0,00"
                    />
                    <InputError :message="itemForm.errors.unit_price" />
                </div>
                <div class="grid gap-1">
                    <Label>Total do item</Label>
                    <span class="flex h-9 items-center text-sm font-medium">{{
                        itemPreviewTotal
                    }}</span>
                </div>
                <Button type="submit" :disabled="itemForm.processing"
                    >Adicionar item</Button
                >
            </form>

            <div class="flex flex-col gap-1 border-t pt-4 text-sm">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">Resumo do preço</h3>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button
                                variant="link"
                                size="sm"
                                class="h-auto px-0"
                            >
                                Como o preço é calculado?
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle
                                    >Como o preço é calculado?</DialogTitle
                                >
                                <DialogDescription>
                                    Regras aplicadas no backend sobre o
                                    subtotal. Os descontos podem se acumular e o
                                    total nunca é salvo — é sempre recalculado.
                                </DialogDescription>
                            </DialogHeader>
                            <ul class="ml-4 list-disc space-y-3 text-sm">
                                <li>
                                    <strong>Desconto por quantidade:</strong> 5%
                                    sobre o subtotal quando a soma das
                                    quantidades dos itens é
                                    <strong>≥ 3</strong>.
                                    <div
                                        :class="
                                            totalQuantity >= 3
                                                ? 'text-green-600'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        Neste contrato: {{ totalQuantity }} —
                                        {{
                                            totalQuantity >= 3
                                                ? 'aplicado'
                                                : 'não aplicado'
                                        }}
                                    </div>
                                </li>
                                <li>
                                    <strong>Desconto progressivo:</strong> sobre
                                    o subtotal — <strong>10%</strong> se ≥ R$
                                    1.000,00; senão <strong>5%</strong> se ≥ R$
                                    500,00 (apenas a maior faixa aplicável).
                                    <div
                                        :class="
                                            subtotalCents >= 50000
                                                ? 'text-green-600'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        Neste contrato:
                                        {{ formatBRL(subtotalCents) }} —
                                        {{
                                            subtotalCents >= 100000
                                                ? '10% aplicado'
                                                : subtotalCents >= 50000
                                                  ? '5% aplicado'
                                                  : 'não aplicado'
                                        }}
                                    </div>
                                </li>
                            </ul>
                        </DialogContent>
                    </Dialog>
                </div>
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <span>{{ formatBRL(record.pricing.subtotal_cents) }}</span>
                </div>
                <div
                    v-for="(adjustment, index) in record.pricing.adjustments"
                    :key="index"
                    class="flex justify-between text-muted-foreground"
                >
                    <span>{{ adjustment.label }}</span>
                    <span>{{ formatBRL(adjustment.amount_cents) }}</span>
                </div>
                <div class="flex justify-between border-t pt-1 font-semibold">
                    <span>Total</span>
                    <span>{{ formatBRL(record.pricing.total_cents) }}</span>
                </div>
            </div>
        </section>
    </div>
</template>
