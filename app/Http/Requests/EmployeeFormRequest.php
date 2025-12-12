<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use App\Traits\TrimsInputTrait;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeFormRequest extends FormRequest
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

            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50',
            'unique_identifier' => 'required|string|max:50|unique:profiles,unique_identifier',
            'email' => 'nullable|email|max:255',
            'position' => 'required|string|max:50',
            'department_id' => 'required|integer|exists:departments,id',


        ];
    }
}
