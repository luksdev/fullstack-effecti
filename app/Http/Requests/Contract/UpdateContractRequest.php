<?php

namespace App\Http\Requests\Contract;

use App\Enums\ContractStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateContractRequest extends FormRequest
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
        return [
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', new Enum(ContractStatus::class)],
        ];
    }
}
