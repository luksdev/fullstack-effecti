const formatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

/** Format an integer amount of cents as a BRL value, e.g. 123932 -> "R$ 1.239,32". */
export function formatBRL(cents: number): string {
    return formatter.format(cents / 100);
}
