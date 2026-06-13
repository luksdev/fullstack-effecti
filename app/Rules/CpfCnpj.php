<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = $this->normalize($value);

        $isValid = match (strlen($digits)) {
            11 => $this->isValidCpf($digits),
            14 => $this->isValidCnpj($digits),
            default => false,
        };

        if (! $isValid) {
            $fail('O campo :attribute não é um CPF ou CNPJ válido.');
        }
    }

    /**
     * Strip everything that is not a digit so masked and unmasked input behave the same.
     */
    private function normalize(mixed $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    private function isValidCpf(string $cpf): bool
    {
        if ($this->hasRepeatedDigits($cpf)) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;

            for ($index = 0; $index < $position; $index++) {
                $sum += (int) $cpf[$index] * (($position + 1) - $index);
            }

            $checkDigit = $this->checkDigitFromSum($sum);

            if ($checkDigit !== (int) $cpf[$position]) {
                return false;
            }
        }

        return true;
    }

    private function isValidCnpj(string $cnpj): bool
    {
        if ($this->hasRepeatedDigits($cnpj)) {
            return false;
        }

        $firstWeights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $secondWeights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $firstDigit = $this->cnpjCheckDigit($cnpj, $firstWeights);

        if ($firstDigit !== (int) $cnpj[12]) {
            return false;
        }

        $secondDigit = $this->cnpjCheckDigit($cnpj, $secondWeights);

        return $secondDigit === (int) $cnpj[13];
    }

    /**
     * @param  array<int, int>  $weights
     */
    private function cnpjCheckDigit(string $cnpj, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += (int) $cnpj[$index] * $weight;
        }

        return $this->checkDigitFromSum($sum);
    }

    private function checkDigitFromSum(int $sum): int
    {
        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    private function hasRepeatedDigits(string $digits): bool
    {
        return preg_match('/^(\d)\1+$/', $digits) === 1;
    }
}
