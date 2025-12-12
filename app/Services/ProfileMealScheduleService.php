<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\ProfileMealScheduleInterface;
use App\Models\ProfileMealSchedule;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;

class ProfileMealScheduleService implements ProfileMealScheduleInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created profileMealSchedule in storage.
     */
    public function storeProfileMealSchedule(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $profileMealSchedule = ProfileMealSchedule::create([
                    'profile_id' => $data['profile_id'],
                    'meal_schedule_id' => $data['meal_schedule_id'],
                ]);

                return $this->returnModel(201, Helper::SUCCESS, 'ProfileMealSchedule created successfully!', $profileMealSchedule, $profileMealSchedule->id);
            });
        } catch (\Exception $e) {
            $meal_schedule_id = $this->httpCode($e);
            return $this->returnModel($meal_schedule_id, Helper::ERROR, $e->getMessage());
        }
    }

    /**
     * Update the specified profileMealSchedule in storage.
     */
    public function updateProfileMealSchedule($profileMealScheduleId, array $data): array
    {
        try {
            return DB::transaction(function () use ($profileMealScheduleId, $data) {
                $profileMealSchedule = ProfileMealSchedule::create([
                    'profile_id' => $data['profile_id'] ?? null,
                    'meal_schedule_id' => $data['meal_schedule_id'] ?? null,

                ]);

                return $this->returnModel(200, Helper::SUCCESS, 'ProfileMealSchedule updated successfully!', $profileMealSchedule, $profileMealSchedule->id);
            });
        } catch (\Exception $e) {
            $meal_schedule_id = $this->httpCode($e);
            return $this->returnModel($meal_schedule_id, Helper::ERROR, $e->getMessage());
        }
    }

    /**
     * Remove the specified profileMealSchedule from storage.
     */
    public function deleteProfileMealSchedule($profileMealScheduleId): array
    {
        try {
            return DB::transaction(function () use ($profileMealScheduleId) {
                $profileMealSchedule = ProfileMealSchedule::findOrFail($profileMealScheduleId);
                $profileMealSchedule->delete();

                return $this->returnModel(200, Helper::SUCCESS, 'ProfileMealSchedule deleted successfully!');
            });
        } catch (\Exception $e) {
            $meal_schedule_id = $this->httpCode($e);
            return $this->returnModel($meal_schedule_id, Helper::ERROR, $e->getMessage());
        }
    }
}
