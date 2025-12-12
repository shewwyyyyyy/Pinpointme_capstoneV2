<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $schedule = [];
        
        // Get the property meal schedule with meal schedule items
        if ($this->propertyMealSchedule && $this->propertyMealSchedule->mealSchedule) {
            $mealSchedule = $this->propertyMealSchedule->mealSchedule;
            $items = $mealSchedule->mealSchedule; // This returns the HasMany relationship
            
            // Organize schedule by day
            foreach ($items as $item) {
                $day = $item->day_type;
                $mealType = $item->meal_type;
                
                if (!isset($schedule[$day])) {
                    $schedule[$day] = [];
                }
                
                $schedule[$day]["{$mealType}_start"] = substr($item->time_start, 0, 5); // HH:MM format
                $schedule[$day]["{$mealType}_end"] = substr($item->time_end, 0, 5);
            }
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'username' => $this->username,
            'unique_identifier' => $this->unique_identifier,
            'description' => $this->description,
            'schedule' => $schedule,
        ];
    }
}
