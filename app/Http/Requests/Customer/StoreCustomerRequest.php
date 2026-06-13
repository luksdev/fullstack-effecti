<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerStatus;
use App\Rules\CpfCnpj;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Strip any mask from the document so validation and storage use digits only.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('federal_document')) {
            $this->merge([
                'federal_document' => preg_replace('/\D/', '', (string) $this->input('federal_document')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'federal_document' => ['required', 'string', new CpfCnpj, Rule::unique('customers', 'federal_document')],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')],
            'status' => ['required', new Enum(CustomerStatus::class)],
        ];
    }
}
