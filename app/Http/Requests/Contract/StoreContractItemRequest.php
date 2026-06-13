<?php

namespace App\Http\Requests\Contract;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Contract $contract */
        $contract = $this->route('contract');

        return [
            'service_id' => [
                'required',
                'uuid',
                'exists:services,id',
                Rule::unique('contract_items', 'service_id')->where('contract_id', $contract->id),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.unique' => 'Este serviço já está no contrato.',
        ];
    }
}
