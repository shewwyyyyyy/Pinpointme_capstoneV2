<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\PropertyInterface;
use App\Models\Property;
use App\Models\MealSchedule;
use App\Models\MealScheduleItem;
use App\Models\Profile;
use App\Models\PropertyMealSchedule;
use App\Models\User;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PropertyService implements PropertyInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created property in storage.
     */
    public function storeProperty(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $property = Property::create([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'username' => $data['username'],
                    'unique_identifier' => $data['unique_identifier'],
                    'description' => $data['description']
                ]);

                // Create meal schedule if provided, otherwise create default
                if (isset($data['schedule']) && !empty($data['schedule'])) {
                    $this->createMealSchedule($property, $data['schedule']);
                } else {
                    // Create default meal schedule
                    $this->createDefaultMealSchedule($property);
                }

                // If username and unique_identifier are provided, create a user and profile
                if (!empty($data['username']) && !empty($data['unique_identifier'])) {
                    $this->createUserAndProfile($property, $data);
                }

                return $this->returnModel(201, Helper::SUCCESS, 'Property created successfully!', $property, $property->id);
            });
        } catch (\Exception $e) {
            $code = $this->httpCode($e);
            return $this->returnModel($code, Helper::ERROR, $e->getMessage());
        }
    }

    /**
     * Update the specified property in storage.
     */
    public function updateProperty($propertyId, array $data): array
    {
        try {
            return DB::transaction(function () use ($propertyId, $data) {
                $property = Property::findOrFail($propertyId);
                
                // Store old name for comparison
                $oldPropertyName = $property->name;

                $property = tap($property)->update([
                    'name' => $data['name'] ?? $property->name,
                    'code' => $data['code'] ?? $property->code,
                    'username' => $data['username'] ?? $property->username,
                    'unique_identifier' => $data['unique_identifier'] ?? $property->unique_identifier,
                    'description' => $data['description'] ?? $property->description
                ]);

                // Update associated user and profile if they exist
                $this->updateUserAndProfile($property, $data, $oldPropertyName);

                if (isset($data['schedule']) && !empty($data['schedule'])) {
                    $this->updateMealSchedule($property, $data['schedule']);
                }

                return $this->returnModel(200, Helper::SUCCESS, 'Property updated successfully!', $property, $property->id);
            });
        } catch (\Exception $e) {
            $code = $this->httpCode($e);
            return $this->returnModel($code, Helper::ERROR, $e->getMessage());
        }
    }

    /**
     * Remove the specified property from storage.
     */
    public function deleteProperty($propertyId): array
    {
        try {
            return DB::transaction(function () use ($propertyId) {
                $property = Property::findOrFail($propertyId);
                $property->delete();

                return $this->returnModel(200, Helper::SUCCESS, 'Property deleted successfully!');
            });
        } catch (\Exception $e) {
            $code = $this->httpCode($e);
            return $this->returnModel($code, Helper::ERROR, $e->getMessage());
        }
    }


    private function updateMealSchedule(Property $property, array $scheduleData): void
    {
        $userId = Auth::id() ?? 1;
        
        $propertyMealSchedule = PropertyMealSchedule::where('property_id', $property->id)->first();

        if ($propertyMealSchedule && $propertyMealSchedule->mealSchedule) {
            $mealSchedule = $propertyMealSchedule->mealSchedule;
            
            $mealSchedule->update([
                'updated_by' => $userId,
            ]);

            $enabledDays = array_keys($scheduleData);
            
            MealScheduleItem::where('meal_schedule_id', $mealSchedule->id)
                ->whereNotIn('day_type', $enabledDays)
                ->delete();

            foreach ($scheduleData as $day => $meals) {
                foreach (['breakfast', 'lunch', 'dinner'] as $mealType) {
                    if (isset($meals["{$mealType}_start"]) && isset($meals["{$mealType}_end"])) {
                        MealScheduleItem::updateOrCreate(
                            [
                                'meal_schedule_id' => $mealSchedule->id,
                                'day_type' => $day,
                                'meal_type' => $mealType,
                            ],
                            [
                                'time_start' => $meals["{$mealType}_start"] . ':00',
                                'time_end' => $meals["{$mealType}_end"] . ':00',
                            ]
                        );
                    }
                }
            }
        }
    }


    private function createDefaultMealSchedule(Property $property): void
    {
        $userId = Auth::id() ?? 1;
        
        $scheduleName = $property->name . ' Meal Schedule';

        $mealSchedule = MealSchedule::create([
            'name' => $scheduleName,
            'remarks' => 'Default weekly schedule for ' . $property->name,
            'days' => Helper::MEAL_SCHEDULE_DAYS,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Create default meal schedule items for each day
        foreach (Helper::MEAL_SCHEDULE_DAYS as $day) {
            MealScheduleItem::create([
                'meal_schedule_id' => $mealSchedule->id,
                'time_start' => '07:00:00',
                'time_end' => '10:00:00',
                'day_type' => $day,
                'meal_type' => Helper::MEAL_SCHEDULE_BREAKFAST,
            ]);
            MealScheduleItem::create([
                'meal_schedule_id' => $mealSchedule->id,
                'time_start' => '11:00:00',
                'time_end' => '14:00:00',
                'day_type' => $day,
                'meal_type' => Helper::MEAL_SCHEDULE_LUNCH,
            ]);
            MealScheduleItem::create([
                'meal_schedule_id' => $mealSchedule->id,
                'time_start' => '17:00:00',
                'time_end' => '20:00:00',
                'day_type' => $day,
                'meal_type' => Helper::MEAL_SCHEDULE_DINNER,
            ]);
        }

        PropertyMealSchedule::create([
            'property_id' => $property->id,
            'meal_schedule_id' => $mealSchedule->id,
        ]);
    }


    private function createMealSchedule(Property $property, array $scheduleData): void
    {
        $userId = Auth::id() ?? 1;
        
        $scheduleName = $property->name . ' Meal Schedule';

        $mealSchedule = MealSchedule::create([
            'name' => $scheduleName,
            'remarks' => 'Weekly schedule for ' . $property->name,
            'days' => Helper::MEAL_SCHEDULE_DAYS,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        foreach ($scheduleData as $day => $meals) {
            foreach (['breakfast', 'lunch', 'dinner'] as $mealType) {
                if (isset($meals["{$mealType}_start"]) && isset($meals["{$mealType}_end"])) {
                    MealScheduleItem::create([
                        'meal_schedule_id' => $mealSchedule->id,
                        'time_start' => $meals["{$mealType}_start"] . ':00',
                        'time_end' => $meals["{$mealType}_end"] . ':00',
                        'day_type' => $day,
                        'meal_type' => $mealType,
                    ]);
                }
            }
        }

        PropertyMealSchedule::create([
            'property_id' => $property->id,
            'meal_schedule_id' => $mealSchedule->id,
        ]);
    }

    /**
      * Create a user and profile for the property
     * 
     * @param Property $property The property for which to create the user and profile.
     * @param array $data The data used to create the user and profile.
     */
    private function createUserAndProfile(Property $property, array $data): void
    {
        // Split property name into first and last names
        $nameParts = explode(' ', trim($property->name), 2);
        $firstName = $nameParts[0] ?? $property->name;
        $lastName = $nameParts[1] ?? null;

        // Create user account
        $user = User::create([
            'username' => $data['username'],
            'email' => null, // Email is not needed for property
            'password' => bcrypt($data['username']), // Default password, should be changed
            'isAdmin' => false,
            'is_able_to_login' => true,
            'status' => 'active',
        ]);

        // Create profile linked to this property
        Profile::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'unique_identifier' => $data['unique_identifier'],
            'position' => null, // Position is not needed for property
            'property_id' => $property->id,
            'department_id' => null, // Department is not needed for property
            'location_id' => 1, // Default location, should be configurable
        ]);
    }

    /**
     * Update user and profile for the property
     */
    private function updateUserAndProfile(Property $property, array $data, $oldPropertyName = null): void
    {
        // Find existing profile for this property
        $profile = Profile::where('property_id', $property->id)->first();

        if ($profile) {
            // Update existing user and profile
            $user = $profile->user;

            if ($user && isset($data['username']) && !empty($data['username'])) {
                $user->update([
                    'username' => $data['username']
                ]);
            }

            // Update profile if unique_identifier is provided
            if (isset($data['unique_identifier'])) {
                $profile->update([
                    'unique_identifier' => $data['unique_identifier']
                ]);
            }

            // Update profile names if property name changed
            if (isset($data['name']) && $data['name'] !== $oldPropertyName) {
                $nameParts = explode(' ', trim($data['name']), 2);
                $firstName = $nameParts[0] ?? $data['name'];
                $lastName = $nameParts[1] ?? null;

                $profile->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName
                ]);
            }
        } else if (!empty($data['username']) && !empty($data['unique_identifier'])) {
            // Create new user and profile if they don't exist but username and unique_identifier are provided
            $this->createUserAndProfile($property, $data);
        }
    }

    /**
     * Update property username when profile/user username changes
     * Only updates if the profile represents the property (matching username and unique_identifier)
     * @param int|string $profileId
     * @param string $newUsername
     * @param string|null $oldUniqueIdentifier The profile's unique_identifier before update
     * @return void
     */
    public function updatePropertyUsernameFromProfile($profileId, $newUsername, $oldUniqueIdentifier = null): void
    {
        try {
            $profile = Profile::findOrFail($profileId);
            
            if ($profile->property_id) {
                $property = Property::find($profile->property_id);
                
                // Only update if this profile represents the property
                // Use the old unique_identifier if provided (for cases where unique_identifier is being changed)
                // Otherwise use the current unique_identifier
                $identifierToCheck = $oldUniqueIdentifier ?? $profile->unique_identifier;
                
                if ($property && 
                    $property->username !== $newUsername && 
                    $property->unique_identifier === $identifierToCheck) {
                    $property->update([
                        'username' => $newUsername
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't throw to avoid breaking profile update
            Log::error('Failed to update property username from profile: ' . $e->getMessage());
        }
    }

    /**
     * Update property name when profile names change
     * Only updates if the profile represents the property (matching unique_identifier)
     * @param int|string $profileId
     * @param string $firstName
     * @param string|null $lastName
     * @param string|null $oldUniqueIdentifier The profile's unique_identifier before update
     */
    public function updatePropertyNameFromProfile($profileId, $firstName, $lastName = null, $oldUniqueIdentifier = null): void
    {
        try {
            $profile = Profile::findOrFail($profileId);
            
            if ($profile->property_id) {
                $property = Property::find($profile->property_id);
                
                // Only update if this profile represents the property
                // Use the old unique_identifier if provided (for cases where unique_identifier is being changed)
                // Otherwise use the current unique_identifier
                $identifierToCheck = $oldUniqueIdentifier ?? $profile->unique_identifier;
                
                if ($property && $property->unique_identifier === $identifierToCheck) {
                    // Construct full name from first and last name
                    $newPropertyName = trim($firstName . ($lastName ? ' ' . $lastName : ''));
                    
                    if ($property->name !== $newPropertyName) {
                        $property->update([
                            'name' => $newPropertyName
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error but don't throw to avoid breaking profile update
            Log::error('Failed to update property name from profile: ' . $e->getMessage());
        }
    }

    /**
     * Update property unique_identifier when profile unique_identifier changes
     * Only updates if the profile represents the property (was previously matching)
     */
    public function updatePropertyUniqueIdentifierFromProfile($profileId, $newUniqueIdentifier, $oldUniqueIdentifier = null): void
    {
        try {
            $profile = Profile::findOrFail($profileId);
            
            if ($profile->property_id) {
                $property = Property::find($profile->property_id);
                
                // Only update if this profile represents the property
                // Check if the OLD unique_identifier matched the property's unique_identifier
                // This prevents regular employees from changing the property unique_identifier
                if ($property && 
                    $property->unique_identifier !== $newUniqueIdentifier &&
                    ($oldUniqueIdentifier === null || $property->unique_identifier === $oldUniqueIdentifier)) {
                    $property->update([
                        'unique_identifier' => $newUniqueIdentifier
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't throw to avoid breaking profile update
            Log::error('Failed to update property unique_identifier from profile: ' . $e->getMessage());
        }
    }
}
