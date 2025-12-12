<?php

namespace App\Http\Requests;

use App\Traits\TrimsInputTrait;
use Illuminate\Foundation\Http\FormRequest;

class MessageFormRequest extends FormRequest
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
            'sender_id' => 'required|integer|exists:users,id',
            'content' => 'required_without:file|nullable|string|max:5000',
            'file' => 'nullable|file|max:10240',
        ];
    }
}
