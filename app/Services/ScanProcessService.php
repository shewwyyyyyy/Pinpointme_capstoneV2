<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\ScanHistoryInterface;
use App\Interfaces\ScanProcessInterface;
use App\Models\MealSchedule;
use App\Models\MealScheduleItem;
use App\Models\Profile;
use App\Models\PropertyMealSchedule;
use App\Traits\EnsureSuccessTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScanProcessService implements ScanProcessInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        EnsureSuccessTrait;

    public function __construct(
        private ScanHistoryInterface $scanHistory
    ) {}

    /**
     * Process a scan action.
     */
    public function processScan(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {

                // Find the profile associated with the scanned QR code.
                $profile = Profile::where('unique_identifier', $data['unique_identifier'])->first();

                if (!$profile) {
                    return $this->returnModel(201, Helper::SUCCESS, 'Unidentified qr code');
                }

                // Check if selected position filter matches the profile's position
                if (isset($data['selected_position']) && !empty($data['selected_position'])) {
                    if ($profile->position !== $data['selected_position']) {
                        return $this->returnModel(
                            201,
                            Helper::SUCCESS,
                            "Scanner is set to {$data['selected_position']} mode. This QR code belongs to a {$profile->position}."
                        );
                    }
                }

                // Check if OJT position with expired dates
                if ($profile->position === 'OJT') {
                    $dateExpiration = $this->checkOJTDateExpiration($profile);
                    if (!$dateExpiration['valid']) {
                        return $this->returnModel(
                            201,
                            Helper::SUCCESS,
                            $dateExpiration['message']
                        );
                    }
                }

                // Check if meal count exceeded; allow scan but capture warning
                $mealCountCheck = $this->checkMealCountExceeded($profile);
                $mealCountWarning = null;
                if (!$mealCountCheck['valid']) {
                    $mealCountWarning = $mealCountCheck['message'];
                }

                // Check if the day is available for scanning
                $dayAvailability = $this->checkDayAvailability($data['propertyId']);
                if (!$dayAvailability['available']) {
                    return $this->returnModel(
                        201, 
                        Helper::SUCCESS, 
                        $dayAvailability['message']
                    );
                }

                // Get current meal timeframe
                $currentMealTimeframe = $this->getCurrentMealTimeframe($data['propertyId']);
                
                // Check if user has already scanned within the current meal timeframe
                if ($currentMealTimeframe) {
                    $mealTimeframeScan = $this->checkMealTimeframeScan($profile->id, $currentMealTimeframe);
                    if ($mealTimeframeScan['already_scanned']) {
                        return $this->returnModel(
                            201,
                            Helper::SUCCESS,
                            $mealTimeframeScan['message']
                        );
                    }
                }

                // Determine meal type based on property's meal schedule
                $mealType = $this->determineMealType($data['propertyId']);

                // Calculate current meal count for this profile (resets daily)
                $today = Carbon::now('Asia/Manila')->toDateString();
                $currentMealCount = $profile->scanHistories()
                    ->whereDate('scanned_at', $today)
                    ->count() + 1; // +1 for current scan

                // If all checks pass, proceed with scanning
                $scanData = [
                    'profile_id' => $profile->id,
                    'scanned_at' => Carbon::now(),
                    'property_id' => $data['propertyId'],
                    'meal_schedule' => $mealType,
                    'start_date' => $profile->start_date,
                    'end_date' => $profile->end_date,
                    'meal_count' => $currentMealCount
                ];

                $scanResult = $this->scanHistory->storeScanHistory($scanData);

                $this->ensureSuccess($scanResult, 'Failed to scan.');

                $message = "Successfully scanned! Welcome, {$profile->first_name} {$profile->last_name}.";
                if ($mealCountWarning) {
                    $message .= ' Warning: ' . $mealCountWarning;
                }

                return $this->returnModel(
                    201,
                    Helper::SUCCESS,
                    $message,
                    $profile,
                    $profile->id
                );
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, Helper::ERROR, $th->getMessage());
        }
    }

    /**
     * Check if OJT position dates have expired
     */
    private function checkOJTDateExpiration(Profile $profile): array
    {
        try {
            $now = Carbon::now()->format('Y-m-d');
            
            if (!$profile->start_date || !$profile->end_date) {
                return [
                    'valid' => false,
                    'message' => 'OJT profile missing start or end date.'
                ];
            }

            if ($now < $profile->start_date) {
                return [
                    'valid' => false,
                    'message' => 'OJT has not started yet. Start date: ' . $profile->start_date
                ];
            }

            if ($now > $profile->end_date) {
                return [
                    'valid' => false,
                    'message' => 'OJT has expired. End date was: ' . $profile->end_date
                ];
            }

            return [
                'valid' => true,
                'message' => 'OJT dates are valid.'
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error checking OJT expiration: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if meal count has exceeded meal entitlement
     */
    private function checkMealCountExceeded(Profile $profile): array
    {
        try {
            // Default to 1 for Employee position if meal_entitlement is not set
            $mealEntitlement = $profile->meal_entitlement 
                ? (int)$profile->meal_entitlement 
                : ($profile->position === 'Employee' ? 1 : 0);
            
            if ($mealEntitlement === 0) {
                return [
                    'valid' => true,
                    'message' => 'No meal entitlement set.',
                    'meal_count' => 0
                ];
            }

            // Count only today's scans (meal count resets daily)
            $today = Carbon::now('Asia/Manila')->toDateString();
            $mealCount = $profile->scanHistories()->whereDate('scanned_at', $today)->count();

            if ($mealCount >= $mealEntitlement) {
                return [
                    'valid' => false,
                    'message' => "Meal count exceeded. Current: {$mealCount}, Entitlement: {$mealEntitlement}",
                    'meal_count' => $mealCount,
                    'meal_entitlement' => $mealEntitlement
                ];
            }

            return [
                'valid' => true,
                'message' => "Meal count within limit. Current: {$mealCount}, Entitlement: {$mealEntitlement}",
                'meal_count' => $mealCount,
                'meal_entitlement' => $mealEntitlement
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error checking meal count: ' . $e->getMessage()
            ];
        }
    }


    private function determineMealType(?int $propertyId): string
    {
        if (!$propertyId) {
            return 'Unknown';
        }

        try {
            $now = Carbon::now('Asia/Manila');
            $currentTime = $now->format('H:i:s');
            $currentDay = $now->format('l');

            $propertyMealSchedule = PropertyMealSchedule::where('property_id', $propertyId)
                ->with('mealSchedule.mealSchedule')
                ->first();

            if (!$propertyMealSchedule || !$propertyMealSchedule->mealSchedule) {
                return 'No Schedule';
            }

            $mealScheduleItems = MealScheduleItem::where('meal_schedule_id', $propertyMealSchedule->meal_schedule_id)
                ->where('day_type', $currentDay)
                ->get();

            if ($mealScheduleItems->isEmpty()) {
                return 'Day Not Available';
            }

            $mealTypes = ['breakfast', 'lunch', 'dinner'];
            $lastMeal = null;

            foreach ($mealTypes as $mealType) {
                $item = $mealScheduleItems->firstWhere('meal_type', $mealType);

                if ($item) {
                    $lastMeal = $item;

                    // Check if current time is within this meal period
                    if ($currentTime >= $item->time_start && $currentTime <= $item->time_end) {
                        return ucfirst($mealType);
                    }

                    // Check if current time is after this meal's end but before the next meal
                    if ($currentTime > $item->time_end) {
                        // Continue to check if it's within the next meal period
                        continue;
                    }

                    // If current time is before this meal starts
                    if ($currentTime < $item->time_start) {
                        // Check if it's late from the previous meal
                        $prevMealIndex = array_search($mealType, $mealTypes) - 1;
                        if ($prevMealIndex >= 0) {
                            $prevMeal = $mealScheduleItems->firstWhere('meal_type', $mealTypes[$prevMealIndex]);
                            if ($prevMeal && $currentTime > $prevMeal->time_end) {
                                return ucfirst($mealTypes[$prevMealIndex]) . '-Late';
                            }
                        }
                        // If it's before the first meal, it might be very early or very late from dinner
                        return 'Early/Before Schedule';
                    }
                }
            }

            // If we've gone through all meals and current time is after the last meal
            if ($lastMeal && $currentTime > $lastMeal->time_end) {
                return ucfirst($lastMeal->meal_type) . '-Late';
            }

            return 'Unknown';
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Get the current meal timeframe (start time, end time, meal type)
     */
    private function getCurrentMealTimeframe(?int $propertyId): ?array
    {
        if (!$propertyId) {
            return null;
        }

        try {
            $now = Carbon::now('Asia/Manila');
            $currentTime = $now->format('H:i:s');
            $currentDay = $now->format('l');

            $propertyMealSchedule = PropertyMealSchedule::where('property_id', $propertyId)
                ->with('mealSchedule.mealSchedule')
                ->first();

            if (!$propertyMealSchedule || !$propertyMealSchedule->mealSchedule) {
                return null;
            }

            $mealScheduleItems = MealScheduleItem::where('meal_schedule_id', $propertyMealSchedule->meal_schedule_id)
                ->where('day_type', $currentDay)
                ->get();

            if ($mealScheduleItems->isEmpty()) {
                return null;
            }

            $mealTypes = ['breakfast', 'lunch', 'dinner'];

            foreach ($mealTypes as $mealType) {
                $item = $mealScheduleItems->firstWhere('meal_type', $mealType);
                
                if ($item) {
                    // Check if current time is within this meal period
                    if ($currentTime >= $item->time_start && $currentTime <= $item->time_end) {
                        return [
                            'start' => $item->time_start,
                            'end' => $item->time_end,
                            'meal_type' => ucfirst($mealType),
                            'raw_meal_type' => $mealType
                        ];
                    }
                    
                    // Check if current time is after this meal's end but should be considered "late" for this meal
                    if ($currentTime > $item->time_end) {
                        // Find next meal to determine if we're still in "late" period for current meal
                        $nextMealIndex = array_search($mealType, $mealTypes) + 1;
                        if ($nextMealIndex < count($mealTypes)) {
                            $nextMeal = $mealScheduleItems->firstWhere('meal_type', $mealTypes[$nextMealIndex]);
                            if ($nextMeal && $currentTime < $nextMeal->time_start) {
                                // We're in the "late" period for the current meal
                                return [
                                    'start' => $item->time_start,
                                    'end' => $item->time_end,
                                    'meal_type' => ucfirst($mealType) . '-Late',
                                    'raw_meal_type' => $mealType
                                ];
                            }
                        } else {
                            // This is the last meal of the day, consider it "late" until end of day
                            return [
                                'start' => $item->time_start,
                                'end' => '23:59:59',
                                'meal_type' => ucfirst($mealType) . '-Late',
                                'raw_meal_type' => $mealType
                            ];
                        }
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if the current day is available for scanning
     */
    private function checkDayAvailability(?int $propertyId): array
    {
        if (!$propertyId) {
            return [
                'available' => false,
                'message' => 'No property specified for scanning.'
            ];
        }

        try {
            $now = Carbon::now('Asia/Manila');
            $currentDay = $now->format('l');

            $propertyMealSchedule = PropertyMealSchedule::where('property_id', $propertyId)
                ->with('mealSchedule.mealSchedule')
                ->first();

            if (!$propertyMealSchedule || !$propertyMealSchedule->mealSchedule) {
                return [
                    'available' => false,
                    'message' => 'No meal schedule configured for this property.'
                ];
            }

            $mealScheduleItems = MealScheduleItem::where('meal_schedule_id', $propertyMealSchedule->meal_schedule_id)
                ->where('day_type', $currentDay)
                ->get();

            if ($mealScheduleItems->isEmpty()) {
                return [
                    'available' => false,
                    'message' => "Scanning is not available on {$currentDay}. Please check the meal schedule."
                ];
            }

            return [
                'available' => true,
                'message' => 'Day is available for scanning.'
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'message' => 'Error checking day availability: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if user has already scanned within the current meal timeframe
     */
    private function checkMealTimeframeScan(int $profileId, array $mealTimeframe): array
    {
        try {
            $now = Carbon::now('Asia/Manila');
            $today = $now->toDateString();
            
            // Get the meal type without the "-Late" suffix for database lookup
            $mealType = $mealTimeframe['raw_meal_type'];
            $displayMealType = $mealTimeframe['meal_type'];

            // Check if user has already scanned for this meal today
            $existingScan = DB::table('scan_histories')
                ->where('profile_id', $profileId)
                ->whereDate('scanned_at', $today)
                ->where(function($query) use ($mealType) {
                    $query->where('meal_schedule', ucfirst($mealType))
                          ->orWhere('meal_schedule', ucfirst($mealType) . '-Late');
                })
                ->orderBy('scanned_at', 'desc')
                ->first();

            if ($existingScan) {
                $scanTime = Carbon::parse($existingScan->scanned_at)->format('h:i A');
                
                return [
                    'already_scanned' => true,
                    'message' => "You have already scanned for {$displayMealType} today at {$scanTime}. You can only scan once per meal period."
                ];
            }

            return [
                'already_scanned' => false,
                'message' => 'No previous scan found for this meal period.'
            ];
        } catch (\Exception $e) {
            return [
                'already_scanned' => false,
                'message' => 'Error checking meal timeframe scan: ' . $e->getMessage()
            ];
        }
    }
}
