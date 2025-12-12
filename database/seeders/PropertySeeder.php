<?php

namespace Database\Seeders;

use App\Helpers\Helper;
use App\Models\MealSchedule;
use App\Models\MealScheduleItem;
use App\Models\Property;
use App\Models\PropertyMealSchedule;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $propertiesData = [
            [
                'name' => 'Astoria Plaza',
                'code' => 'APZ',
                'username' => 'astoriaplaza',
                'unique_identifier' => 'APZ001',
            ],
            [
                'name' => 'Astoria Current',
                'code' => 'AC3',
                'username' => 'astoriacurrent',
                'unique_identifier' => 'AC3001',
            ],
        ];

        foreach ($propertiesData as $data) {
            $property = Property::create($data);

            $scheduleName = $property->name . ' Meal Schedule';

            $mealSchedule = MealSchedule::create([
                'name' => $scheduleName,
                'remarks' => 'Default weekly schedule for ' . $property->name,
                'days' => Helper::MEAL_SCHEDULE_DAYS,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            //Create three MealScheduleItem records (Breakfast, Lunch, Dinner) for each day
            foreach ($mealSchedule->days as $day) {
                MealScheduleItem::create([
                    'meal_schedule_id' => $mealSchedule->id,
                    'time_start' => '07:30:00',
                    'time_end' => '10:00:00',
                    'day_type' => $day,
                    'meal_type' => Helper::MEAL_SCHEDULE_BREAKFAST,
                ]);
                MealScheduleItem::create([
                    'meal_schedule_id' => $mealSchedule->id,
                    'time_start' => '12:00:00',
                    'time_end' => '14:00:00', // 2 PM
                    'day_type' => $day,
                    'meal_type' => Helper::MEAL_SCHEDULE_LUNCH,
                ]);
                MealScheduleItem::create([
                    'meal_schedule_id' => $mealSchedule->id,
                    'time_start' => '16:00:00', // 4 PM
                    'time_end' => '20:00:00', // 8 PM
                    'day_type' => $day,
                    'meal_type' => Helper::MEAL_SCHEDULE_DINNER,
                ]);
            }

            PropertyMealSchedule::create([
                'property_id' => $property->id,
                'meal_schedule_id' => $mealSchedule->id,
            ]);
        }
    }
}
