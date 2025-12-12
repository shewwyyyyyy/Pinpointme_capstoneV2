<?php

namespace App\Http\Requests;

use App\Traits\TrimsInputTrait;
use Illuminate\Foundation\Http\FormRequest;

class AuditTrailFormRequest extends FormRequest
{
    use TrimsInputTrait;

    /**
     * Prepare the data for validation.
     *
     * This method is called before validation occurs.
     * It allows us to modify the input data, such as trimming whitespace.
     */
    protected function prepareForValidation()
    {
        $this->merge($this->trimInputs($this->all()));
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => 'required|string|max:100',
            'initiator' => 'required|string|max:255',
            'initiator_role' => 'nullable|string|max:50',
            'account_updated' => 'required|string|max:255',
            'details' => 'nullable|string|max:1000',
        ];
    }
}
