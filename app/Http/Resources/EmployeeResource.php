<?php

namespace App\Http\Resources;

use App\Traits\ReturnDatetimeFormat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    use ReturnDatetimeFormat;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'full_name' => $this->getFullName(),
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'email' => $this->user?->email,
            'unique_identifier' => $this->unique_identifier,
            'position' => $this->position,
            'meal_entitlement' => $this->meal_entitlement,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'department_id' => $this->department_id,
            'created_at' => $this->returnShortDateTime($this->created_at),
            'updated_at' => $this->returnShortDateTime($this->updated_at),
        ];
    }
}
