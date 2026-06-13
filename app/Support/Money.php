<?php

namespace App\Support;

class Money
{
    /**
     * Format an integer amount of cents as a two-decimal reais string.
     * This is the presentation boundary — money is stored and computed as cents everywhere else.
     */
    public static function toReais(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
