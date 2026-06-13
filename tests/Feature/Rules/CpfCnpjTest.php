<?php

namespace Tests\Feature\Rules;

use App\Rules\CpfCnpj;
use Illuminate\Support\Facades\Validator;

function validateDocument(string $value): bool
{
    return Validator::make(
        ['document' => $value],
        ['document' => new CpfCnpj]
    )->passes();
}

it('accepts valid documents', function (string $document) {
    expect(validateDocument($document))->toBeTrue();
})->with([
    'cpf unmasked' => '529.982.247-25',
    'cpf masked' => '52998224725',
    'cpf masked alt' => '111.444.777-35',
    'cpf unmasked alt' => '11144477735',
    'cnpj masked' => '11.222.333/0001-81',
    'cnpj unmasked' => '11222333000181',
    'cnpj masked alt' => '04.252.011/0001-10',
    'cnpj unmasked alt' => '04252011000110',
]);

it('rejects invalid documents', function (string $document) {
    expect(validateDocument($document))->toBeFalse();
})->with([
    'cpf wrong check digit' => '529.982.247-26',
    'cpf all same digits' => '111.111.111-11',
    'cpf zeros' => '00000000000',
    'cnpj wrong check digit' => '11.222.333/0001-82',
    'cnpj all same digits' => '11111111111111',
    'too short' => '123',
    'too long' => '123456789012345',
    'letters only' => 'abcdefghijk',
    'cpf with letters' => '5299822472a',
]);
