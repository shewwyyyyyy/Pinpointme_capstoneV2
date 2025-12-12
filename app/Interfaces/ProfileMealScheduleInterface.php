<?php

namespace App\Interfaces;

interface ProfileMealScheduleInterface
{
    /**
     * Store a newly created profileMealSchedule in storage.
     */
    public function storeProfileMealSchedule(array $data): array;

    /**
     * Update the specified profileMealSchedule in storage.
     */
    public function updateProfileMealSchedule($profileMealScheduleId, array $data): array;

    /**
     * Remove the specified profileMealSchedule from storage.
     */
    public function deleteProfileMealSchedule($profileMealScheduleId): array;
}
