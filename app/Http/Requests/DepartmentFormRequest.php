<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * serve as security measure to prevent mass assignment vulnerabilities before it reaces the controller
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */ 
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:departments,name|regex:/^[a-zA-Z0-9\s\-_.,!?()&]+$/',
            'code' => 'required|string|max:50|unique:departments,code|regex:/^[a-zA-Z0-9\-_]+$/',
            'description' => 'nullable|string|max:500|regex:/^[a-zA-Z0-9\s\-_.,!?()&\n\r]+$/',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'The name field contains invalid characters. Emojis and special symbols are not allowed.',
            'code.regex' => 'The code field contains invalid characters. Only letters, numbers, hyphens, and underscores are allowed.',
            'description.regex' => 'The description field contains invalid characters. Emojis and special symbols are not allowed.',
        ];
    }
}
