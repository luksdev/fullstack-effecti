<script setup lang="ts">
import { computed } from 'vue';

/** Masked BRL input. The model value is the amount in integer cents. */
const props = defineProps<{
    modelValue: number | null;
    id?: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number];
}>();

const formatter = new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const display = computed(() => formatter.format((props.modelValue ?? 0) / 100));

function onInput(event: Event): void {
    const target = event.target as HTMLInputElement;
    const digits = target.value.replace(/\D/g, '');
    const cents = digits === '' ? 0 : parseInt(digits, 10);
    const formatted = formatter.format(cents / 100);

    emit('update:modelValue', cents);
    // Force the masked value back so the field always reflects the cents amount.
    target.value = formatted;
}
</script>

<template>
    <div
        class="flex h-9 w-full items-center rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
    >
        <span class="mr-2 text-muted-foreground">R$</span>
        <input
            :id="id"
            :value="display"
            :placeholder="placeholder"
            inputmode="numeric"
            class="w-full bg-transparent outline-none"
            @input="onInput"
        />
    </div>
</template>
