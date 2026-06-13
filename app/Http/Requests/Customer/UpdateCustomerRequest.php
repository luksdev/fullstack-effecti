<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerStatus;
use App\Rules\CpfCnpj;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateCustomerRequest extends FormRequest
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
        $customer = $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:255'],
            'federal_document' => ['required', 'string', new CpfCnpj, Rule::unique('customers', 'federal_document')->ignore($customer)],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer)],
            'status' => ['required', new Enum(CustomerStatus::class)],
        ];
    }
}
