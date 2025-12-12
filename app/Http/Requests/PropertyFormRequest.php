<?php

namespace App\Http\Requests;

use App\Models\Property;
use App\Models\User;
use App\Rules\UniqueIgnoringSoftDeletes;
use App\Traits\TrimsInputTrait;
use Illuminate\Foundation\Http\FormRequest;

class PropertyFormRequest extends FormRequest
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
        // Get the user ID associated with this property (if updating)
        $excludeUserId = null;
        if ($this->id) {
            $property = Property::find($this->id);
            if ($property) {
                $profile = \App\Models\Profile::where('property_id', $property->id)->first();
                if ($profile) {
                    $excludeUserId = $profile->user_id;
                }
            }
        }

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:properties,code,' . $this->id,
            'description' => 'nullable|string|max:1000',
            'username' => [
                'nullable',
                'string',
                'max:50',
                new UniqueIgnoringSoftDeletes(User::class, 'username', $excludeUserId)
            ],
            'unique_identifier' => 'nullable|string|max:50|unique:properties,unique_identifier,' . $this->id,
        ];
    }
}
