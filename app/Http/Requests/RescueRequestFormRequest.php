<?php

namespace App\Http\Requests;

use App\Traits\TrimsInputTrait;
use Illuminate\Foundation\Http\FormRequest;

class RescueRequestFormRequest extends FormRequest
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
            'user_id' => 'nullable|integer|exists:users,id',
            'assigned_rescuer' => 'nullable|integer|exists:users,id',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'building_id' => 'nullable|integer|exists:buildings,id',
            'floor_id' => 'nullable|integer|exists:floors,id',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'description' => 'nullable|string|max:1000',
            'mobility_status' => 'nullable|string|max:255',
            'injuries' => 'nullable|string|max:500',
            'urgency_level' => 'nullable|string|in:low,medium,high,critical',
            'additional_info' => 'nullable|string|max:1000',
            'firstName' => 'nullable|string|max:50',
            'lastName' => 'nullable|string|max:50',
        ];
    }
}
